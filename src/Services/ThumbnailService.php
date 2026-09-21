<?php

namespace TeknikHub\FileManager\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class ThumbnailService
{
    /**
     * Generate or retrieve a thumbnail for a given path.
     * Returns the relative path to the cached thumbnail, or null if not applicable.
     */
    public function getThumbnail(string $disk, string $path): ?string
    {
        $config = config('teknikhub-file-manager.thumbnails');
        
        if (!($config['enabled'] ?? true)) {
            return null;
        }

        $mode = $config['mode'] ?? 'auto';
        $width = $config['width'] ?? 300;
        $height = $config['height'] ?? 200;
        $quality = $config['quality'] ?? 80;
        $cacheDir = $config['cache_directory'] ?? '.teknikhub-file-manager/thumbnails';

        $storage = Storage::disk($disk);
        
        // Ensure the file exists
        if (!$storage->exists($path)) {
            return null;
        }

        // Determine if we should use Intervention
        $useIntervention = false;
        if ($mode === 'intervention' || $mode === 'auto') {
            if (class_exists(\Intervention\Image\ImageManager::class)) {
                $useIntervention = true;
            }
        }

        if (!$useIntervention) {
            return null; // Fallback to CSS scaling of original image in the frontend
        }

        // Generate a unique cache filename based on path and dimensions
        $hash = md5($path . $width . $height . $quality . $storage->lastModified($path));
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $thumbnailPath = rtrim($cacheDir, '/') . '/' . $hash . '.' . $extension;

        if ($storage->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        // Generate thumbnail
        try {
            $content = $storage->get($path);
            
            // Handle Intervention Image v3 (or v2 fallback)
            if (class_exists(\Intervention\Image\ImageManager::class)) {
                // Determine driver (gd or imagick)
                $driver = class_exists(\Intervention\Image\Drivers\Gd\Driver::class) 
                    ? new \Intervention\Image\Drivers\Gd\Driver() 
                    : 'gd';
                
                if (is_string($driver) && class_exists(\Intervention\Image\ImageManagerStatic::class)) {
                    // v2 support
                    $image = \Intervention\Image\ImageManagerStatic::make($content)
                        ->fit($width, $height);
                    $storage->put($thumbnailPath, (string) $image->encode($extension, $quality));
                } else {
                    // v3 support
                    $manager = new \Intervention\Image\ImageManager($driver);
                    $image = $manager->read($content)
                        ->cover($width, $height);
                    $encoded = $image->toJpeg($quality); // Simplification, ideally match extension
                    if (in_array(strtolower($extension), ['png', 'webp', 'gif'])) {
                        $encoded = match(strtolower($extension)) {
                            'png' => $image->toPng(),
                            'webp' => $image->toWebp($quality),
                            'gif' => $image->toGif(),
                            default => $image->toJpeg($quality)
                        };
                    }
                    $storage->put($thumbnailPath, $encoded->toString());
                }
            }
            
            return $thumbnailPath;
            
        } catch (Exception $e) {
            // If generation fails, fail silently and return null (frontend will fallback)
            return null;
        }
    }
}
