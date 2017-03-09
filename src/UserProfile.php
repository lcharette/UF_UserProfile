<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */
namespace UserFrosting\Sprinkle\UserProfile;

use UserFrosting\Sprinkle\UserProfile\ServicesProvider\ServicesProvider;
use UserFrosting\Sprinkle\Core\Initialize\Sprinkle;

/**
 * UserProfile class.
 *
 * Bootstrapper class for the 'UserProfile' sprinkle.
 * @extends Sprinkle
 */
class UserProfile extends Sprinkle
{
    /**
     * Register services.
     */
    public function init()
    {
        $serviceProvider = new ServicesProvider();
        $serviceProvider->register($this->ci);
    }
}