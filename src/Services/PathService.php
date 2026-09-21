<?php

namespace TeknikHub\FileManager\Services;

use Exception;

class PathService
{
    /**
     * Get the configured root for a specific disk.
     */
    public function getRoot(string $disk = null): string
    {
        return config('teknikhub-file-manager.root', '');
    }

    /**
     * Sanitize a path and ensure it remains inside the allowed root.
     * Returns the sanitized path relative to the disk's base, OR throws an exception if invalid.
     *
     * @param string|null $path The user-provided path
     * @param string|null $disk The disk context
     * @return string
     * @throws Exception
     */
    public function sanitize(?string $path, string $disk = null): string
    {
        $path = $path ?? '';

        // Decode URL components
        $path = urldecode($path);

        // Reject null bytes
        if (str_contains($path, "\0")) {
            throw new Exception("Invalid path: contains null bytes.");
        }

        // Normalize slashes
        $path = str_replace('\\', '/', $path);
        
        // Remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path);

        // Explode into segments
        $segments = explode('/', $path);
        $cleanSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                // Pop the last segment off to simulate navigating up.
                // If it goes below zero, it's a path traversal attempt outside the requested scope.
                if (count($cleanSegments) === 0) {
                    throw new Exception("Invalid path: directory traversal attempt.");
                }
                array_pop($cleanSegments);
            } else {
                $cleanSegments[] = $segment;
            }
        }

        $cleanPath = implode('/', $cleanSegments);

        // Prepend the root if configured
        $root = $this->getRoot($disk);
        if ($root !== '') {
            $root = trim(str_replace('\\', '/', $root), '/');
            if ($cleanPath === '') {
                $cleanPath = $root;
            } else {
                // Ensure it doesn't already start with the root (avoid duplication if user passed it)
                if (!str_starts_with($cleanPath, $root . '/')) {
                    $cleanPath = $root . '/' . $cleanPath;
                }
            }
        }

        return $cleanPath;
    }

    /**
     * Check if a path is a hidden file/folder (e.g., .teknikhub-file-manager)
     */
    public function isHidden(string $path): bool
    {
        $basename = basename($path);
        return str_starts_with($basename, '.');
    }
}
