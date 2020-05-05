<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Database\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use UserFrosting\Sprinkle\Core\Database\Models\Model;

/**
 * ProfileFields Class.
 *
 * Provide the polymorphic table used to store group and user custom fields values
 *
 * @property string $slug
 * @property string $value
 */
class ProfileFields extends Model
{
    public $timestamps = true;

    /**
     * @var string The name of the table for the current model.
     */
    protected $table = 'profile_fields';

    /**
     * @var string[] List of fillables columns
     */
    protected $fillable = [
        'slug',
        'value',
    ];

    /**
     * Get all of the owning models.
     *
     * @return MorphTo
     */
    public function parent()
    {
        return $this->morphTo();
    }
}
