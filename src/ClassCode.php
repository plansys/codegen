<?php

namespace Plansys\Codegen;

use PhpParser\ParserFactory;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;

class ClassCode
{
    use Parser\ClassParser;
    use Writer\ClassWriter;

    public $filename = '';
    public $name = '';
    public $extends = '';
    public $namespace = '';
    public $implements = [];
    public $uses = [];
    public $traits = [];
    public $methods = [];
    public $properties = [];

    public function __construct($filename)
    {
        if (!is_file($filename)) {
            $class = str_replace(".php", "", basename($filename));
            file_put_contents($filename, " <?php \n class {$class} {} ");
        }
        if (!is_writable($filename)) {
            throw new \Exception("File $filename is not writtable");
        }

        $this->filename = realpath($filename);

        $code = file_get_contents($this->filename);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->ast = $parser->parse($code);
        $this->parseRoot($this->ast);
    }

    public static function getName($name)
    {
        if (strpos($name, "\\") === 0) {
            return new FullyQualified(trim($name, '\\'));
        } else {
            return new Name($name);
        }
    }

}