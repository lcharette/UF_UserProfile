<?php

/*
 * UF Custom User Profile Field Sprinkle
 *
 * @link      https://github.com/lcharette/UF_UserProfile
 * @copyright Copyright (c) 2020 Louis Charette
 * @license   https://github.com/lcharette/UF_UserProfile/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\UserProfile\Util;

use Illuminate\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Collection;
use UserFrosting\Sprinkle\UserProfile\Database\Models\User;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;
use UserFrosting\Support\Repository\Repository as Config;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * CustomProfileHelper Class.
 *
 * Helper class to fetch and controls the custom profile fields
 */
class UserProfileHelper
{
    /** @var Config */
    protected $config;

    /** @var Cache */
    protected $cache;

    /** @var ResourceLocatorInterface */
    protected $locator;

    /** @var string The schemas to load */
    protected $schema = 'userProfile';

    /** @var string The key used to cache the schema */
    protected $schemaCacheKey = 'customProfileUserSchema';

    /**
     * Constructor.
     *
     * @param ResourceLocatorInterface $locator
     * @param Cache                    $cache
     * @param Config                   $config
     */
    public function __construct(ResourceLocatorInterface $locator, Cache $cache, Config $config)
    {
        $this->locator = $locator;
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Return the value for the specified user profile.
     *
     * @param User $user
     * @param bool $transform
     *
     * @return Collection<string,string>
     */
    public function getProfile(User $user, bool $transform = false)
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
            if ($transform && $item['form']['type'] == 'select' && isset($item['form']['options'][$value])) {
                $value = $item['form']['options'][$value];
            }

            return [$key => $value];
        });

        //});
    }

    /**
     * Set one or more user profile fields from an array.
     *
     * @param mixed $data
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
     */
    public function getFieldsSchema()
    {
        if ($this->config['customProfile.cache']) {
            return $this->cache->rememberForever($this->schemaCacheKey, function () {
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
     * @param string $schemaLocation
     */
    protected function getSchemaContent(string $schemaLocation)
    {
        // Define the YAML loader
        $loader = new YamlFileLoader([]);

        // Get all the location where we can find config schemas
        $paths = array_reverse($this->locator->findResources('schema://' . $schemaLocation, true, false));

        // For every location...
        foreach ($paths as $path) {

            // Get a list of all the schemas file
            $files_with_path = glob($path . '/*.json');

            // Load every found files
            foreach ($files_with_path as $file) {

                // Load the file content
                $loader->addPath($file);
            }
        }

        return $loader->load();
    }
}
