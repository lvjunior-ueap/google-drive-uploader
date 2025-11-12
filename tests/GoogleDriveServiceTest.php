<?php

namespace LvjuniorUeap\GoogleDriveUploader\Tests;

use PHPUnit\Framework\TestCase;
use LvjuniorUeap\GoogleDriveUploader\GoogleDriveService;

class GoogleDriveServiceTest extends TestCase
{
    public function testInstanciaServico()
    {
        $this->expectException(\Exception::class);
        new GoogleDriveService('/caminho/invalido/credentials.json');
    }
}
