<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Integration;

use UserFrosting\Sprinkle\Admin\Controller\GroupController;
use UserFrosting\Sprinkle\Admin\Tests\Integration\Controller\GroupControllerTest;
use UserFrosting\Sprinkle\UserProfile\Controller\GroupProfileController;
use UserFrosting\Sprinkle\UserProfile\Database\Models\Group;

/**
 * This test requires UF 4.4.3 to work properly
 */
class GroupProfileControllerTest extends GroupControllerTest
{
    public function testControllerConstructor()
    {
        $controller = $this->getController();
        $this->assertInstanceOf(GroupController::class, $controller);
    }

    /**
     * @return GroupController
     */
    public function testControllerConstructorWithUser()
    {
        parent::testControllerConstructorWithUser();

        $controller = $this->getController();
        $this->assertInstanceOf(GroupController::class, $controller);

        // Register custom scheme location
        $this->ci->locator->registerLocation('test', __DIR__);

        return $controller;
    }

    /**
     * @depends testControllerConstructorWithUser
     * @param GroupController $controller
     */
    public function testUpdateInfo(GroupController $controller)
    {
        // Create a group
        $fm = $this->ci->factory;
        $group = $fm->create('UserFrosting\Sprinkle\Account\Database\Models\Group', [
            'name' => 'barbar',
            'slug' => 'foofoo',
        ]);

        // Set post data
        $data = [
            'location' => 'here', //<-- N.B.
            'name' => 'barbarbar',
            'slug' => 'foofoo',
            'icon' => 'icon',
        ];
        $request = $this->getRequest()->withParsedBody($data);

        // Get controller stuff
        $result = $controller->updateInfo($request, $this->getResponse(), ['slug' => $group->slug]);
        $this->assertSame($result->getStatusCode(), 200);
        $this->assertJson((string) $result->getBody());
        $this->assertSame('[]', (string) $result->getBody());

        // Make sure group was update
        $editedGroup = Group::where('slug', 'foofoo')->first();
        $this->assertSame('barbarbar', $editedGroup->name);
        $this->assertNotSame($group->name, $editedGroup->name);
        $this->assertSame($group->description, $editedGroup->description);

        // Test custom fields
        $this->assertSame('here', $editedGroup->getProfileFieldsForSlug('location'));

        // Test message
        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;
        $messages = $ms->getAndClearMessages();
        $this->assertSame('success', end($messages)['type']);
    }

    /**
     * @return GroupController
     */
    protected function getController(): GroupController
    {
        return new GroupProfileController($this->ci);
    }
}
