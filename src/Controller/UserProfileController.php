<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Controller;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use UserFrosting\Fortress\RequestDataTransformer;
//use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Fortress\Adapter\JqueryValidationAdapter;
use UserFrosting\Sprinkle\Account\Model\Group;
use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\Account\Util\Password;
use UserFrosting\Sprinkle\Admin\Sprunje\ActivitySprunje;
use UserFrosting\Sprinkle\Admin\Sprunje\RoleSprunje;
use UserFrosting\Sprinkle\Admin\Sprunje\UserSprunje;
use UserFrosting\Sprinkle\Core\Facades\Debug;
use UserFrosting\Sprinkle\Core\Mail\EmailRecipient;
use UserFrosting\Sprinkle\Core\Mail\TwigMailMessage;
use UserFrosting\Support\Exception\BadRequestException;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Support\Exception\HttpException;

use Interop\Container\ContainerInterface;
use UserFrosting\Sprinkle\Admin\Controller\UserController;
use UserFrosting\Sprinkle\UserProfile\Util\UserProfileHelper;
use UserFrosting\Sprinkle\FormGenerator\RequestSchema;

class UserProfileController extends UserController
{
    protected $profileHelper;

    /**
     * Constructor.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->profileHelper = new UserProfileHelper($ci);
        return parent::__construct($ci);
    }

    /**
     * Processes the request to create a new user (from the admin controls).
     *
     * Processes the request from the user creation form, checking that:
     * 1. The username and email are not already in use;
     * 2. The logged-in user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication.
     * Request type: POST
     * @see formUserCreate
     */
    public function create($request, $response, $args)
    {
        // Get POST parameters: user_name, first_name, last_name, email, locale, (group)
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_user')) {
            throw new ForbiddenException();
        }

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();

        // Load the request schema
        $schema = new RequestSchema('schema://user/create.json');
        $schema->appendSchema($cutomsFields);

        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);

        $error = false;

        // Validate request data
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms->addValidationErrors($validator);
            $error = true;
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Check if username or email already exists
        if ($classMapper->staticMethod('user', 'exists', $data['user_name'], 'user_name')) {
            $ms->addMessageTranslated('danger', 'USERNAME.IN_USE', $data);
            $error = true;
        }

        if ($classMapper->staticMethod('user', 'exists', $data['email'], 'email')) {
            $ms->addMessageTranslated('danger', 'EMAIL.IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        /** @var Config $config */
        $config = $this->ci->config;

        // If currentUser does not have permission to set the group, but they try to set it to something other than their own group,
        // throw an exception.
        if (!$authorizer->checkAccess($currentUser, 'create_user_field', [
            'fields' => ['group']
        ])) {
            if (isset($data['group_id']) && $data['group_id'] != $currentUser->group_id) {
                throw new ForbiddenException();
            }
        }

        // In any case, set the group id if not otherwise set
        if (!isset($data['group_id'])) {
            $data['group_id'] = $currentUser->group_id;
        }

        $data['flag_verified'] = 1;
        // Set password as empty on initial creation.  We will then send email so new user can set it themselves via a verification token
        $data['password'] = '';

        // All checks passed!  log events/activities, create user, and send verification email (if required)
        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($classMapper, $data, $ms, $config, $currentUser) {
            // Create the user
            $user = $classMapper->createInstance('user', $data);

            // Store new user to database
            $user->save();

            // We now have to update the custom profile fields
            $this->profileHelper->setProfile($user, $data);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} created a new account for {$user->user_name}.", [
                'type' => 'account_create',
                'user_id' => $currentUser->id
            ]);

            // Load default roles
            $defaultRoleSlugs = $classMapper->staticMethod('role', 'getDefaultSlugs');
            $defaultRoles = $classMapper->staticMethod('role', 'whereIn', 'slug', $defaultRoleSlugs)->get();
            $defaultRoleIds = $defaultRoles->pluck('id')->all();

            // Attach default roles
            $user->roles()->attach($defaultRoleIds);

            // Try to generate a new password request
            $passwordRequest = $this->ci->repoPasswordReset->create($user, $config['password_reset.timeouts.create']);

            // Create and send welcome email with password set link
            $message = new TwigMailMessage($this->ci->view, 'mail/password-create.html.twig');

            $message->from($config['address_book.admin'])
                    ->addEmailRecipient(new EmailRecipient($user->email, $user->full_name))
                    ->addParams([
                        'user' => $user,
                        'create_password_expiration' => $config['password_reset.timeouts.create'] / 3600 . ' hours',
                        'token' => $passwordRequest->getToken()
                    ]);

            $this->ci->mailer->send($message);

            $ms->addMessageTranslated('success', 'USER.CREATED', $data);
        });

        return $response->withStatus(200);
    }

    /**
     * Renders a page displaying a user's information, in read-only mode.
     *
     * Overrides the base `UserController:pageInfo` method, to display additional user fields.
     * Request type: GET
     */
    public function pageInfo($request, $response, $args)
    {
        $user = $this->getUserFromParams($args);

        // If the user no longer exists, forward to main user listing page
        if (!$user) {
            $usersPage = $this->ci->router->pathFor('uri_users');
            return $response->withRedirect($usersPage, 404);
        }

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'uri_user', [
                'user' => $user
            ])) {
            throw new ForbiddenException();
        }

        /** @var Config $config */
        $config = $this->ci->config;

        // Get a list of all locales
        $locales = $config['site.locales.available'];

        // Determine fields that currentUser is authorized to view
        $fieldNames = ['name', 'email', 'locale'];

        // Generate form
        $fields = [
            // Always hide these
            'hidden' => ['user_name', 'group', 'theme'],
            'disabled' => []
        ];

        // Determine which fields should be hidden entirely
        foreach ($fieldNames as $field) {
            if ($authorizer->checkAccess($currentUser, 'view_user_field', [
                'user' => $user,
                'property' => $field
            ])) {
                $fields['disabled'][] = $field;
            } else {
                $fields['hidden'][] = $field;
            }
        }

        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $userCutomsFields = $this->profileHelper->getProfile($user);

        $schema = new RequestSchema();
        $schema->setSchema($cutomsFields);
        $schema->initForm($userCutomsFields);

        // Determine buttons to display
        $editButtons = [
            'hidden' => []
        ];

        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['name', 'email', 'locale']
        ])) {
            $editButtons['hidden'][] = 'edit';
        }

        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['flag_enabled']
        ])) {
            $editButtons['hidden'][] = 'enable';
        }

        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['flag_verified']
        ])) {
            $editButtons['hidden'][] = 'activate';
        }

        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['password']
        ])) {
            $editButtons['hidden'][] = 'password';
        }

        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['roles']
        ])) {
            $editButtons['hidden'][] = 'roles';
        }

        if (!$authorizer->checkAccess($currentUser, 'delete_user', [
            'user' => $user
        ])) {
            $editButtons['hidden'][] = 'delete';
        }

        return $this->ci->view->render($response, 'pages/user.html.twig', [
            'user' => $user,
            'locales' => $locales,
            'form' => [
                'fields' => $fields,
                'customFields' => $schema->generateForm(),
                'edit_buttons' => $editButtons
            ]
        ]);
    }

    /**
     * Renders the modal form for creating a new user.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * If the currently logged-in user has permission to modify user group membership, then the group toggle will be displayed.
     * Otherwise, the user will be added to the default group and receive the default roles automatically.
     * This page requires authentication.
     * Request type: GET
     */
    public function getModalCreate($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        $translator = $this->ci->translator;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_user')) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var Config $config */
        $config = $this->ci->config;

        // Determine form fields to hide/disable
        // TODO: come back to this when we finish implementing theming
        $fields = [
            'hidden' => ['theme'],
            'disabled' => []
        ];

        // Get a list of all locales
        $locales = $config['site.locales.available'];

        // Determine if currentUser has permission to modify the group.  If so, show the 'group' dropdown.
        // Otherwise, set to the currentUser's group and disable the dropdown.
        if ($authorizer->checkAccess($currentUser, 'create_user_field', [
            'fields' => ['group']
        ])) {
            // Get a list of all groups
            $groups = $classMapper->staticMethod('group', 'all');
        } else {
            // Get the current user's group
            $groups = $classMapper->staticMethod('group', 'where', 'id', $currentUser->group_id);
            $fields['disabled'][] = 'group';
        }

        // Create a dummy user to prepopulate fields
        $data = [
            'group_id' => $currentUser->group_id,
            'locale'   => $config['site.registration.user_defaults.locale'],
            'theme'    => ''
        ];

        $user = $classMapper->createInstance('user', $data);

        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $userCutomsFields = $this->profileHelper->getProfile($user);

        $schema = new RequestSchema('schema://user/create.json');
        $schema->appendSchema($cutomsFields);
        $schema->initForm($userCutomsFields);

        // Load validation rules
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        return $this->ci->view->render($response, 'components/modals/user.html.twig', [
            'user' => $user,
            'groups' => $groups,
            'locales' => $locales,
            'form' => [
                'action' => 'api/users',
                'method' => 'POST',
                'fields' => $fields,
                'customFields' => $schema->generateForm(),
                'submit_text' => $translator->translate("CREATE")
            ],
            'page' => [
                'validators' => $validator->rules('json', false)
            ]
        ]);
    }

    /**
     * Renders the modal form for editing an existing user.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * This page requires authentication.
     * Request type: GET
     */
    public function getModalEdit($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        $user = $this->getUserFromParams($params);

        // If the user doesn't exist, return 404
        if (!$user) {
            throw new NotFoundException($request, $response);
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Get the user to edit
        $user = $classMapper->staticMethod('user', 'where', 'user_name', $user->user_name)
            ->with('group')
            ->first();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled resource - check that currentUser has permission to edit basic fields "name", "email", "locale" for this user
        $fieldNames = ['name', 'email', 'locale'];
        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => $fieldNames
        ])) {
            throw new ForbiddenException();
        }

        // Get a list of all groups
        $groups = $classMapper->staticMethod('group', 'all');

        /** @var Config $config */
        $config = $this->ci->config;

        // Get a list of all locales
        $locales = $config['site.locales.available'];

        // Generate form
        $fields = [
            'hidden' => ['theme'],
            'disabled' => ['user_name']
        ];

        // Disable group field if currentUser doesn't have permission to modify group
        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => ['group']
        ])) {
            $fields['disabled'][] = 'group';
        }

        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $userCutomsFields = $this->profileHelper->getProfile($user);

        $schema = new RequestSchema('schema://user/edit-info.json');
        $schema->appendSchema($cutomsFields);
        $schema->initForm($userCutomsFields);

        // Load validation rules
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        return $this->ci->view->render($response, 'components/modals/user.html.twig', [
            'user' => $user,
            'groups' => $groups,
            'locales' => $locales,
            'form' => [
                'action' => "api/users/u/{$user->user_name}",
                'method' => 'PUT',
                'fields' => $fields,
                'customFields' => $schema->generateForm(),
                'submit_text' => 'Update user'
            ],
            'page' => [
                'validators' => $validator->rules('json', false)
            ]
        ]);
    }

    /**
     * Processes the request to update an existing user's basic details (first_name, last_name, email, locale, group_id)
     *
     * Processes the request from the user update form, checking that:
     * 1. The target user's new email address, if specified, is not already in use;
     * 2. The logged-in user has the necessary permissions to update the putted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication.
     * Request type: PUT
     */
    public function updateInfo($request, $response, $args)
    {
        // Get the username from the URL
        $user = $this->getUserFromParams($args);

        if (!$user) {
            throw new NotFoundException($request, $response);
        }

        /** @var Config $config */
        $config = $this->ci->config;

        // Get PUT parameters
        $params = $request->getParsedBody();

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();

        // Load the request schema
        $schema = new RequestSchema('schema://user/edit-info.json');
        $schema->appendSchema($cutomsFields);

        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);

        $error = false;

        // Validate request data
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms->addValidationErrors($validator);
            $error = true;
        }

        // Determine targeted fields
        $fieldNames = [];
        foreach ($data as $name => $value) {
            if ($name == 'first_name' || $name == 'last_name') {
                $fieldNames[] = 'name';
            } elseif ($name == 'group_id') {
                $fieldNames[] = 'group';
            } else {
                $fieldNames[] = $name;
            }
        }

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled resource - check that currentUser has permission to edit submitted fields for this user
        if (!$authorizer->checkAccess($currentUser, 'update_user_field', [
            'user' => $user,
            'fields' => array_values(array_unique($fieldNames))
        ])) {
            throw new ForbiddenException();
        }

        // Only the master account can edit the master account!
        if (
            ($user->id == $config['reserved_user_ids.master']) &&
            ($currentUser->id != $config['reserved_user_ids.master'])
        ) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Check if email already exists
        if (
            isset($data['email']) &&
            $data['email'] != $user->email &&
            $classMapper->staticMethod('user', 'exists', $data['email'], 'email')
        ) {
            $ms->addMessageTranslated('danger', 'EMAIL.IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($data, $user, $currentUser) {
            // Update the user and generate success messages
            foreach ($data as $name => $value) {
                if (isset($user->$name) && $value != $user->$name) {
                    $user->$name = $value;
                }
            }

            $user->save();

            // We now have to update the custom profile fields
            $this->profileHelper->setProfile($user, $data);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated basic account info for user {$user->user_name}.", [
                'type' => 'account_update_info',
                'user_id' => $currentUser->id
            ]);
        });

        $ms->addMessageTranslated('success', 'DETAILS_UPDATED', [
            'user_name' => $user->user_name
        ]);
        return $response->withStatus(200);
    }



    /**
     * Account settings page.
     *
     * Provides a form for users to modify various properties of their account, such as name, email, locale, etc.
     * Any fields that the user does not have permission to modify will be automatically disabled.
     * This page requires authentication.
     * Request type: GET
     */
    public function pageSettings($request, $response, $args)
    {
        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'uri_account_settings')) {
            throw new ForbiddenException();
        }

        // Load validation rules
        $schema = new RequestSchema("schema://account-settings.json");
        $validatorAccountSettings = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $userCutomsFields = $this->profileHelper->getProfile($currentUser);

        $schema = new RequestSchema("schema://profile-settings.json");
        $schema->appendSchema($cutomsFields);
        $schema->initForm($userCutomsFields);

        // Load validator as usual
        $validatorProfileSettings = new JqueryValidationAdapter($schema, $this->ci->translator);

        /** @var Config $config */
        $config = $this->ci->config;

        // Get a list of all locales
        $locales = $config['site.locales.available'];

        return $this->ci->view->render($response, 'pages/account-settings.html.twig', [
            "locales" => $locales,
            'customFields' => $schema->generateForm(),
            "page" => [
                "validators" => [
                    "account_settings"    => $validatorAccountSettings->rules('json', false),
                    "profile_settings"    => $validatorProfileSettings->rules('json', false)
                ],
                "visibility" => ($authorizer->checkAccess($currentUser, "update_account_settings") ? "" : "disabled")
            ]
        ]);
    }

    /**
     * Processes a request to update a user's profile information.
     *
     * Processes the request from the user profile settings form, checking that:
     * 1. They have the necessary permissions to update the posted field(s);
     * 2. The submitted data is valid.
     * This route requires authentication.
     * Request type: POST
     */
    public function profile($request, $response, $args)
    {
        /** @var UserFrosting\Sprinkle\Core\MessageStream $ms */
        $ms = $this->ci->alerts;

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access control for entire resource - check that the current user has permission to modify themselves
        // See recipe "per-field access control" for dynamic fine-grained control over which properties a user can modify.
        if (!$authorizer->checkAccess($currentUser, 'update_account_settings')) {
            $ms->addMessageTranslated("danger", "ACCOUNT.ACCESS_DENIED");
            return $response->withStatus(403);
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\Config\Config $config */
        $config = $this->ci->config;

        // POST parameters
        $params = $request->getParsedBody();

        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();

        // Load the request schema
        $schema = new RequestSchema("schema://profile-settings.json");
        $schema->appendSchema($cutomsFields);

        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);

        $error = false;

        // Validate, and halt on validation errors.
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms->addValidationErrors($validator);
            $error = true;
        }

        // Check that locale is valid
        $locales = $config['site.locales.available'];
        if (!array_key_exists($data['locale'], $locales)) {
            $ms->addMessageTranslated("danger", "LOCALE.INVALID", $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // Looks good, let's update with new values!
        // Note that only fields listed in `profile-settings.json` will be permitted in $data, so this prevents the user from updating all columns in the DB
        $currentUser->fill($data);

        $currentUser->save();

        // We now have to update the custom profile fields
        $this->profileHelper->setProfile($currentUser, $data);

        // Create activity record
        $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated their profile settings.", [
            'type' => 'update_profile_settings'
        ]);

        $ms->addMessageTranslated("success", "PROFILE.UPDATED");
        return $response->withStatus(200);
    }
}