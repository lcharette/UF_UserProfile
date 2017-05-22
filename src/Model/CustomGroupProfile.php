<?php
namespace UserFrosting\Sprinkle\UserProfile\Model;

use UserFrosting\Sprinkle\Account\Model\Group;
use UserFrosting\Sprinkle\UserProfile\Util\GroupProfile;

class CustomGroupProfile extends Group {

    /**
     * Get the owler associated with this group.
     */
    public function profileFields()
    {
        return $this->hasMany('\UserFrosting\Sprinkle\UserProfile\Model\GroupProfile', 'group_id');
    }

    /**
     * delete function
     *
     * @access public
     * @param bool $hardDelete (default: false)
     * @return void
     */
    public function delete()
    {
        $this->profileFields()->delete();

        parent::delete();
    }

    /**
     * Return a collection of group profile fields
     *
     * @access public
     * @return void
     */
    public function getGroupFields()
    {
        //N.B.: Group cache not yet implemented in master/develop. See UF branch `feature-cache`
        //return $this->cache->rememberForever('profileFields', function() use ($group) {

            // Get the fields list
            $GroupProfile = new GroupProfile(static::$ci);
            $fields = $GroupProfile->getFieldsSchema();
            $fields = collect($fields);

            // Get the group fields from the db
            $groupFields = $this->profileFields()->get();
            $groupFields = $groupFields->pluck('value', 'slug');

            // Map the fields from the list to the values from the db
            return $fields->mapWithKeys(function ($item, $key) use ($groupFields) {
                return [$key => $groupFields->get($key, "")]; //!TODO : Fields default
            });

        //});
    }

    /**
     * Set one or more group profile fields from an array
     *
     * @access public
     * @param mixed $data
     * @return void
     */
    public function setGroupFields($data)
    {
        // Get the group fields
        $groupFields = $this->getGroupFields();

        // If data is not a collection, make it so
        if (!$data instanceof \Illuminate\Database\Eloquent\Collection || !$data instanceof \Illuminate\Support\Collection)
        {
            $data = collect($data);
        }

        foreach ($groupFields as $slug => $value) {
            if ($data->has($slug) && $data->get($slug) != $value)
            {
                $this->profileFields()->updateOrCreate(
                    ['slug' => $slug],
                    ['value' => $data->get($slug)]
                );
            }
        }

        // Flush cache
        //N.B.: Group cache not yet implemented in master/develop. See UF branch `feature-cache`
        //$this->cache->forget('profileFields');
    }

    /**
     * Set one group profile fields by slug
     *
     * @access public
     * @param mixed $group
     * @param mixed $slug
     * @param mixed $value
     * @return void
     */
    public function setGroupField($slug, $value)
    {
        $data = collect([$slug => $value]);
        $this->setGroupFields($data);
    }
}