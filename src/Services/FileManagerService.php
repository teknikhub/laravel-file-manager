<?php

namespace TeknikHub\FileManager\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Exception;

class FileManagerService
{
    public function __construct(
        protected PathService $pathService,
        protected FileTypeResolver $fileTypeResolver,
        protected ThumbnailService $thumbnailService
    ) {}

    /**
     * List files and folders in a given path on a specific disk.
     */
    public function list(string $disk, ?string $path): array
    {
        $safePath = $this->pathService->sanitize($path, $disk);
        $storage = Storage::disk($disk);
        
        if ($safePath !== '' && !$storage->exists($safePath)) {
            throw new Exception("Directory not found.");
        }

        $directories = $storage->directories($safePath);
        $files = $storage->files($safePath);

        $result = [];

        foreach ($directories as $dir) {
            if ($this->pathService->isHidden($dir)) {
                continue;
            }
            $result[] = [
                'name' => basename($dir),
                'path' => $dir,
                'type' => 'directory',
            ];
        }

        foreach ($files as $file) {
            if ($this->pathService->isHidden($file)) {
                continue;
            }
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $type = $this->fileTypeResolver->getType($extension);
            
            $item = [
                'name' => basename($file),
                'path' => $file,
                'type' => 'file',
                'extension' => $extension,
                'mime_type' => $storage->mimeType($file),
                'size' => $storage->size($file),
                'modified' => Carbon::createFromTimestamp($storage->lastModified($file))->toDateTimeString(),
                'previewable' => $this->fileTypeResolver->isPreviewable($extension),
                'icon' => $type,
                'url' => $storage->url($file),
            ];

            if ($type === 'image') {
                $item['thumbnail'] = $this->thumbnailService->getThumbnail($disk, $file);
            }

            $result[] = $item;
        }

        return $result;
    }

    public function search(string $disk, ?string $path, string $query): array
    {
        $safePath = $this->pathService->sanitize($path, $disk);
        $storage = Storage::disk($disk);
        
        $directories = $storage->allDirectories($safePath);
        $files = $storage->allFiles($safePath);

        $result = [];
        $query = mb_strtolower($query);

        foreach ($directories as $dir) {
            if ($this->pathService->isHidden($dir)) {
                continue;
            }
            $basename = basename($dir);
            if (str_contains(mb_strtolower($basename), $query)) {
                $result[] = [
                    'name' => $basename,
                    'path' => $dir,
                    'type' => 'directory',
                ];
            }
        }

        foreach ($files as $file) {
            if ($this->pathService->isHidden($file)) {
                continue;
            }
            $basename = basename($file);
            if (str_contains(mb_strtolower($basename), $query)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $type = $this->fileTypeResolver->getType($extension);
                
                $item = [
                    'name' => $basename,
                    'path' => $file,
                    'type' => 'file',
                    'extension' => $extension,
                    'mime_type' => $storage->mimeType($file),
                    'size' => $storage->size($file),
                    'modified' => Carbon::createFromTimestamp($storage->lastModified($file))->toDateTimeString(),
                    'previewable' => $this->fileTypeResolver->isPreviewable($extension),
                    'icon' => $type,
                    'url' => $storage->url($file),
                ];

                if ($type === 'image') {
                    $item['thumbnail'] = $this->thumbnailService->getThumbnail($disk, $file);
                }

                $result[] = $item;
            }
        }

        return $result;
    }

    public function createFolder(string $disk, ?string $path, string $name): array
    {
        $safePath = $this->pathService->sanitize($path, $disk);
        
        // Prevent path traversal in the name itself
        $name = basename(str_replace('\\', '/', $name));
        if (empty($name) || $name === '.' || $name === '..') {
            throw new Exception("Invalid folder name.");
        }

        $targetPath = $safePath !== '' ? $safePath . '/' . $name : $name;
        $storage = Storage::disk($disk);

        if ($storage->exists($targetPath)) {
            throw new Exception("Folder already exists.");
        }

        $storage->makeDirectory($targetPath);

        return [
            'name' => $name,
            'path' => $targetPath,
            'type' => 'directory'
        ];
    }

    public function rename(string $disk, string $oldPath, string $newName): array
    {
        $safeOldPath = $this->pathService->sanitize($oldPath, $disk);
        
        $newName = basename(str_replace('\\', '/', $newName));
        if (empty($newName) || $newName === '.' || $newName === '..') {
            throw new Exception("Invalid new name.");
        }

        $storage = Storage::disk($disk);
        if (!$storage->exists($safeOldPath)) {
            throw new Exception("Item not found.");
        }

        $parentPath = dirname($safeOldPath);
        if ($parentPath === '.') {
            $parentPath = '';
        }

        $safeNewPath = $parentPath !== '' ? $parentPath . '/' . $newName : $newName;

        if ($storage->exists($safeNewPath)) {
            throw new Exception("An item with the new name already exists.");
        }

        if ($storage->directoryExists($safeOldPath)) {
            $storage->move($safeOldPath, $safeNewPath);
            return [
                'name' => $newName,
                'path' => $safeNewPath,
                'type' => 'directory'
            ];
        } else {
            $storage->move($safeOldPath, $safeNewPath);
            $extension = pathinfo($safeNewPath, PATHINFO_EXTENSION);
            return [
                'name' => $newName,
                'path' => $safeNewPath,
                'type' => 'file',
                'extension' => $extension,
            ];
        }
    }

    public function delete(string $disk, array $paths): void
    {
        $storage = Storage::disk($disk);
        
        foreach ($paths as $path) {
            $safePath = $this->pathService->sanitize($path, $disk);
            
            if ($storage->directoryExists($safePath)) {
                $storage->deleteDirectory($safePath);
            } elseif ($storage->fileExists($safePath)) {
                $storage->delete($safePath);
            }
        }
    }

    public function copyOrMove(string $disk, array $paths, string $destination, bool $isMove = false): void
    {
        $safeDestination = $this->pathService->sanitize($destination, $disk);
        $storage = Storage::disk($disk);

        foreach ($paths as $path) {
            $safePath = $this->pathService->sanitize($path, $disk);
            $name = basename($safePath);
            $targetPath = $safeDestination !== '' ? $safeDestination . '/' . $name : $name;

            // Prevent moving into itself or a descendant (for directories)
            if ($safePath === $targetPath && $isMove) {
                // Moving to the exact same location is a no-op
                continue;
            }
            
            if (str_starts_with($targetPath, $safePath . '/')) {
                throw new Exception("Cannot move/copy a directory into itself.");
            }
            
            if ($storage->exists($targetPath)) {
                if ($isMove) {
                    throw new Exception("Item {$name} already exists in destination.");
                } else {
                    // For copy, append a suffix to avoid collision
                    $targetPath = $this->generateUniqueName($storage, $safeDestination, $name);
                }
            }

            if ($storage->directoryExists($safePath)) {
                // Flysystem doesn't natively copy directories recursively well via simple copy()
                // A workaround is needed or it's a move.
                if ($isMove) {
                    $storage->move($safePath, $targetPath);
                } else {
                    $this->copyDirectory($storage, $safePath, $targetPath);
                }
            } elseif ($storage->fileExists($safePath)) {
                if ($isMove) {
                    $storage->move($safePath, $targetPath);
                } else {
                    $storage->copy($safePath, $targetPath);
                }
            }
        }
    }
    
    protected function copyDirectory($storage, $source, $destination)
    {
        $storage->makeDirectory($destination);
        foreach ($storage->files($source) as $file) {
            $storage->copy($file, $destination . '/' . basename($file));
        }
        foreach ($storage->directories($source) as $dir) {
            $this->copyDirectory($storage, $dir, $destination . '/' . basename($dir));
        }
    }

    protected function generateUniqueName($storage, $destination, $name)
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $filename = pathinfo($name, PATHINFO_FILENAME);
        
        $counter = 1;
        while (true) {
            $newName = $filename . " ({$counter})" . ($extension ? '.' . $extension : '');
            $targetPath = $destination !== '' ? $destination . '/' . $newName : $newName;
            if (!$storage->exists($targetPath)) {
                return $targetPath;
            }
            $counter++;
        }
    }
}
