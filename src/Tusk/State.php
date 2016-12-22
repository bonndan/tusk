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
    
    /**
     * Explicit imports
     * @var string[] 
     */
    private $imports = [];

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
    
    public function setPackage(string $package)
    {
        $this->package = $package;
    }
    
    public function setFileName(string $name)
    {
        $this->filename = $name;
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
        $this->used[$used] = $used;
    }
    
    public function isUsed($used) : bool
    {       
        return array_key_exists($used, $this->used);
    }

    /**
     * Returns all explicit imports.
     * 
     * @return string[]
     */
    public function getImports() : array
    {
        return $this->imports;
    }

    /**
     * Add a class etc. to import.
     * 
     * @param string $import
     */
    public function addImport(string $import)
    {
        $this->imports[$import] = $import;
    }
}
