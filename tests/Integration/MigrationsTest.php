<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Tests\Integration;

use UserFrosting\Sprinkle\Core\Database\Migrator\Migrator;
use UserFrosting\Sprinkle\Core\Tests\RefreshDatabase;
use UserFrosting\Sprinkle\Core\Tests\TestDatabase;
use UserFrosting\Tests\TestCase;

class MigrationsTest extends TestCase
{
    use TestDatabase;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Setup test database
        $this->setupTestDatabase();
        $this->refreshDatabase();
    }

    public function testMigration(): void
    {
        /** @var \Illuminate\Database\Capsule\Manager */
        $db = $this->ci->db;
        $schema = $db->schema();

        $expecation = [
            'id',
            'slug',
            'value',
            'created_at',
            'updated_at',
            'parent_type',
            'parent_id',
        ];
        $result = $schema->getColumnListing('profile_fields');

        $this->assertEquals(sort($expecation), sort($result));

        /** @var Migrator */
        $migrator = $this->ci->migrator;
        $migrator->rollback();

        $this->assertEquals([], $schema->getColumnListing('profile_fields'));
    }
}
