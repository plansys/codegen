<?php

namespace Plansys\Codegen\Writer\ClassPreparer;

use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

trait PrepareUse
{
    private function prepareUse()
    {
        if (isset($this->astlink['namespace'])) {
            $stmts = &$this->astlink['namespace']->stmts;
        } else {
            $stmts = &$this->ast;
        }
        foreach ($stmts as $k => $stmt) {
            if (get_class($stmt) == 'PhpParser\Node\Stmt\Use_') {
                unset($stmts[$k]);
            }
        }

        $uses = [];
        foreach ($this->uses as $use) {
            if (in_array($use['name'], $uses)) continue;

            $uses[] = $use['name'];
            $useuse = new Stmt\UseUse(self::getName($use['name']), @$use['alias']);
            array_unshift($stmts, new Stmt\Use_([$useuse]));
        }
    }
}