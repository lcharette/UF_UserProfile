<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Database\Migrations\v201;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\UserProfile\Database\Models\ProfileFields;
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
    public static $dependencies = [
        '\UserFrosting\Sprinkle\UserProfile\Database\Migrations\v200\ProfileFieldsTable',
    ];

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            //$table->dropForeign(['user_id']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        // Delete all group related entries
        ProfileFields::where('parent_type', 'UserFrosting\Sprinkle\UserProfile\Database\Models\Group')->delete();

        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('users');
        });
    }
}
