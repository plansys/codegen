<?php

namespace Plansys\Codegen\Writer\ClassPreparer;

use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\ClassMethod;

trait PrepareClass
{
    private function prepareClass()
    {
        if (isset($this->astlink['namespace'])) {
            $stmts = &$this->astlink['namespace']->stmts;
        } else {
            $stmts = &$this->ast;
        }

        $class = null;
        foreach ($stmts as $stmt) {
            if (get_class($stmt) == 'PhpParser\Node\Stmt\Class_') {
                $class = &$stmt;
            }
        }

        if (is_null($class)) return;

        $this->astlink['class'] = $class;

        $this->prepareProperties($class);
        $this->prepareTraits($class);
        $this->prepareMethods($class);
    }

    private function prepareMethods($class) {
        $stmts = $class->stmts;
        foreach ($stmts as $k => $stmt) {
            if (get_class($stmt) == 'PhpParser\Node\Stmt\ClassMethod') {
                unset($stmts[$k]);
            }
        }
        $class->stmts = array_values($stmts);
        $stmts = &$class->stmts;

        foreach ($this->methods as $k => &$p) {
            $flags = 0;
            if (!isset($p['access'])) {
                $p['access'] = 'public';
            }
            switch ($p['access']) {
                case 'private':
                    $flags |= Class_::MODIFIER_PRIVATE;
                    break;
                case 'protected':
                    $flags |= Class_::MODIFIER_PROTECTED;
                    break;
                default:
                    $flags |= Class_::MODIFIER_PUBLIC;
                    break;
            }

            if (!isset($p['static'])) {
                $p['static'] = false;
            }
            if ($p['static']) {
                $flags |= Class_::MODIFIER_STATIC;
            }

            if (!isset($p['abstract'])) {
                $p['abstract'] = false;
            }
            if ($p['abstract']) {
                $flags |= Class_::MODIFIER_ABSTRACT;
            }

            if (!isset($p['final'])) {
                $p['final'] = false;
            }
            if ($p['final']) {
                $flags |= Class_::MODIFIER_FINAL;
            }

            if (!isset($p['byRef'])) {
                $p['byRef'] = false;
            }

            if (!isset($p['params'])) {
                $p['params'] = [];
            }
            if (!isset($p['returnType'])) {
                $p['returnType'] = null;
            }

            if (!isset($p['code'])) {
                $p['code'] = '';
            }


            array_push($stmts, new ClassMethod($k, [
                'flags' => $flags,
                'byRef' => $p['byRef'],
                'params' => $p['params'],
                'returnType' => $p['returnType'],
                'stmts' => self::parseCode($p['code'], true)
            ]));
        }
    }

    private function prepareTraits(&$class) {
        $stmts = $class->stmts;
        foreach ($stmts as $k => $stmt) {
            if (get_class($stmt) == 'PhpParser\Node\Stmt\TraitUse') {
                unset($stmts[$k]);
            }
        }
        $class->stmts = array_values($stmts);
        $stmts = &$class->stmts;

        $traits = [];
        foreach ($this->traits as $k => $t) {
            if (in_array($t, $traits)) continue;

            $traits[] = $t['name'];

            array_unshift($stmts, new TraitUse([
                self::getName($t['name'])
            ]));
        }
    }

    private function prepareProperties(&$class)
    {
        $stmts = $class->stmts;
        foreach ($stmts as $k => $stmt) {
            if (get_class($stmt) == 'PhpParser\Node\Stmt\Property') {
                unset($stmts[$k]);
            }
        }
        $class->stmts = array_values($stmts);
        $stmts = &$class->stmts;

        foreach ($this->properties as $k => &$p) {
            $flags = 0;

            if (!isset($p['access'])) {
                $p['access'] = 'public';
            }
            switch ($p['access']) {
                case 'private':
                    $flags |= Class_::MODIFIER_PRIVATE;
                    break;
                case 'protected':
                    $flags |= Class_::MODIFIER_PROTECTED;
                    break;
                default:
                    $flags |= Class_::MODIFIER_PUBLIC;
                    break;
            }

            if (!isset($p['static'])) {
                $p['static'] = false;
            }

            if ($p['static']) {
                $flags |= Class_::MODIFIER_STATIC;
            }

            if (isset($p['value'])) {
                $default = self::parseValue($p['value']);
            } else {
                $default = null;
            }

            array_unshift($stmts, new Property($flags,[
                new PropertyProperty($k, $default)
            ]));
        }
    }
}