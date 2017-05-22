<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Util;

use UserFrosting\Sprinkle\UserProfile\Util\UserProfile;

/**
 * UserProfileHelper Class
 *
 * Helper class to fetch and controls the custom profile fields
 */
class GroupProfile extends UserProfile
{
    protected $schema = "groupProfile";
}