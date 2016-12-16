<?php

namespace Tusk;

/**
 * Tusk runtime configuration.
 * 
 * 
 */
class Configuration
{
    /**
     * source directory
     * @var string
     */
    public $source;
    
    /**
     * target directory
     * @var string
     */
    public $target;
    
    public $targetSrcDir = 'src/main/groovy';
    
    public $targetResourcesDir = 'src/main/resources';
    
    /**
     * @var string[] source dir => target namespace
     */
    public $namespaces = [];
    
    /**
     * source dir => target dir under resources
     * @var string[]
     */
    public $resources = [];
    
    /**
     * source dir => target dir under project
     * @var string[]
     */
    public $other = [];
    
    public function isConfigured() : bool
    {
        return $this->source && $this->target;
    }

    /**
     * Factory method, accepts an assoc array with keys matching this fields.
     * 
     * @param array $options
     * @return \Tusk\Configuration
     */
    public static function create(array $options) : Configuration
    {
        $config = new Configuration();
        foreach ($options as $key => $value) {
            if (!property_exists(Configuration::class, $key))
                throw new \InvalidArgumentException("Unknown configuration property $key");
            
            $config->$key = $value;
        }
        return $config;
    }

}
