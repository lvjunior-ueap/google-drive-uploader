<?php

namespace LvjuniorUeap\GoogleDriveUploader;

use Exception;

class GoogleDrive
{
    private static ?GoogleDriveService $instance = null;

    public static function init(string $credentialsPath): void
    {
        self::$instance = new GoogleDriveService($credentialsPath);
    }

    public static function __callStatic($name, $arguments)
    {
        if (!self::$instance) {
            throw new Exception("GoogleDriveService não inicializado. Chame GoogleDrive::init() primeiro.");
        }

        return self::$instance->$name(...$arguments);
    }
}
