<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2017 Louis Charette
 * @license https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

/**
 * Routes for administrative user management.  Overrides routes defined in routes://users.php.
 */
$app->group('/users', function () {
    $this->get('/u/{user_name}', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:pageInfo');
})->add('authGuard');

$app->group('/api/users', function () {
    $this->get('', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:getList');

    $this->put('/u/{user_name}', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:updateInfo');

    $this->post('', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:create');
});

$app->group('/account', function () {
    $this->get('/settings', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:pageSettings')
        ->add('authGuard');

    $this->post('/settings/profile', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:profile')
        ->add('authGuard');
});

$app->group('/modals/users', function () {
    $this->get('/create', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:getModalCreate');

    $this->get('/edit', 'UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController:getModalEdit');
});
