<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Integration;

use UserFrosting\Sprinkle\Account\Tests\withTestUser;
use UserFrosting\Sprinkle\Core\Tests\RefreshDatabase;
use UserFrosting\Sprinkle\Core\Tests\TestDatabase;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;
use UserFrosting\Sprinkle\UserProfile\Util\UserProfileHelper;
use UserFrosting\Tests\TestCase;

class UserProfileHelperTest extends TestCase
{
    use TestDatabase;
    use RefreshDatabase;
    use withTestUser;

    public function setUp(): void
    {
        parent::setUp();

        // Setup test database
        $this->setupTestDatabase();
        $this->refreshDatabase();

        // Register custom scheme location
        $this->ci->locator->registerLocation('test', __DIR__);
    }

    public function testConstructor(): UserProfileHelper
    {
        // Force no cache for now
        $this->ci->config['customProfile.cache'] = false;

        $helper = new UserProfileHelper($this->ci->locator, $this->ci->cache, $this->ci->config);
        $this->assertInstanceOf(UserProfileHelper::class, $helper);

        return $helper;
    }

    /**
     * @depends testConstructor
     */
    public function testgetFieldsSchema(UserProfileHelper $helper): void
    {
        $schema = $helper->getFieldsSchema();

        $this->assertIsArray($schema);
        $this->assertSame([
            'location' => [
                'validators' => [
                    'length' => [
                        'label'   => 'LOCATION',
                        'min'     => 1,
                        'max'     => 255,
                        'message' => 'VALIDATE.LENGTH_RANGE',
                    ],
                ],
                'form' => [
                    'type'  => 'text',
                    'label' => 'LOCATION',
                    'icon'  => 'fa-globe',
                ],
            ],
            'gender' => [
                'validators' => [
                ],
                'form' => [
                    'type'    => 'select',
                    'label'   => 'GENDER',
                    'icon'    => 'fa-transgender',
                    'options' => [
                        '1' => 'GENDER.MALE',
                        '2' => 'GENDER.FEMALE',
                        '3' => 'GENDER.NEUTRAL',
                    ],
                ],
            ],
            'active' => [
                'validators' => [
                ],
                'form' => [
                    'type'    => 'select',
                    'label'   => 'ACTIVE',
                    'options' => [
                        '0' => 'NO',
                        '1' => 'YES',
                    ],
                ],
            ],
        ], $schema);
    }

    /**
     * @depends testConstructor
     * @depends testgetFieldsSchema
     */
    public function testgetProfile(UserProfileHelper $helper): void
    {
        // Create test user. Result is the base user. Switch to our model
        $user = $this->createTestUser();
        $user = User::find($user->id);
        $this->assertInstanceOf(User::class, $user);

        $profile = $helper->getProfile($user, false);

        // Expection
        $expectation = [
            'location' => '',
            'gender'   => '',
            'active'   => '',
        ];

        $this->assertEquals(collect($expectation), $profile);
        $this->assertSame($expectation, $profile->toArray());

        // Test set
        $helper->setProfile($user, ['location' => 'bar']);
        $helper->setProfile($user, collect(['gender' => '1']));

        // Check set worked
        $user = User::find($user->id);
        $profile = $helper->getProfile($user, true);

        // Expection
        $expectation = [
            'location' => 'bar',
            'gender'   => 'GENDER.MALE',
            'active'   => '',
        ];

        $this->assertEquals(collect($expectation), $profile);
        $this->assertSame($expectation, $profile->toArray());
    }

    /**
     * @depends testgetProfile
     */
    public function testCache(): void
    {
        // Force cache
        $this->ci->config['customProfile.cache'] = true;
        $this->ci->cache->flush();

        $helper = new UserProfileHelper($this->ci->locator, $this->ci->cache, $this->ci->config);

        $this->testgetFieldsSchema($helper);
    }
}
