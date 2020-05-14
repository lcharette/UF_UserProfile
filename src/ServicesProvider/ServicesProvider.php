<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\ServicesProvider;

use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\UserProfile\Database\Models\Group;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;

/**
 * Registers services for the UserProfile sprinkle.
 */
class ServicesProvider
{
    /**
     * Register UserProfile services.
     *
     * @param ContainerInterface $container
     */
    public function register($container)
    {
        /*
         * Extend the 'classMapper' service to register model classes.
         *
         * Mappings added: user & group
         */
        $container->extend('classMapper', function ($classMapper, $c) {
            $classMapper->setClassMapping('user', User::class);
            $classMapper->setClassMapping('group', Group::class);

            return $classMapper;
        });
    }
}
