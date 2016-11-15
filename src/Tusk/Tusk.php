<?php

namespace Tusk;

use \PhpParser\ParserFactory;

/**
 * Tusk entry point.
 *
 * 
 */
class Tusk
{

    /**
     * @var Configuration
     */
    private $config;

    public function __construct(Configuration $config = null)
    {
        if ($config) {
            $this->config = $config;
        } else {
            $this->config = new Configuration();
        }
    }

    /**
     * Converts a php source file into groovy.
     * 
     * @param string $path
     */
    public function run(string $path)
    {
        if (is_file($path)) {
            $this->runFile(new \Symfony\Component\Finder\SplFileInfo($path, null, null));
        } else {
            $this->runDirectory($path);
        }
    }

    private function runFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $code = $file->getContents();
        $groovySrc = $this->toGroovy($this->getStatements($code));
        if ($this->config->isConfigured()) {
            
        } else {
            echo $groovySrc;
        }
    }

    private function runDirectory(string $path)
    {
        $onlyPHP = function (\SplFileInfo $file) {
            return strpos((string) $file, ".php");
        };

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($path)->filter($onlyPHP);

        foreach ($finder as $file) {
            $this->runFile($file);
        }
    }

    public function toGroovy(array $statements)
    {
        $printer = new Printer\Groovy();
        return $printer->prettyPrint($statements);
    }

    public function getStatements(string $code): array
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $traverser = new \PhpParser\NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\Destruct());
        $traverser->addVisitor(new NodeVisitor\MagicCall());

        $stmts = $parser->parse($code);
        $stmts = $traverser->traverse($stmts);
        return $stmts;
    }

}
