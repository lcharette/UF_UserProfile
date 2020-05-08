<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Database\Migrations\v200;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\GroupsTable;
use UserFrosting\Sprinkle\Core\Database\Migration;
use UserFrosting\Sprinkle\UserProfile\Database\Migrations\v100\ProfileFieldsTable as V100ProfileFieldsTable;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;

/**
 * Group profile fields table migration.
 */
class ProfileFieldsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static $dependencies = [
        GroupsTable::class,
        V100ProfileFieldsTable::class,
    ];

    /**
     * {@inheritdoc}
     *  N.B.: On two separate line, otherwise it doesn't work with the test env.
     */
    public function up()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            // Default is used for compatibility with old version (exisitng user entry)
            $table->string('parent_type')->after('user_id')->default(User::class);
        });

        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->renameColumn('user_id', 'parent_id');
        });
    }

    /**
     * {@inheritdoc}
     * N.B.: SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.
     */
    public function down()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->renameColumn('parent_id', 'user_id');
        });

        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->dropColumn(['parent_type']);
        });
    }
}
