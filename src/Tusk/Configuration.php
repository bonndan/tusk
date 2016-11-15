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
}
