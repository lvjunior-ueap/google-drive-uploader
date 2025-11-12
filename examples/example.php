<?php

require __DIR__ . '/../vendor/autoload.php';

use LvjuniorUeap\GoogleDriveUploader\GoogleDrive;

// Inicializa
GoogleDrive::init(__DIR__ . '/credentials.json');

// Cria ou obtém pasta
$folderId = GoogleDrive::getOrCreateFolder('Testes');

// Faz upload de um arquivo local
$file = GoogleDrive::upload(__DIR__ . '/arquivo.pdf', $folderId);

echo "Enviado: {$file->name} (ID: {$file->id})" . PHP_EOL;
