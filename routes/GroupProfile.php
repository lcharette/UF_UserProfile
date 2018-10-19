<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2017 Louis Charette
 * @license https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

/**
 * Routes for administrative group management.
 */
$app->group('/groups', function () {
    $this->get('', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:pageList')
        ->setName('uri_groups');

    $this->get('/g/{slug}', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:pageInfo');
})->add('authGuard');

$app->group('/api/groups', function () {
    $this->delete('/g/{slug}', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:delete');

    $this->get('', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getList');

    $this->get('/g/{slug}', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getInfo');

    $this->get('/g/{slug}/users', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getUsers');

    $this->post('', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:create');

    $this->put('/g/{slug}', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:updateInfo');
})->add('authGuard');

$app->group('/modals/groups', function () {
    $this->get('/confirm-delete', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getModalConfirmDelete');

    $this->get('/create', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getModalCreate');

    $this->get('/edit', 'UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController:getModalEdit');
})->add('authGuard');
