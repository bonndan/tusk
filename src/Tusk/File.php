<?php

namespace Tusk;

/**
 * An envelope around the src providing some meta information.
 * 
 *
 */
class File
{
    private $src;
    private $package;
    private $filename;

    public function __construct(string $src, string $package = null, string $filename = null)
    {
        $this->src = $src;
        $this->package = $package;
        $this->filename = $filename;
    }
    
    public function __toString()
    {
        return $this->src;
    }
    
    public function getPackage() : string
    {
        return $this->package;
    }
}
