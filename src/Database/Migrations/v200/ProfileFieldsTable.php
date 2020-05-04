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
use UserFrosting\System\Bakery\Migration;

/**
 * Group profile fields table migration.
 *
 * @extends Migration
 */
class ProfileFieldsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public $dependencies = [
        '\UserFrosting\Sprinkle\Account\Database\Migrations\v400\GroupsTable',
        '\UserFrosting\Sprinkle\UserProfile\Database\Migrations\v100\ProfileFieldsTable',
    ];

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            // Default is used for compatibility with old version (exisitng user entry)
            $table->string('parent_type')->after('user_id')->default('UserFrosting\\\Sprinkle\\\UserProfile\\\Models\\\User');
            $table->renameColumn('user_id', 'parent_id');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->renameColumn('parent_id', 'user_id');
            $table->dropColumn(['parent_type']);
        });
    }
}
