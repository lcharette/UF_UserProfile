<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Integration;

use UserFrosting\Sprinkle\Admin\Controller\UserController;
use UserFrosting\Sprinkle\Admin\Tests\Integration\Controller\UserControllerTest;
use UserFrosting\Sprinkle\UserProfile\Controller\UserProfileController;

/**
 * This test requires UF 4.4.3 to work properly
 */
class UserProfileControllerTest extends UserControllerTest
{
    protected function getController(): UserController
    {
        return new UserProfileController($this->ci);
    }
}
