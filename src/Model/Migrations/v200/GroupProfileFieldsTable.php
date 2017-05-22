<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */
namespace UserFrosting\Sprinkle\UserProfile\Model\Migrations\v200;

use UserFrosting\System\Bakery\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Group profile fields table migration
 * @extends Migration
 */
class GroupProfileFieldsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public $dependencies = [
        '\UserFrosting\Sprinkle\Account\Model\Migrations\v400\GroupsTable'
    ];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('groups_profile_fields')) {
            $this->schema->create('groups_profile_fields', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('group_id')->unsigned();
                $table->string('slug', 255);
                $table->string('value', 255)->nullable();
                $table->timestamps();

                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->foreign('group_id')->references('id')->on('groups');
                $table->index('id');
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->schema->drop('groups_profile_fields');
    }
}