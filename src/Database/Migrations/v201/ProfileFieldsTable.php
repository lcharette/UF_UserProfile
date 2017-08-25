<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */
namespace UserFrosting\Sprinkle\UserProfile\Database\Migrations\v201;

use UserFrosting\System\Bakery\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Group profile fields table migration
 * @extends Migration
 */
class ProfileFieldsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public $dependencies = [];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->schema->table('profile_fields', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}