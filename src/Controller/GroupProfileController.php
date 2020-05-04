<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Controller;

use Illuminate\Database\Capsule\Manager as Capsule;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use UserFrosting\Fortress\Adapter\JqueryValidationAdapter;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\RequestSchema\RequestSchemaRepository;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Admin\Controller\GroupController;
use UserFrosting\Sprinkle\FormGenerator\Form;
use UserFrosting\Sprinkle\UserProfile\Util\GroupProfileHelper;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;

/**
 * Controller class for group-related requests, including listing groups, CRUD for groups, etc.
 *
 * @author Alex Weissman (https://alexanderweissman.com)
 */
class GroupProfileController extends GroupController
{
    protected $profileHelper;

    /**
     * Constructor.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->profileHelper = new GroupProfileHelper($ci);

        return parent::__construct($ci);
    }

    /**
     * Processes the request to create a new group.
     *
     * Processes the request from the group creation form, checking that:
     * 1. The group name and slug are not already in use;
     * 2. The user has permission to create a new group;
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     *
     * @see getModalCreateGroup
     */
    public function create($request, $response, $args)
    {
        // Get POST parameters: name, slug, icon, description
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager $authorizer */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_group')) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\MessageStream $ms */
        $ms = $this->ci->alerts;

        //-->
        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();

        // Load the schema file content
        $loader = new YamlFileLoader('schema://requests/group/create.yaml');
        $loaderContent = $loader->load();

        // Add the custom fields
        $loaderContent = array_merge($loaderContent, $cutomsFields);

        // Get the schema repo, validator and create the form
        $schema = new RequestSchemaRepository($loaderContent);
        //<--

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

        // Check if name or slug already exists
        if ($classMapper->staticMethod('group', 'where', 'name', $data['name'])->first()) {
            $ms->addMessageTranslated('danger', 'GROUP.NAME.IN_USE', $data);
            $error = true;
        }

        if ($classMapper->staticMethod('group', 'where', 'slug', $data['slug'])->first()) {
            $ms->addMessageTranslated('danger', 'GROUP.SLUG.IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        /** @var UserFrosting\Config\Config $config */
        $config = $this->ci->config;

        // All checks passed!  log events/activities and create group
        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction(function () use ($classMapper, $data, $ms, $config, $currentUser) {
            // Create the group
            $group = $classMapper->createInstance('group', $data);

            // Store new group to database
            $group->save();

            // We now have to update the custom profile fields
            $this->profileHelper->setProfile($group, $data);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} created group {$group->name}.", [
                'type'    => 'group_create',
                'user_id' => $currentUser->id,
            ]);

            $ms->addMessageTranslated('success', 'GROUP.CREATION_SUCCESSFUL', $data);
        });

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }

    /**
     * Renders the modal form for creating a new group.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * This page requires authentication.
     * Request type: GET
     */
    public function getModalCreate($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager $authorizer */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_group')) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Create a dummy group to prepopulate fields
        $group = $classMapper->createInstance('group', []);

        $group->icon = 'fa fa-user';

        $fieldNames = ['name', 'slug', 'icon', 'description'];
        $fields = [
            'hidden'   => [],
            'disabled' => [],
        ];

        //-->
        // Load more fields names
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $customProfile = $this->profileHelper->getProfile($group);

        // Load the schema file content
        $loader = new YamlFileLoader('schema://requests/group/create.yaml');
        $loaderContent = $loader->load();

        // Add the custom fields
        $loaderContent = array_merge($loaderContent, $cutomsFields);

        // Get the schema repo, validator and create the form
        $schema = new RequestSchemaRepository($loaderContent);
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);
        $form = new Form($schema, $customProfile);
        //<--

        return $this->ci->view->render($response, 'modals/group.html.twig', [
            'group' => $group,
            'form'  => [
                'action'       => 'api/groups',
                'method'       => 'POST',
                'fields'       => $fields,
                'customFields' => $form->generate(),
                'submit_text'  => $translator->translate('CREATE'),
            ],
            'page' => [
                'validators' => $validator->rules('json', false),
            ],
        ]);
    }

    /**
     * Renders the modal form for editing an existing group.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * This page requires authentication.
     * Request type: GET
     */
    public function getModalEdit($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        $group = $this->getGroupFromParams($params);

        // If the group doesn't exist, return 404
        if (!$group) {
            throw new NotFoundException($request, $response);
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager $authorizer */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        // Access-controlled resource - check that currentUser has permission to edit basic fields "name", "slug", "icon", "description" for this group
        $fieldNames = ['name', 'slug', 'icon', 'description'];
        if (!$authorizer->checkAccess($currentUser, 'update_group_field', [
            'group' => $group,
            'fields' => $fieldNames,
        ])) {
            throw new ForbiddenException();
        }

        // Generate form
        $fields = [
            'hidden'   => [],
            'disabled' => [],
        ];

        //-->
        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $customProfile = $this->profileHelper->getProfile($group);

        // Load the schema file content
        $loader = new YamlFileLoader('schema://requests/group/edit-info.yaml');
        $loaderContent = $loader->load();

        // Add the custom fields
        $loaderContent = array_merge($loaderContent, $cutomsFields);

        // Get the schema repo, validator and create the form
        $schema = new RequestSchemaRepository($loaderContent);
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);
        $form = new Form($schema, $customProfile);
        //<--

        return $this->ci->view->render($response, 'modals/group.html.twig', [
            'group' => $group,
            'form'  => [
                'action'       => "api/groups/g/{$group->slug}",
                'method'       => 'PUT',
                'fields'       => $fields,
                'customFields' => $form->generate(),
                'submit_text'  => $translator->translate('UPDATE'),
            ],
            'page' => [
                'validators' => $validator->rules('json', false),
            ],
        ]);
    }

    /**
     * Renders a page displaying a group's information, in read-only mode.
     *
     * This checks that the currently logged-in user has permission to view the requested group's info.
     * It checks each field individually, showing only those that you have permission to view.
     * This will also try to show buttons for deleting, and editing the group.
     * This page requires authentication.
     * Request type: GET
     */
    public function pageInfo($request, $response, $args)
    {
        $group = $this->getGroupFromParams($args);

        // If the group no longer exists, forward to main group listing page
        if (!$group) {
            $redirectPage = $this->ci->router->pathFor('uri_groups');

            return $response->withRedirect($redirectPage, 404);
        }

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager $authorizer */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'uri_group', [
            'group' => $group,
        ])) {
            throw new ForbiddenException();
        }

        // Determine fields that currentUser is authorized to view
        $fieldNames = ['name', 'slug', 'icon', 'description'];

        //-->
        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();
        $customProfile = $this->profileHelper->getProfile($group, true);

        $schema = new RequestSchemaRepository($cutomsFields);
        $form = new Form($schema, $customProfile);
        //<--

        // Generate form
        $fields = [
            'hidden' => [],
        ];

        foreach ($fieldNames as $field) {
            if (!$authorizer->checkAccess($currentUser, 'view_group_field', [
                'group' => $group,
                'property' => $field,
            ])) {
                $fields['hidden'][] = $field;
            }
        }

        // Determine buttons to display
        $editButtons = [
            'hidden' => [],
        ];

        if (!$authorizer->checkAccess($currentUser, 'update_group_field', [
            'group' => $group,
            'fields' => ['name', 'slug', 'icon', 'description'],
        ])) {
            $editButtons['hidden'][] = 'edit';
        }

        if (!$authorizer->checkAccess($currentUser, 'delete_group', [
            'group' => $group,
        ])) {
            $editButtons['hidden'][] = 'delete';
        }

        return $this->ci->view->render($response, 'pages/group.html.twig', [
            'group'        => $group,
            'fields'       => $fields,
            'customFields' => $form->generate(),
            'tools'        => $editButtons,
        ]);
    }

    /**
     * Processes the request to update an existing group's details.
     *
     * Processes the request from the group update form, checking that:
     * 1. The group name/slug are not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: PUT
     *
     * @see getModalGroupEdit
     */
    public function updateInfo($request, $response, $args)
    {
        // Get the group based on slug in URL
        $group = $this->getGroupFromParams($args);

        if (!$group) {
            throw new NotFoundException($request, $response);
        }

        /** @var UserFrosting\Config\Config $config */
        $config = $this->ci->config;

        // Get PUT parameters: (name, slug, icon, description)
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Core\MessageStream $ms */
        $ms = $this->ci->alerts;

        //-->
        // Load the custom fields
        $cutomsFields = $this->profileHelper->getFieldsSchema();

        // Load the schema file content
        $loader = new YamlFileLoader('schema://requests/group/edit-info.yaml');
        $loaderContent = $loader->load();

        // Add the custom fields
        $loaderContent = array_merge($loaderContent, $cutomsFields);

        // Get the schema repo, validator and create the form
        $schema = new RequestSchemaRepository($loaderContent);
        //<--

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
            $fieldNames[] = $name;
        }

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager $authorizer */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled resource - check that currentUser has permission to edit submitted fields for this group
        if (!$authorizer->checkAccess($currentUser, 'update_group_field', [
            'group' => $group,
            'fields' => array_values(array_unique($fieldNames)),
        ])) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Check if name or slug already exists
        if (
            isset($data['name']) &&
            $data['name'] != $group->name &&
            $classMapper->staticMethod('group', 'where', 'name', $data['name'])->first()
        ) {
            $ms->addMessageTranslated('danger', 'GROUP.NAME.IN_USE', $data);
            $error = true;
        }

        if (
            isset($data['slug']) &&
            $data['slug'] != $group->slug &&
            $classMapper->staticMethod('group', 'where', 'slug', $data['slug'])->first()
        ) {
            $ms->addMessageTranslated('danger', 'GROUP.SLUG.IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction(function () use ($data, $group, $currentUser) {
            // Update the group and generate success messages
            foreach ($data as $name => $value) {
                if (isset($group->$name) && $value != $group->$name) {
                    $group->$name = $value;
                }
            }

            $group->save();

            // We now have to update the custom profile fields
            $this->profileHelper->setProfile($group, $data);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated details for group {$group->name}.", [
                'type'    => 'group_update_info',
                'user_id' => $currentUser->id,
            ]);
        });

        $ms->addMessageTranslated('success', 'GROUP.UPDATE', [
            'name' => $group->name,
        ]);

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }
}
