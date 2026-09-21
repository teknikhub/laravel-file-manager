# TeknikHub Laravel File Manager

A filesystem-first, database-free File Manager and File Picker package for Laravel. It provides a beautiful, responsive user interface built on **Bootstrap 5** to seamlessly manage your files and folders, allowing for easy integration anywhere in your Laravel application.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/teknikhub/laravel-file-manager.svg?style=flat-square)](https://packagist.org/packages/teknikhub/laravel-file-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/teknikhub/laravel-file-manager.svg?style=flat-square)](https://packagist.org/packages/teknikhub/laravel-file-manager)
[![License](https://img.shields.io/packagist/l/teknikhub/laravel-file-manager.svg?style=flat-square)](https://packagist.org/packages/teknikhub/laravel-file-manager)

## Features

- **Database-Free:** Operates directly on your Laravel `Storage` disks. No database tables required.
- **Bootstrap 5 UI:** A clean, modern, and responsive user interface built with standard Bootstrap 5.
- **Multiple Disks:** Supports seamlessly switching between multiple configured storage disks (e.g., `public`, `s3`, `local`).
- **File Picker Component:** Easily integrate a file picker popup into your existing forms.
- **Security First:** Built-in protection against directory traversal attacks, mime-type spoofing, and dangerous file extensions (like `.php`).
- **Dynamic Thumbnails:** Generates thumbnails on the fly (supports both Intervention Image v2 and v3).
- **Search & Filter:** Instantly search through directories for files.

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- Bootstrap 5 (for the UI to render correctly in your layouts)

## Installation & Setup Guide (Step-by-Step)

Follow these simple steps to get the File Manager up and running in your Laravel project.

### Step 1: Install via Composer
First, open your terminal, navigate to your Laravel project folder, and run this composer command to download the package:

```bash
composer require teknikhub/laravel-file-manager
```

### Step 2: Publish Assets & Configuration
The package comes with its own CSS, JavaScript, and a configuration file. You need to "publish" these into your main Laravel application so the browser can load them.

Run this command in your terminal:

```bash
php artisan vendor:publish --provider="TeknikHub\FileManager\FileManagerServiceProvider" --force
```
*(Tip: Always use the `--force` flag when updating the package later to make sure you get the newest CSS/JS files!)*

### Step 3: Link Your Storage
Since the file manager deals with uploaded files, Laravel needs a symbolic link to make your `storage/app/public` folder accessible from the web.

Run this standard Laravel command:

```bash
php artisan storage:link
```

### Step 4: Configure Your Settings (Optional)
A new configuration file has been created at `config/teknikhub-file-manager.php`. Open this file to customize how the file manager works.

Here is a breakdown of the available configuration options:

```php
return [
    // The default disk to load when the file manager opens
    'default_disk' => env('TEKNIKHUB_FM_DISK', 'public'),

    // An array of disks the file manager is allowed to access and switch between
    'disks' => [
        'public',
        // 's3',
    ],

    // Restrict access to a specific subfolder within a disk (leave empty for root access)
    'root' => env('TEKNIKHUB_FM_ROOT', ''),

    // The URL path for the standalone file manager page
    'route_prefix' => env('TEKNIKHUB_FM_ROUTE_PREFIX', 'admin/file-manager'),

    // Middleware to protect the standalone routes (IMPORTANT: secure this!)
    'middleware' => ['web', 'auth'],

    // Upload constraints
    'upload' => [
        'max_size' => env('TEKNIKHUB_FM_MAX_SIZE', 10240), // Max size in KB (10MB)
        'multiple' => true, // Allow selecting multiple files at once
        'allowed_extensions' => [], // Whitelist: leave empty to allow all (except blocked)
        'blocked_extensions' => ['php', 'sh', 'exe', 'bat'], // Blacklist: malicious files
    ],
    
    // Thumbnail generation settings
    'thumbnails' => [
        'enabled' => true,
        'mode' => 'auto', // Auto-detects Intervention Image v2 or v3
        'width' => 300,
        'height' => 200,
        'quality' => 80,
        'cache_directory' => '.teknikhub-file-manager/thumbnails', // Hidden cache folder
    ],

    // Toggle specific UI features on or off
    'features' => [
        'upload' => true,
        'folders' => true,
        'rename' => true,
        'delete' => true,
        'copy' => true,
        'download' => true,
        // ... see config file for all toggles
    ],
];
```



## Usage

### 1. Using as a Standalone Page
The package automatically registers routes under the prefix you specify in the config (default: `/admin/file-manager`). Simply navigate to that URL in your browser to use the full-page file manager.

### 2. Using as a Component in Blade
You can easily embed the File Manager directly into any Blade view using the provided component. This is perfect for embedding it inside a dashboard panel.

```blade
<x-teknikhub-file-manager />
```
*Note: Make sure Bootstrap 5 CSS and JS are loaded in the layout where you embed this component.*

### 3. Using as a File Picker (Modal/Popup)
To use it as a file picker (e.g., for selecting an image for an input field in a form), include the component with the `picker` attribute set to true and define an `onSelect` callback.

```html
<!-- Input field that will receive the selected file URL -->
<div class="mb-3">
    <label class="form-label">Profile Image</label>
    <div class="input-group">
        <input type="text" id="image_url" name="image_url" class="form-control">
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#fileManagerModal">
            Browse
        </button>
    </div>
</div>

<!-- Bootstrap 5 Modal containing the File Picker -->
<div class="modal fade" id="fileManagerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body p-0" style="height: 70vh;">
                <!-- Include the file manager component -->
                <x-teknikhub-file-manager :picker="true" onSelect="handleFileSelect" />
            </div>
        </div>
    </div>
</div>

<script>
    function handleFileSelect(fileUrl) {
        // Set the input value to the selected URL
        document.getElementById('image_url').value = fileUrl;
        
        // Hide the Bootstrap 5 modal
        var modalEl = document.getElementById('fileManagerModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    }
</script>
```

## Security considerations

Security is handled by restricting route access based on your application's middleware. Ensure you appropriately set the `middleware` array in `config/teknikhub-file-manager.php` (e.g., `['web', 'auth']` or an admin-specific middleware) to restrict who can access, upload, or delete files. The package inherently sanitizes all incoming paths to prevent directory traversal (`../`) attacks.

## Testing

This package uses `orchestra/testbench` for testing.
To run the tests locally:

```bash
composer install
vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see License File for more information.
