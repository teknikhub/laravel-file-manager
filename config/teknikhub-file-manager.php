<?php

return [
    /**
     * The default disk to use.
     * The File Manager will list and manage files in this disk.
     */
    'default_disk' => env('TEKNIKHUB_FM_DISK', 'public'),

    /**
     * Disks that the File Manager is allowed to access.
     */
    'disks' => [
        'public',
        'local',
        's3',
    ],

    /**
     * The root directory within the selected disk.
     * Leave empty to allow access to the entire disk.
     */
    'root' => env('TEKNIKHUB_FM_ROOT', ''),

    /**
     * Routing configuration for the standalone file manager.
     */
    'route_prefix' => env('TEKNIKHUB_FM_ROUTE_PREFIX', 'admin/file-manager'),

    'middleware' => [
        'web',
        'auth',
    ],

    /**
     * Bootstrap version for views (4 or 5)
     */
    'bootstrap_version' => env('TEKNIKHUB_FM_BOOTSTRAP', 5),

    /**
     * Upload constraints.
     */
    'upload' => [
        // Max upload size in KB. Example: 10240 = 10MB
        'max_size' => env('TEKNIKHUB_FM_MAX_SIZE', 10240),
        
        // Allow multiple file upload
        'multiple' => true,
        
        // If not empty, only these extensions are allowed.
        'allowed_extensions' => [],
        
        // These extensions are blocked from being uploaded.
        'blocked_extensions' => [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'ps1', 'vbs',
        ],
    ],

    /**
     * Thumbnail generation configuration.
     */
    'thumbnails' => [
        'enabled' => true,
        
        // 'auto' (use intervention if available), 'intervention', or 'original'
        'mode' => 'auto',
        
        'width' => 300,
        'height' => 200,
        'quality' => 80,
        
        // Directory within the configured disk to store cached thumbnails.
        'cache_directory' => '.teknikhub-file-manager/thumbnails',
    ],

    /**
     * Features toggle. Disable if you want a read-only or restricted manager.
     */
    'features' => [
        'upload' => true,
        'folders' => true,
        'rename' => true,
        'delete' => true,
        'copy' => true,
        'cut' => true,
        'paste' => true,
        'download' => true,
        'preview' => true,
        'search' => true,
        'fullscreen' => true,
        'grid' => true,
        'list' => true,
    ],

    /**
     * ACL configuration placeholder.
     */
    'acl' => [
        'enabled' => false,
        'mode' => 'blacklist',
    ],
];
