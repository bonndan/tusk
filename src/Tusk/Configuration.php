<?php

namespace Tusk;

/**
 * Tusk runtime configuration.
 * 
 * 
 */
class Configuration
{
    public $packageBaseDir;
    public $namespaceBaseDir;
    
    public function isConfigured() : bool
    {
        return $this->packageBaseDir && $this->namespaceBaseDir;
    }

    public static function create(array $options) : Configuration
    {
        $config = new Configuration();
        foreach ($options as $key => $value) {
            $config->$key = $value;
        }
        return $config;
    }

}
