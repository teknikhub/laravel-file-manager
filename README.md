# Laravel File Manager

A filesystem-first, database-free File Manager and File Picker package for Laravel. It provides a beautiful user interface to manage your files and folders, allowing integration anywhere in your Laravel application.

## Requirements

- PHP >= 8.2
- Laravel >= 11.0

## Installation

You can install the package via composer:

```bash
composer require teknikhub/laravel-file-manager
```

## Setup

1. **Publish Assets and Configuration:**
   Publish the required assets (CSS/JS files) and the configuration file.

   ```bash
   php artisan vendor:publish --provider="TeknikHub\FileManager\FileManagerServiceProvider" --force
   ```

   *Note: Use the `--force` flag when updating the package to ensure you receive the latest assets.*

2. **Configure Disks:**
   The published configuration file is located at `config/teknikhub-file-manager.php`. Here you can specify which disks (configured in your `config/filesystems.php`) you want the file manager to be able to access.

   ```php
   'disks' => [
       'public', 
       // 's3',
       // 'local'
   ],
   ```

## Usage

### Using as a Standalone Page
The package automatically registers routes under the prefix you specify in the config (default: `admin/file-manager`). You can navigate to `/admin/file-manager` in your browser to use the full-page file manager.

### Using as a Component in Blade
You can easily embed the File Manager into any Blade view using the provided component:

```blade
<x-teknikhub-file-manager />
```

### Using as a File Picker (Popup or Modal)
To use it as a file picker (e.g., selecting an image for an input field), simply include the component with the `picker` attribute set to true and an `onSelect` callback.

```html
<!-- Input field that will receive the selected file URL -->
<input type="text" id="image_url" name="image_url" class="form-control">

<!-- Include the file manager component with picker="true" -->
<div style="height: 500px;">
    <x-teknikhub-file-manager :picker="true" onSelect="handleFileSelect" />
</div>

<script>
    function handleFileSelect(fileUrl) {
        // fileUrl is the URL of the selected file
        document.getElementById('image_url').value = fileUrl;
        
        // Hide modal or picker UI here
    }
</script>
```

## Security

This package relies on Laravel's built-in file storage system. Security is handled by restricting access based on your application's middleware (e.g., adding `auth` middleware to the file manager routes via config) and by protecting against directory traversal attacks.

Make sure you appropriately set the middleware array in `config/teknikhub-file-manager.php` to restrict who can access the routes.

## Testing

This package uses `orchestra/testbench` for testing.
To run the tests:

```bash
cd packages/teknikhub/laravel-file-manager
composer install
vendor/bin/phpunit
```

## License

The MIT License (MIT).
