<?php
// src/FacturePdfBundle/FacturePdfBundle.php

namespace App\FacturePdfBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FacturePdfBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__).'/FacturePdfBundle';
    }
}