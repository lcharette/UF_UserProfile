<?php
namespace UserFrosting\Sprinkle\UserProfile\Model;

use UserFrosting\Sprinkle\Core\Model\UFModel;

class UserProfile extends UFModel {

    public $timestamps = true;

    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "profile_fields";

    protected $fillable = [
        "user_id",
        "slug",
        "value"
    ];

    /**
     * Get the user associated with this owler.
     */
    public function user()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = static::$ci->classMapper;

        return $this->belongsTo($classMapper->getClassMapping('user'), 'user_id');
    }
}