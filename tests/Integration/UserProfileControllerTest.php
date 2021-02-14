<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Integration;

use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\Admin\Controller\UserController;
use UserFrosting\Sprinkle\Admin\Tests\Integration\Controller\UserControllerTest;
use UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;

/**
 * This test requires UF 4.4.3 to work properly
 */
class UserProfileControllerTest extends UserControllerTest
{
    protected function getController(): UserController
    {
        return new UserProfileController($this->ci);
    }

    /**
     * @depends testControllerConstructor
     * @return UserController
     */
    public function testControllerConstructorWithUser()
    {
        parent::testControllerConstructorWithUser();

        // Register custom scheme location
        $this->ci->locator->registerLocation('test', __DIR__);

        return $this->getController();
    }

    /**
     * @depends testControllerConstructorWithUser
     * @param UserController $controller
     */
    public function testUpdateInfoWithCustomData(UserController $controller)
    {        
        // Create a user
        $user = $this->createTestUser();

        // Also create a group
        $fm = $this->ci->factory;
        $group = $fm->create(Group::class);

        // Set post data
        $data = [
            'location'   => 'here', //<-- N.B.
            'first_name' => 'bar',
            'group_id'   => $group->id,
        ];
        $request = $this->getRequest()->withParsedBody($data);

        // Get controller stuff
        $result = $controller->updateInfo($request, $this->getResponse(), ['user_name' => $user->user_name]);
        $this->assertSame($result->getStatusCode(), 200);
        $this->assertJson((string) $result->getBody());
        $this->assertSame('[]', (string) $result->getBody());

        // Make sure user was update
        $editedUser = User::where('user_name', $user->user_name)->first();
        $this->assertSame('bar', $editedUser->first_name);
        $this->assertNotSame($user->first_name, $editedUser->first_name);
        $this->assertSame($user->last_name, $editedUser->last_name);
        $this->assertSame($group->id, $editedUser->group->id);

        // Test custom fields
        $this->assertSame('here', $editedUser->getProfileFieldsForSlug('location'));

        // Test message
        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;
        $messages = $ms->getAndClearMessages();
        $this->assertSame('success', end($messages)['type']);
    }
}
