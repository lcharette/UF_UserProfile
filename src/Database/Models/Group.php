<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Database\Models;

use UserFrosting\Sprinkle\Account\Database\Models\Group as CoreGroup;
use UserFrosting\Sprinkle\Core\Database\Relations\MorphManySyncable;
use UserFrosting\Sprinkle\UserProfile\Database\Models\Traits\ProfileFieldsHelpers;

/**
 * Group Class.
 *
 * Extend the core Group Model to add the custom group profile fields
 */
class Group extends CoreGroup
{
    use ProfileFieldsHelpers;

    /**
     * Eloquent relation to the profile table.
     *
     * @return MorphManySyncable
     */
    public function profileFields()
    {
        return $this->morphMany('\UserFrosting\Sprinkle\UserProfile\Database\Models\ProfileFields', 'parent');
    }

    /**
     * Delete the profileFields values when deleting the main model.
     * {@inheritDoc}
     */
    public function delete()
    {
        $this->profileFields()->delete();
        parent::delete();
    }
}
