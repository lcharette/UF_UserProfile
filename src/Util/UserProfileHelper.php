<?php
/**
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Util;

use UserFrosting\Support\Exception\FileNotFoundException;
use UserFrosting\Support\Exception\JsonException;
use Interop\Container\ContainerInterface;
use UserFrosting\Sprinkle\Core\Facades\Debug;

/**
 * CustomProfileHelper Class
 *
 * Helper class to fetch and controls the custom profile fields
 */
class UserProfileHelper
{
    protected $ci;

    protected $schema = "userProfile";
    protected $schemaCacheKey = "customProfileUserSchema";

    /**
     * __construct function.
     *
     * @access public
     * @param ContainerInterface $ci
     * @return void
     */
    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
    }

    /**
     * Return the value for the specified user profile.
     *
     * @access public
     * @param mixed $user
     * @return void
     */
    public function getProfile($user, $transform = false)
    {
        //N.B.: User cache not yet implemented in master/develop. See UF branch `feature-cache`
        //return $this->cache->rememberForever('profileFields', function() use ($user) {

            // Get the fields list
            $fields = $this->getFieldsSchema();
            $fields = collect($fields);

            // Get the user fields from the db
            $userFields = $user->profileFields->pluck('value', 'slug');

            // Map the fields from the list to the values from the db
            return $fields->mapWithKeys(function ($item, $key) use ($userFields, $transform) {

                // Get the default value
                $default = ($item['form']['default']) ?: "";

                // Get the field value.
                $value = $userFields->get($key, $default);

                // Add the pretty formated version
                if ($transform && $item['form']['type'] == "select") {
                    $value = ($item['form']['options'][$value]) ?: $value;
                }

                return [
                    $key => $value
                ];;
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
    public function setProfile($user, $data)
    {
        // Get the user fields
        $userFields = $this->getProfile($user);

        // If data is not a collection, make it so
        if (!$data instanceof \Illuminate\Database\Eloquent\Collection ||
            !$data instanceof \Illuminate\Support\Collection)
        {
            $data = collect($data);
        }

        foreach ($userFields as $slug => $value) {
            if ($data->has($slug) && $data->get($slug) != $value)
            {
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
     * Use the cache if the config is on or return directly otherwise
     *
     * @access public
     * @return void
     */
    public function getFieldsSchema()
    {
        $config = $this->ci->config;
        $cache = $this->ci->cache;

        if ($config['customProfile.cache']) {
            return $cache->rememberForever($this->schemaCacheKey, $this->getSchemaContent($this->schema));
        } else {
            return $this->getSchemaContent($this->schema);
        }
    }

    /**
     * Load the specified schemas
     * Loop trhought all the available json schema inside a type of schemas
     *
     * @access protected
     * @param string $schema
     * @return void
     */
    protected function getSchemaContent($schemaLocation)
    {
        $schemas = array();
        $locator = $this->ci->locator;

        // Get all the location where we can find config schemas
        $paths = array_reverse($locator->findResources('schema://' . $schemaLocation, true, false));

        // For every location...
        foreach ($paths as $path) {

            // Get a list of all the schemas file
            $files_with_path = glob($path . "/*.json");

            // Load every found files
            foreach ($files_with_path as $file) {

                // Load the file content
                $schema = $this->loadSchema($file);

                // Add to list
                $schemas = array_merge($schemas, $schema);
            }
        }

        return $schemas;
    }

    /**
     * loadSchema function.
     * Load the specified file content and return it as an array
     *
     * @access public
     * @param mixed $file   The full path of the schema we want
     * @return array        The schema content
     */
    protected function loadSchema($file)
    {
        $doc = file_get_contents($file);
        if ($doc === false)
            throw new FileNotFoundException("The schema '$file' could not be found.");

        $schema = json_decode($doc, true);
        if ($schema === null) {
            throw new JsonException("The schema '$file' does not contain a valid JSON document.  JSON error: " . json_last_error());
        }

        return $schema;
    }
}