<?php

namespace Plansys\Codegen\Writer;

use PhpParser\PrettyPrinter;
use PhpParser\Node\Expr;
class CodePrinter extends PrettyPrinter\Standard
{

    protected function pExpr_Array(Expr\Array_ $node) {
        $syntax = $node->getAttribute('kind',
            $this->options['shortArraySyntax'] ? Expr\Array_::KIND_SHORT : Expr\Array_::KIND_LONG);

        if ($syntax === Expr\Array_::KIND_SHORT) {
            return '[' . $this->pMaybeMultiline($node->items, true) . ']';
        } else {
            return 'array(' . $this->pMaybeMultiline($node->items, true) . ')';
        }
    }

    private function pMaybeMultiline(array $nodes, $trailingComma = false) {
        return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . "\n";
    }
}