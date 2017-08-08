<?php

namespace Plansys\Codegen\Writer\ClassPreparer;

use PhpParser\Node\Stmt;
use PhpParser\Node\Name;

trait PrepareNamespace
{
    private function prepareNamespace()
    {
        $this->namespace = preg_replace('/[^a-zA-Z0-9_]+/', '_', $this->namespace);

        if ($this->namespace) {
            if ($this->astlink['namespace']) {
                $this->astlink['namespace']->name = self::getName($this->namespace);
            } else {
                $this->wrapClassInNamespace();
            }
        } else {
            if (is_object($this->astlink['namespace'])) {
                $this->removeNamespace();
            }
        }
    }

    private function wrapClassInNamespace() {
        $stmts = $this->astlink['uses'];
        $stmts[] = $this->astlink['class'];
        $this->ast = [new Stmt\Namespace_(self::getName($this->namespace), $stmts)];
    }

    private function removeNamespace() {
        $this->ast = $this->astlink['namespace']->stmts;
        $this->astlink['namespace'] = '';
    }
}