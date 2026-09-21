<?php

namespace TeknikHub\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class FileUploadService
{
    public function __construct(
        protected PathService $pathService
    ) {}

    /**
     * Upload a file to the specified path on the disk.
     */
    public function upload(string $disk, ?string $path, UploadedFile $file): array
    {
        $safePath = $this->pathService->sanitize($path, $disk);
        $storage = Storage::disk($disk);
        
        // Ensure path exists
        if ($safePath !== '' && !$storage->exists($safePath)) {
            $storage->makeDirectory($safePath);
        }

        $config = config('teknikhub-file-manager.upload');
        $maxSize = ($config['max_size'] ?? 10240) * 1024; // Convert KB to Bytes
        
        if ($file->getSize() > $maxSize) {
            throw new Exception("File size exceeds the allowed limit.");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        
        $allowed = $config['allowed_extensions'] ?? [];
        if (!empty($allowed) && !in_array($extension, $allowed)) {
            throw new Exception("File type not allowed.");
        }

        $blocked = $config['blocked_extensions'] ?? [];
        if (in_array($extension, $blocked)) {
            throw new Exception("File type is blocked for security reasons.");
        }
        
        // Extra check for PHP execution contexts
        $mime = $file->getClientMimeType();
        if (str_contains($mime, 'php') || str_contains($extension, 'php')) {
            throw new Exception("Invalid file type.");
        }

        // Sanitize filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
        if (empty($safeName)) {
            $safeName = 'upload_' . time();
        }
        
        $filename = $safeName . '.' . $extension;
        $targetPath = $safePath !== '' ? $safePath . '/' . $filename : $filename;

        // Prevent overwrite by appending timestamp if exists
        if ($storage->exists($targetPath)) {
            $filename = $safeName . '_' . time() . '.' . $extension;
            $targetPath = $safePath !== '' ? $safePath . '/' . $filename : $filename;
        }

        // Store file
        $file->storeAs($safePath, $filename, ['disk' => $disk]);

        return [
            'name' => $filename,
            'path' => $targetPath,
            'type' => 'file',
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
