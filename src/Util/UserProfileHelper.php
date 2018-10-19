<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2017 Louis Charette
 * @license https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Util;

use Interop\Container\ContainerInterface;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;

/**
 * CustomProfileHelper Class.
 *
 * Helper class to fetch and controls the custom profile fields
 */
class UserProfileHelper
{
    protected $ci;

    protected $schema = 'userProfile';
    protected $schemaCacheKey = 'customProfileUserSchema';

    /**
     * __construct function.
     *
     * @param ContainerInterface $ci
     *
     * @return void
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
    }

    /**
     * Return the value for the specified user profile.
     *
     * @param mixed $user
     *
     * @return void
     */
    public function getProfile($user, $transform = false)
    {
        //N.B.: User cache not yet implemented in master/develop. See UF branch `feature-cache`
        //return $user->cache->rememberForever('profileFields', function() use ($user) {

        // Get the fields list
        $fields = $this->getFieldsSchema();
        $fields = collect($fields);

        // Get the user fields from the db
        $userFields = $user->profileFields->pluck('value', 'slug');

        // Map the fields from the list to the values from the db
        return $fields->mapWithKeys(function ($item, $key) use ($userFields, $transform) {

                // Get the default value
            $default = isset($item['form']['default']) ? $item['form']['default'] : '';

            // Get the field value.
            $value = $userFields->get($key, $default);

            // Add the pretty formated version
            if ($transform && $item['form']['type'] == 'select') {
                $value = ($item['form']['options'][$value]) ?: $value;
            }

            return [
                    $key => $value,
                ];
        });

        //});
    }

    /**
     * Set one or more user profile fields from an array.
     *
     * @param mixed $data
     *
     * @return void
     */
    public function setProfile($user, $data)
    {
        // Get the user fields
        $userFields = $this->getProfile($user);

        // If data is not a collection, make it so
        if (!$data instanceof \Illuminate\Database\Eloquent\Collection ||
            !$data instanceof \Illuminate\Support\Collection) {
            $data = collect($data);
        }

        foreach ($userFields as $slug => $value) {
            if ($data->has($slug) && $data->get($slug) != $value) {
                $user->profileFields()->updateOrCreate(
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
     * Return the json schema for the GROUP custom profile fields.
     * Use the cache if the config is on or return directly otherwise.
     *
     * @return void
     */
    public function getFieldsSchema()
    {
        $config = $this->ci->config;
        $cache = $this->ci->cache;

        if ($config['customProfile.cache']) {
            return $cache->rememberForever($this->schemaCacheKey, function () {
                return $this->getSchemaContent($this->schema);
            });
        } else {
            return $this->getSchemaContent($this->schema);
        }
    }

    /**
     * Load the specified schemas
     * Loop trhought all the available json schema inside a type of schemas.
     *
     * @param string $schema
     *
     * @return void
     */
    protected function getSchemaContent($schemaLocation)
    {
        $schemas = [];
        $locator = $this->ci->locator;

        // Define the YAML loader
        $loader = new YamlFileLoader([]);

        // Get all the location where we can find config schemas
        $paths = array_reverse($locator->findResources('schema://'.$schemaLocation, true, false));

        // For every location...
        foreach ($paths as $path) {

            // Get a list of all the schemas file
            $files_with_path = glob($path.'/*.json');

            // Load every found files
            foreach ($files_with_path as $file) {

                // Load the file content
                $loader->addPath($file);
            }
        }

        return $loader->load();
    }
}
