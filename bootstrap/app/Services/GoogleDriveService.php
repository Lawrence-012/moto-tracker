<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission as DrivePermission;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $folderId;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google Drive API Client
     */
    protected function initializeClient()
    {
        $this->client = new Client();
        
        // Set credentials from .env
        $this->client->setClientId(config('services.google_drive.client_id'));
        $this->client->setClientSecret(config('services.google_drive.client_secret'));
        
        // Set scopes
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Drive::DRIVE_FILE);
        
        // Set refresh token and fetch access token
        $refreshToken = config('services.google_drive.refresh_token');
        if ($refreshToken) {
            try {
                // Fetch access token using refresh token
                $accessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                // Set the access token
                $this->client->setAccessToken($accessToken);
            } catch (\Exception $e) {
                Log::error("Failed to fetch access token: " . $e->getMessage());
                throw $e;
            }
        }
        
        // Initialize the Drive service
        $this->service = new Drive($this->client);
        
        // Get folder ID from config
        $this->folderId = config('services.google_drive.folder_id');
    }

    /**
     * Upload file to Google Drive
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string|null $filename Optional custom filename
     * @return array|false Returns file info ['id' => ..., 'webViewLink' => ...] or false on failure
     */
    public function uploadFile($file, $filename = null)
    {
        try {
            // Generate filename if not provided
            if ($filename === null) {
                $filename = time() . '_' . $file->getClientOriginalName();
            }

            // Get file contents
            $fileContents = file_get_contents($file->getRealPath());
            
            // Create a new file in Google Drive
            $googleFile = new DriveFile([
                'name' => $filename,
                'parents' => [$this->folderId],
            ]);

            // Upload the file
            $uploadedFile = $this->service->files->create($googleFile, [
                'data' => $fileContents,
                'mimeType' => $file->getClientMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, webContentLink'
            ]);

            // Make the file accessible with link
            $this->shareFileWithLink($uploadedFile->getId());

            Log::info("File uploaded to Google Drive: {$filename}", [
                'file_id' => $uploadedFile->getId(),
                'web_view_link' => $uploadedFile->getWebViewLink()
            ]);

            return [
                'id' => $uploadedFile->getId(),
                'name' => $filename,
                'webViewLink' => $uploadedFile->getWebViewLink(),
                'webContentLink' => $uploadedFile->getWebContentLink(),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to upload file to Google Drive: " . $e->getMessage());
            Log::error("Exception trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Delete file from Google Drive
     * 
     * @param string $fileId Google Drive File ID
     * @return bool
     */
    public function deleteFile($fileId)
    {
        try {
            $this->service->files->delete($fileId);
            Log::info("File deleted from Google Drive: {$fileId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete file from Google Drive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file metadata from Google Drive
     * 
     * @param string $fileId Google Drive File ID
     * @return array|false
     */
    public function getFileMetadata($fileId)
    {
        try {
            $file = $this->service->files->get($fileId, [
                'fields' => 'id, name, mimeType, size, createdTime, webViewLink'
            ]);
            
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'createdTime' => $file->getCreatedTime(),
                'webViewLink' => $file->getWebViewLink(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get file metadata from Google Drive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Share file with anyone who has the link
     * 
     * @param string $fileId Google Drive File ID
     * @return void
     */
    protected function shareFileWithLink($fileId)
    {
        try {
            // Create permission for anyone with the link to view
            $permission = new DrivePermission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            $this->service->permissions->create($fileId, $permission);
            
            Log::info("File shared publicly: {$fileId}");
        } catch (\Exception $e) {
            Log::error("Failed to share file {$fileId}: " . $e->getMessage());
            Log::error("Exception trace: " . $e->getTraceAsString());
            throw $e; // Re-throw so we know it failed
        }
    }
}