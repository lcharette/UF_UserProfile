<?php
namespace UserFrosting\Sprinkle\UserProfile\Model;

use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\UserProfile\Util\UserProfile;

class CustomUserProfile extends User {

    /**
     * Get the owler associated with this user.
     */
    public function profileFields()
    {
        return $this->hasMany('\UserFrosting\Sprinkle\UserProfile\Model\UserProfile', 'user_id');
    }

    /**
     * delete function
     *
     * @access public
     * @param bool $hardDelete (default: false)
     * @return void
     */
    public function delete($hardDelete = false)
    {
        if ($hardDelete)
        {
            $this->profileFields()->delete();
        }

        parent::delete($hardDelete);
    }

    /**
     * Return a collection of user profile fields
     *
     * @access public
     * @return void
     */
    public function getUserFields()
    {
        //N.B.: User cache not yet implemented in master/develop. See UF branch `feature-cache`
        //return $this->cache->rememberForever('profileFields', function() use ($user) {

            // Get the fields list
            $UserProfile = new UserProfile(static::$ci);
            $fields = $UserProfile->getFieldsSchema();
            $fields = collect($fields);

            // Get the user fields from the db
            $userFields = $this->profileFields()->get();
            $userFields = $userFields->pluck('value', 'slug');

            // Map the fields from the list to the values from the db
            return $fields->mapWithKeys(function ($item, $key) use ($userFields) {
                return [$key => $userFields->get($key, "")]; //!TODO : Fields default
            });

        //});
    }

    /**
     * Set one or more user profile fields from an array
     *
     * @access public
     * @param mixed $data
     * @return void
     */
    public function setUserFields($data)
    {
        // Get the user fields
        $userFields = $this->getUserFields();

        // If data is not a collection, make it so
        if (!$data instanceof \Illuminate\Database\Eloquent\Collection || !$data instanceof \Illuminate\Support\Collection)
        {
            $data = collect($data);
        }

        foreach ($userFields as $slug => $value) {
            if ($data->has($slug) && $data->get($slug) != $value)
            {
                $this->profileFields()->updateOrCreate(
                    ['slug' => $slug],
                    ['value' => $data->get($slug)]
                );
            }
        }

        // Flush cache
        //N.B.: User cache not yet implemented in master/develop. See UF branch `feature-cache`
        //$this->cache->forget('profileFields');
    }

    /**
     * Set one user profile fields by slug
     *
     * @access public
     * @param mixed $user
     * @param mixed $slug
     * @param mixed $value
     * @return void
     */
    public function setUserField($slug, $value)
    {
        $data = collect([$slug => $value]);
        $this->setUserFields($data);
    }
}