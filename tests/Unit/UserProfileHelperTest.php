<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Unit;

use Closure;
use Mockery;
use Illuminate\Cache\Repository as Cache;
use UserFrosting\Sprinkle\UserProfile\Database\Models\ProfileFields;
use UserFrosting\Support\Repository\Repository as Config;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;
use UserFrosting\Sprinkle\UserProfile\Util\UserProfileHelper;
use UserFrosting\Tests\TestCase;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

class UserProfileHelperTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testConstructor(): void
    {
        $locator = Mockery::mock(ResourceLocatorInterface::class);
        $cache = Mockery::mock(Cache::class);
        $config = Mockery::mock(Config::class);

        $helper = new UserProfileHelper($locator, $cache, $config);
        $this->assertInstanceOf(UserProfileHelper::class, $helper);
    }

    /**
     * @depends testConstructor
     */
    public function testgetFieldsSchema(): void
    {
        $locator = Mockery::mock(ResourceLocatorInterface::class)
            ->shouldReceive('findResources')
            ->with('schema://userProfile', true, false)
            ->andReturn([__DIR__ . '/schema/userProfile'])
            ->getMock();
        $cache = Mockery::mock(Cache::class);
        $config = Mockery::mock(Config::class)
            ->shouldReceive('offsetGet')
            ->with('customProfile.cache')
            ->andReturn(false)
            ->getMock();

        $helper = new UserProfileHelper($locator, $cache, $config);

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
    public function testgetFieldsSchemaWithCache(): void
    {
        $locator = Mockery::mock(ResourceLocatorInterface::class);
        $cache = Mockery::mock(Cache::class)
            ->shouldReceive('rememberForever')
            ->once()
            ->with('customProfileUserSchema', Closure::class)
            ->andReturn([])
            ->getMock();
        $config = Mockery::mock(Config::class)
            ->shouldReceive('offsetGet')
            ->once()
            ->with('customProfile.cache')
            ->andReturn(true)
            ->getMock();

        $helper = new UserProfileHelper($locator, $cache, $config);

        $schema = $helper->getFieldsSchema();

        $this->assertSame([], $schema);
    }

    /**
     * @depends testConstructor
     * @depends testgetFieldsSchema
     */
    public function testgetProfile(): void
    {
        // Set services
        $locator = Mockery::mock(ResourceLocatorInterface::class)
            ->shouldReceive('findResources')
            ->with('schema://userProfile', true, false)
            ->andReturn([__DIR__ . '/schema/userProfile'])
            ->getMock();
        $cache = Mockery::mock(Cache::class);
        $config = Mockery::mock(Config::class)
            ->shouldReceive('offsetGet')
            ->with('customProfile.cache')
            ->andReturn(false)
            ->getMock();

        // Get helper
        $helper = new UserProfileHelper($locator, $cache, $config);

        // Set profile raw data
        $data = collect([
            ['slug' => 'location', 'value' => 'foo'],
            ['slug' => 'gender', 'value' => '1'],
        ]);

        // Create fake user
        $user = Mockery::mock(User::class)
            ->shouldReceive('getAttribute')
            ->with('profileFields')
            ->andReturn($data)
            ->getMock();

        // Act
        $profile = $helper->getProfile($user, false);

        // Expection
        $expectation = [
            'location' => 'foo',
            'gender'   => '1',
            'active'   => '',
        ];

        // Assert
        $this->assertEquals(collect($expectation), $profile);
        $this->assertSame($expectation, $profile->toArray());

        // Act again with true
        $profile = $helper->getProfile($user, true);

        // Expection
        $expectation = [
            'location' => 'foo',
            'gender'   => 'GENDER.MALE', // Transformed here
            'active'   => '',
        ];

        // Assert
        $this->assertEquals(collect($expectation), $profile);
        $this->assertSame($expectation, $profile->toArray());
    }

    /**
     * @depends testConstructor
     * @depends testgetProfile
     * getFieldsSchema
     */
    public function testsetProfile(): void
    {
        // Set services
        $locator = Mockery::mock(ResourceLocatorInterface::class)
            ->shouldReceive('findResources')
            ->with('schema://userProfile', true, false)
            ->andReturn([__DIR__ . '/schema/userProfile'])
            ->getMock();
        $cache = Mockery::mock(Cache::class);
        $config = Mockery::mock(Config::class)
            ->shouldReceive('offsetGet')
            ->with('customProfile.cache')
            ->andReturn(false)
            ->getMock();

        // Get helper
        $helper = new UserProfileHelper($locator, $cache, $config);

        // Set profile raw data
        $data = collect([
            ['slug' => 'location', 'value' => 'foo'],
            ['slug' => 'gender', 'value' => '1'],
        ]);

        $profileFields = Mockery::mock(ProfileFields::class);
        $profileFields->shouldReceive('updateOrCreate')->once()->with(['slug' => 'location'], ['value' => 'bar'])->andReturn(null);

        // Create fake user
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->once()->with('profileFields')->andReturn($data);
        $user->shouldReceive('profileFields')->once()->andReturn($profileFields);

        // Act
        $result = $helper->setProfile($user, [
            'location' => 'bar',
        ]);

        $this->assertNull($result);
    }
}
