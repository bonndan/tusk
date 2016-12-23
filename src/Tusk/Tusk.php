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

        $this->runProject();
    }

    private function runFile(SplFileInfo $file, string $package = null)
    {
        $code = $file->getContents();
        $state = new State($file->getFilename());
        $state->setPackage($package);
        $this->logger->addInfo("Reading $file");
        $groovySrc = $this->toGroovy($this->getStatements($code, $state), $state);
        if ($this->config->isConfigured()) {
            $target = $this->getPathForFile($groovySrc, $file);
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
                $this->logger->addInfo("Created directory " . dirname($target));
            }

            file_put_contents($target, (string) $groovySrc);
            $this->logger->addInfo("Written $target");
        } else {
            echo $groovySrc;
        }
    }

    /**
     * Removes any artifical parts like ".class.php" and adds .groovy suffix.
     * 
     * @param \Tusk\State $tuskFile
     * @param SplFileInfo $fileInfo
     * @return string
     */
    private function getPathForFile(State $tuskFile, SplFileInfo $fileInfo): string
    {
        $cleanFileName = $this->cleanFileName($fileInfo->getFilename());
        $targetFile = $this->config->target . DIRECTORY_SEPARATOR
            . $this->config->targetSrcDir . DIRECTORY_SEPARATOR
            . str_replace(".", DIRECTORY_SEPARATOR, $tuskFile->getPackage())
            . DIRECTORY_SEPARATOR
            . $cleanFileName . ".groovy";
        return $targetFile;
    }

    private function cleanFileName(string $filename) : string
    {
        if (strpos($filename, ".") === false)
            return $filename;
        
        $remove = ['inc', 'class'];
        $parts = explode('.', $filename);
        array_pop($parts);
        foreach ($remove as $r) {
            if (($key = array_search($r, $parts)) !== false) {
                unset($parts[$key]);
            }
        }
        
        return implode('', $parts);
    }

    private function runDirectory(string $path, string $namespace = null)
    {
        $onlyPHP = function (SplFileInfo2 $file) {
            return strpos((string) $file, ".php");
        };

        $finder = new Finder();
        $finder->files()->in($path)->filter($onlyPHP);

        foreach ($finder as $file) {
            $this->runFile($file, $namespace);
        }
    }

    private function runProject()
    {
        foreach ($this->config->namespaces as $dir => $ns) {
            $this->runDirectory($this->config->source . DIRECTORY_SEPARATOR . $dir, $ns);
        }

        foreach ($this->config->resources as $source => $target) {
            $dest = $this->config->target . DIRECTORY_SEPARATOR . $this->config->targetResourcesDir . DIRECTORY_SEPARATOR . $target;
            exec("mkdir -p $dest");
            exec("cp -r " . $this->config->source . DIRECTORY_SEPARATOR . $source . " " . dirname($dest));
        }

        //write build file
        if (!empty($this->config->buildFile)) {
            file_put_contents(
                $this->config->target . DIRECTORY_SEPARATOR . "build.gradle",
                $this->config->buildFile
            );
        }
    }

    public function toGroovy(array $statements, \Tusk\State $state): State
    {
        $printer = new Printer\Groovy(['state' => $state, 'logger' => $this->logger]);
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
        $traverser->addVisitor(new NodeVisitor\TreeRelation($state));
        $traverser->addVisitor(new NodeVisitor\Destruct());
        $traverser->addVisitor(new NodeVisitor\MagicCall());
        $traverser->addVisitor(new NodeVisitor\BuiltInException($state));
        $traverser->addVisitor(new NodeVisitor\PublicTraitMethods());
        $traverser->addVisitor(new NodeVisitor\ReservedWords());
        $traverser->addVisitor(new NodeVisitor\VariableDefinition());
        $traverser->addVisitor(new NodeVisitor\NestedLoop());
        $traverser->addVisitor(new NodeVisitor\GlobalsExchanger());
        $traverser->addVisitor(new NodeVisitor\ServerVars());
        $traverser->addVisitor(new NodeVisitor\Imports($state, $this->config));
        $traverser->addVisitor(new NodeVisitor\InterfaceDefaultValues());
        return $traverser->traverse($stmts);
    }

}
