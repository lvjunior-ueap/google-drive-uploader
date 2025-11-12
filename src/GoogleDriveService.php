<?php

namespace LvjuniorUeap\GoogleDriveUploader;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Exception;

class GoogleDriveService
{
    private GoogleDrive $drive;

    public function __construct(string $credentialsPath)
    {
        if (!file_exists($credentialsPath)) {
            throw new Exception("Arquivo de credenciais não encontrado: {$credentialsPath}");
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(GoogleDrive::DRIVE);
        $client->addScope(GoogleDrive::DRIVE_FILE);
        $client->addScope(GoogleDrive::DRIVE_APPDATA);

        $this->drive = new GoogleDrive($client);
    }

    public function upload(string $filePath, string $folderId): DriveFile
    {
        if (!file_exists($filePath)) {
            throw new Exception("Arquivo não encontrado: {$filePath}");
        }

        $fileMetadata = new DriveFile([
            'name' => basename($filePath),
            'parents' => [$folderId],
        ]);

        $content = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);

        return $this->drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,name,webViewLink,webContentLink,capabilities',
            'supportsAllDrives' => true,
        ]);
    }

    public function listFiles(string $folderId): array
    {
        $response = $this->drive->files->listFiles([
            'q' => "'$folderId' in parents and trashed = false",
            'fields' => 'files(id,name,mimeType,webViewLink,webContentLink,parents)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        return $response->files;
    }

    public function deleteFile(string $fileId): bool
    {
        try {
            $this->drive->files->delete($fileId, ['supportsAllDrives' => true]);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function getOrCreateFolder(string $folderName, ?string $parentId = null): string
    {
        $query = "mimeType='application/vnd.google-apps.folder' and name='$folderName' and trashed=false";
        if ($parentId) $query .= " and '$parentId' in parents";

        $response = $this->drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id,name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        if (count($response->files) > 0) {
            return $response->files[0]->id;
        }

        $folderMetadata = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if ($parentId) {
            $folderMetadata->setParents([$parentId]);
        }

        $folder = $this->drive->files->create($folderMetadata, [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        return $folder->id;
    }

    public function moveFileById(string $fileId, string $newParentId, string $newName = null): DriveFile
    {
        $file = $this->drive->files->get($fileId, [
            'fields' => 'parents',
            'supportsAllDrives' => true,
        ]);

        $previousParents = $file->parents ? implode(',', $file->parents) : null;

        $metadata = new DriveFile();
        if ($newName) $metadata->setName($newName);

        return $this->drive->files->update($fileId, $metadata, [
            'addParents' => $newParentId,
            'removeParents' => $previousParents,
            'fields' => 'id,name,webViewLink,webContentLink,parents',
            'supportsAllDrives' => true,
        ]);
    }
}
