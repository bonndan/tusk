<?php

namespace Tusk;

/**
 * A state for each file.
 * 
 *
 */
class State
{
    private $src;
    private $package = '';
    private $filename;
    
    /**
     * all used classes etc (aka imports)
     * @var string[]
     */
    private $used = [];

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }
    
    public function setSrc(string $src)
    {
        $this->src = $src;
    }
    
    public function __toString()
    {
        return $this->src;
    }
    
    public function getPackage() : string
    {
        return $this->package;
    }
    
    public function getFilename()
    {
        return $this->filename;
    }

    public function setNamespace(string $namespace)
    {
        $this->package = Printer\Groovy::asPackage($namespace);
    }

    /**
     * Adds a used class/etc.
     * 
     * @param string $used
     */
    public function addUse(string $used)
    {
        $this->uses[$used] = $used;
    }
    
    public function isUsed($used) : bool
    {
        return array_key_exists($used, $this->used);
    }

}
