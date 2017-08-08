<?php

namespace Plansys\Codegen;

use PhpParser\ParserFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

class ConfigCode
{
    private $ast = null;
    private $return = null;
    public $filename = '';

    public function __construct($filename)
    {
        if (!is_file($filename)) {
            throw new \Exception("File $filename does not exists");
        }
        if (!is_writable($filename)) {
            throw new \Exception("File $filename is not writtable");
        }
        $this->filename = realpath($filename);

        $code = file_get_contents($this->filename);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->ast = $parser->parse($code);

        $this->return = end($this->ast);
        if (get_class($this->return) != "PhpParser\Node\Stmt\Return_") {
            throw new \Exception("Config file must return something");
        }

        if (get_class($this->return->expr) != "PhpParser\Node\Expr\Array_") {
            throw new \Exception("Config file must return an array");
        }

        if (!is_array($this->return->expr->items)) {
            throw new \Exception("Config file must return an array");
        }
    }

    public function get($path)
    {
        $config = include($this->filename);

        $pathArr = explode(".", $path);
        $current = &$config;
        foreach ($pathArr as $k => $p) {
            $found = false;
            if (isset($current[$p])) {
                $found = true;
                $current = &$current[$p];
            }

            if (!$found) return null;
        }

        return @$current;
    }

    public function remove($path)
    {
        if (!is_null($this->get($path))) {
            $pathArr = explode(".", $path);
            $parent = &$this->return->expr;
            $current = &$parent->items;
            foreach ($pathArr as $k => $p) {
                $found = false;
                foreach ($current as $idx => $item) {
                    $key = $item->key->value;

                    if ($key == $p) {
                        if (count($pathArr) - 1 == $k) {
                            array_splice($current, $idx, 1);
                        } else {
                            $current = &$item->value->items;
                        }
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    return false;
                    break;
                }
            }

            $codePrinter = new CodePrinter();
            $this->ast[count($this->ast) - 1] = $this->return;
            $code = $codePrinter->prettyPrintFile($this->ast);
            file_put_contents($this->filename, $code);
            return true;
        }
    }

    public function set($path, $value, $isCode = false)
    {
        $parent = &$this->return->expr;
        $pathArr = explode(".", $path);
        $current = &$parent->items;
        foreach ($pathArr as $k => $p) {
            $found = false;

            foreach ($current as &$item) {
                $key = $item->key->value;
                if ($key == $p) {
                    $found = true;
                    $isset = false;
                    if (is_array($item->value)) {
                        if ($k == count($pathArr) - 1) {
                            $item->value = self::parseValue($value, $isCode);
                            $isset = true;
                        }
                    }

                    if (!$isset) {
                        $item->value = new Expr\Array_();
                        $current = &$item->value->items;
                    }
                    break;
                }
            }

            if (!$found) {
                if ($k != count($pathArr) - 1) {
                    $current[count($current)] = new Expr\ArrayItem(new Expr\Array_(), new Scalar\String_($p));
                    $current = &$current[count($current) - 1]->value->items;

                } else {
                    $current[count($current)] = new Expr\ArrayItem(self::parseValue($value, $isCode), new Scalar\String_($p));
                }
            }
        }

        $codePrinter = new Writer\CodePrinter();
        $this->ast[count($this->ast) - 1] = $this->return;
        $code = $codePrinter->prettyPrintFile($this->ast);
        file_put_contents($this->filename, $code);
    }

    private function parseValue($value, $isCode = false)
    {
        if (!is_string($value)) {
            ob_start();
            var_export($value);
            $value = ob_get_clean();
            $isCode = true;
        }

        if (!$isCode) {
            return new Scalar\String_($value);
        } else {
            $preCode = "<?php
                        {$value};
                    ";

            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $valcode = $parser->parse($preCode);
            return end($valcode);
        }
    }

}