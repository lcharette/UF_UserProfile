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

/**
 * UserProfileHelper Class
 *
 * Helper class to fetch and controls the custom profile fields
 */
class UserProfile
{
    protected $ci;

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
     * getFieldsSchema function.
     * Return the json schema for the user custom profile fields
     *
     * @access public
     * @return void
     */
    public function getFieldsSchema()
    {
        return $this->ci->cache->rememberForever('profileFieldsSchemas', function () {

            $schemas = array();

            // Get all the location where we can find config schemas
            $paths = array_reverse($this->ci->locator->findResources('schema://profileFields', true, false));

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
        });
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