<?php

namespace Redium\Storage;

class FileUpload
{
    private string $uploadDir;
    private array $allowedExtensions = [];
    private int $maxFileSize = 5242880; // 5MB default

    public function __construct(?string $uploadDir)
    {
        $this->uploadDir = $uploadDir ?? dirname(__DIR__, 2) . '/storage/uploads';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Set allowed file extensions
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set maximum file size in bytes
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Upload a file
     */
    public function upload(array $file, string $directory = ''): array
    {
        // Validate file
        $this->validate($file);

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = generateUniqueIdentifier(20) . '.' . $extension;

        // Create subdirectory if specified
        $targetDir = $this->uploadDir;
        if ($directory) {
            $targetDir .= '/' . trim($directory, '/');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $targetPath = $targetDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to upload file");
        }

        return [
            'filename' => $filename,
            'path' => $targetPath,
            'url' => $this->getUrl($directory, $filename),
            'size' => $file['size'],
            'mime_type' => $file['type']
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validate(array $file): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxMB = round($this->maxFileSize / 1048576, 2);
            throw new \Exception("File size exceeds maximum allowed size of {$maxMB}MB");
        }

        // Check file extension
        if (!empty($this->allowedExtensions)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $this->allowedExtensions)) {
                throw new \Exception("File type not allowed. Allowed types: " . implode(', ', $this->allowedExtensions));
            }
        }

        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception("Invalid file upload");
        }
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }

    /**
     * Get file URL
     */
    private function getUrl(string $directory, string $filename): string
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $path = $directory ? "storage/uploads/{$directory}/{$filename}" : "storage/uploads/{$filename}";
        return $baseUrl . '/' . $path;
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Store base64 encoded file
     */
    public function storeBase64(string $base64Data, string $directory = '', string $extension = 'png'): array
    {
        // Remove data URI scheme if present
        if (str_contains($base64Data, 'base64,')) {
            $base64Data = explode('base64,', $base64Data)[1];
        }

        $data = base64_decode($base64Data);
        
        if ($data === false) {
            throw new \Exception("Invalid base64 data");
        }

        // Generate unique filename
        $filename = generateUniqueIdentifier(20) . '.' . $extension;

        // Create subdirectory if specified
        $targetDir = $this->uploadDir;
        if ($directory) {
            $targetDir .= '/' . trim($directory, '/');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }

        $targetPath = $targetDir . '/' . $filename;

        // Save file
        if (file_put_contents($targetPath, $data) === false) {
            throw new \Exception("Failed to save file");
        }

        return [
            'filename' => $filename,
            'path' => $targetPath,
            'url' => $this->getUrl($directory, $filename),
            'size' => strlen($data)
        ];
    }
}
