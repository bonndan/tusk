<?php

namespace Tusk;

use PhpParser\ParserFactory;
use SplFileInfo as SplFileInfo2;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
    
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    public function __construct(Configuration $config = null)
    {
        if ($config) {
            $this->config = $config;
        } else {
            $this->config = new Configuration();
        }
        
        $this->logger = new \Monolog\Logger('Tusk');
        $this->logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());
    }

    /**
     * Converts a php source file into groovy.
     * 
     * @param string $path
     */
    public function run(string $path = null)
    {
        if ($path) {
            if (is_file($path)) {
                $this->runFile(new SplFileInfo($path, null, null));
            } else {
                $this->runDirectory($path);
            }
        }
        
        if (!$this->config->isConfigured())
            die("Please provide a path or a valid configuration.");
        
        $this->runDirectory($this->config->namespaceBaseDir);
    }

    private function runFile(SplFileInfo $file)
    {
        $code = $file->getContents();
        $state = new State($file->getFilename());
        $groovySrc = $this->toGroovy($this->getStatements($code), $state);
        if ($this->config->isConfigured()) {
            $target = $this->getPathForFile($groovySrc, $file);
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
                $this->logger->addInfo("Created directory " . dirname($target));
            }
            
            file_put_contents($target, (string)$groovySrc);
            $this->logger->addInfo("Written $target");
        } else {
            echo $groovySrc;
        }
    }
    
    private function getPathForFile(State $tuskFile, SplFileInfo $fileInfo) : string
    {
        $targetFile = $this->config->packageBaseDir 
                . DIRECTORY_SEPARATOR 
                . str_replace(".", DIRECTORY_SEPARATOR, $tuskFile->getPackage())
                . DIRECTORY_SEPARATOR
                . str_replace(".php", ".groovy", $fileInfo->getFilename());
        return $targetFile;
    }

    private function runDirectory(string $path)
    {
        $onlyPHP = function (SplFileInfo2 $file) {
            return strpos((string) $file, ".php");
        };

        $finder = new Finder();
        $finder->files()->in($path)->filter($onlyPHP);

        foreach ($finder as $file) {
            $this->runFile($file);
        }
    }

    public function toGroovy(array $statements, \Tusk\State $state) : State
    {
        $printer = new Printer\Groovy(['state' => $state]);
        $state->setSrc($printer->prettyPrint($statements));
        return $state;
    }

    public function getStatements(string $code, State $state): array
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        //traverser sequence is important
        $traverser = new \PhpParser\NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\Namespace_($state));
        $traverser->addVisitor(new NodeVisitor\Destruct());
        $traverser->addVisitor(new NodeVisitor\MagicCall());
        $traverser->addVisitor(new NodeVisitor\BuiltInException($state));
        return $traverser->traverse($stmts);
    }

}
