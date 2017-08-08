<?php

namespace Plansys\Codegen\Parser;

use PhpParser\Node\Scalar;
use PhpParser\ParserFactory;

trait ClassParser
{
    private $ast = null;
    private $astlink = [
        'namespace' => '',
        'class' => null,
    ];

    private function parseRoot($ast)
    {
        $found = false;
        $this->uses = [];
        foreach ($ast as $k => &$node) {
            if (get_class($node) == 'PhpParser\Node\Stmt\Namespace_') {
                $found = 'namespace';
                $this->parseNamespace($node);
                break;
            } else if (get_class($node) == 'PhpParser\Node\Stmt\Class_') {
                $found = 'class';
                $this->parseClass($node);
                break;
            } else if (get_class($node) == 'PhpParser\Node\Stmt\Use_') {
                $found = 'use';
                $this->parseUse($node);
            }
        }
        if (!$found) {
            throw new \Exception("File {$this->filename} must contains PHP Class or PHP Namespace");
        }
    }

    private function parseNamespace(&$node)
    {

        $prefix = '';
        if (get_class($node->name) == 'PhpParser\Node\Name\FullyQualified') {
            $prefix = '\\';
        }

        $this->namespace = $prefix . implode('\\', $node->name->parts);
        $this->astlink['namespace'] = &$node;

        foreach ($node->stmts as $k => $n) {
            switch (get_class($n)) {
                case  'PhpParser\Node\Stmt\Class_':
                    $this->parseClass($n);
                    break;
                case 'PhpParser\Node\Stmt\Use_':
                    $this->parseUse($n);
                    break;
            }
        }
    }

    private function parseUse($node)
    {

        $prefix = '';
        if (get_class($node->uses[0]->name) == 'PhpParser\Node\Name\FullyQualified') {
            $prefix = '\\';
        }

        $this->uses[] = [
            'alias' => $node->uses[0]->alias,
            'name' => $prefix . implode('\\', $node->uses[0]->name->parts)
        ];
    }

    private function parseClass($node)
    {
        $this->extends = $node->extends;
        $this->className = $node->name;
        $this->implements = $node->implements;
        $this->astlink['class'] = $node;

        $this->parseTraits($node);
        $this->parseProperties($node);
        $this->parseMethods($node);
    }

    private function parseProperties($class)
    {
        $this->properties = [];
        foreach ($class->stmts as $s) {
            if (get_class($s) == "PhpParser\Node\Stmt\Property") {

                $access = 'public';
                if ($s->isProtected()) $access = 'protected';
                else if ($s->isPrivate()) $access = 'private';

                $props = [
                    'access' => $access,
                    'static' => $s->isStatic()
                ];

                foreach ($s->props as $p) {
                    $default = null;
                    if (!is_null($p->default)) {
                        $default = self::printCode($p->default, false);

                        if ($default != '') {
                            eval('$default = ' . $default);
                        }
                    }

                    $this->properties[$p->name] = array_merge($props, [
                        'value' => $default
                    ]);
                }
            }
        }
    }

    private function parseMethods($class)
    {
        $this->methods = [];
        foreach ($class->stmts as $s) {
            if (get_class($s) == 'PhpParser\Node\Stmt\ClassMethod') {
                $access = 'public';
                if ($s->isProtected()) $access = 'protected';
                else if ($s->isPrivate()) $access = 'private';

                $this->methods[$s->name] = [
                    'access' => $access,
                    'static' => $s->isStatic(),
                    'abstract' => $s->isAbstract(),
                    'final' => $s->isFinal(),
                    'byRef' => $s->byRef,
                    'returnType' => $s->returnType,
                    'params' => $s->params,
                    'code' => self::printCode($s->stmts, false)
                ];
            }
        }
    }

    private function parseTraits($class)
    {
        $this->traits = [];
        foreach ($class->stmts as $s) {
            if (get_class($s) == 'PhpParser\Node\Stmt\TraitUse') {
                foreach ($s->traits as $t) {
                    $prefix = '';
                    if (get_class($t) == 'PhpParser\Node\Name\FullyQualified') {
                        $prefix = '\\';
                    }

                    $this->traits[] = [
                        'name' => $prefix . implode('\\', $t->parts)
                    ];
                }
            }
        }
    }

    public static function printCode($ast = null, $phpTag = true)
    {
        $codePrinter = new \Plansys\Codegen\Writer\CodePrinter();

        if (!is_array($ast)) {
            $ast = [$ast];
        }

        if (!$phpTag) {
            $code = $codePrinter->prettyPrintFile($ast);
            $code = str_replace('<?php

', '', $code);

            return $code;
        } else {
            return $codePrinter->prettyPrintFile($ast);
        }
    }

    public static function parseValue($value, $isCode = false)
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

    public static function parseCode($code)
    {
        if (!is_string($code)) {
            ob_start();
            var_export($code);
            $code = ob_get_clean();
        }

        $preCode = "<?php
                        {$code};
                    ";

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $valcode = $parser->parse($preCode);
        return $valcode;
    }
}