<?php

namespace Plansys\Codegen\Writer;

trait ClassWriter
{
    use ClassPreparer\PrepareNamespace;
    use ClassPreparer\PrepareUse;
    use ClassPreparer\PrepareClass;

    public function save()
    {
        $this->prepareNamespace();
        $this->prepareUse();
        $this->prepareClass();

        $code = self::printCode($this->ast);
        file_put_contents($this->filename, $code);
    }
}