<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Database\Models\Traits;

trait ProfileFieldsHelpers
{
    /**
     * Custom scope to help find Group based on a slug value.
     */
    public function scopeForProfileFieldsValue($query, $slug, $value)
    {
        return $query->whereHas('profileFields', function ($query) use ($slug, $value) {
            $query->where([
                'slug'  => $slug,
                'value' => $value,
            ]);
        });
    }

    /**
     * Custom method to get a profile field value from the slug.
     */
    public function getProfileFieldsForSlug($slug)
    {
        return $this->profileFields->where('slug', $slug)->first()->value;
    }
}
