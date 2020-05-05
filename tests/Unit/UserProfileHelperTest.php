<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Unit;

use Mockery;
use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\UserProfile\Util\UserProfileHelper;
use UserFrosting\Tests\TestCase;

class UserProfileHelperTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testConstructor(): void
    {
        $ci = Mockery::mock(ContainerInterface::class);
        $helper = new UserProfileHelper($ci);
        $this->assertInstanceOf(UserProfileHelper::class, $helper);
    }

    /**
     * @depends testConstructor
     */
    /*public function testgetFieldsSchema(): void
    {
        $ci = Mockery::mock(ContainerInterface::class);
        $ci->config = ['customProfile.cache' => false];
        $ci->cache = null;

        $helper = new UserProfileHelper($ci);

        $foo = $helper->getFieldsSchema();
        var_dump($foo);
    }*/

    /**
     * @depends testConstructor
     * getFieldsSchema
     */
    /*public function testgetProfile(): void
    {
        $ci = Mockery::mock(ContainerInterface::class);
        $helper = new UserProfileHelper($ci);

        $user = $this->createTestUser();

        $foo = $helper->getProfile($user);
        var_dump($foo);
    }*/
}
