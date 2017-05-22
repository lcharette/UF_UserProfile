<?php
namespace UserFrosting\Sprinkle\UserProfile\Model;

use UserFrosting\Sprinkle\Core\Model\UFModel;

class GroupProfile extends UFModel {

    public $timestamps = true;

    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "groups_profile_fields";

    protected $fillable = [
        "group_id",
        "slug",
        "value"
    ];

    /**
     * Get the user associated with this owler.
     */
    public function group()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = static::$ci->classMapper;

        return $this->belongsTo($classMapper->getClassMapping('group'), 'group_id');
    }
}