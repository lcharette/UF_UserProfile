<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Util;

/**
 * CustomProfileHelper Class.
 *
 * Helper class to fetch and controls the custom profile fields
 */
class GroupProfileHelper extends UserProfileHelper
{
    protected $schema = 'groupProfile';
    protected $schemaCacheKey = 'customProfileGroupSchema';
}
