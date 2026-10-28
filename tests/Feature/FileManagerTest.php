<?php

namespace TeknikHub\FileManager\Tests\Feature;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TeknikHub\FileManager\Services\PathService;
use TeknikHub\FileManager\Tests\TestCase;

class FileManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_list_directory()
    {
        Storage::disk('public')->put('test.txt', 'Hello World');
        Storage::disk('public')->makeDirectory('my_folder');

        $response = $this->getJson(route('teknikhub.file-manager.browse', ['disk' => 'public', 'path' => '']));

        $response->assertStatus(200)
            ->assertJsonPath('result', 'success');

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_can_create_folder()
    {
        $response = $this->postJson(route('teknikhub.file-manager.folder'), [
            'disk' => 'public',
            'path' => '',
            'name' => 'new_folder',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'success');

        Storage::disk('public')->assertExists('new_folder');
    }

    public function test_prevents_path_traversal_in_folder_creation()
    {
        $response = $this->postJson(route('teknikhub.file-manager.folder'), [
            'disk' => 'public',
            'path' => '',
            'name' => '../../malicious',
        ]);

        // Validation should fail or it creates a sanitized folder named 'malicious' due to basename
        // In our implementation, basename(../../malicious) -> malicious
        $response->assertStatus(200);
        Storage::disk('public')->assertExists('malicious');
        Storage::disk('public')->assertMissing('../../malicious');
    }

    public function test_path_service_prevents_traversal()
    {
        $pathService = new PathService;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid path: directory traversal attempt.');

        $pathService->sanitize('../../../etc/passwd', 'public');
    }

    public function test_can_upload_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson(route('teknikhub.file-manager.upload'), [
            'disk' => 'public',
            'path' => '',
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'success');

        Storage::disk('public')->assertExists('avatar.jpg');
    }

    public function test_can_upload_file_with_unicode_filename()
    {
        $file = UploadedFile::fake()->create('وُلد في .pdf', 100, 'application/pdf');

        $response = $this->postJson(route('teknikhub.file-manager.upload'), [
            'disk' => 'public',
            'path' => '',
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'success')
            ->assertJsonPath('data.name', 'وُلد_في_.pdf');

        Storage::disk('public')->assertExists('وُلد_في_.pdf');
    }

    public function test_blocked_extensions_are_prevented()
    {
        config(['teknikhub-file-manager.upload.blocked_extensions' => ['php']]);

        $file = UploadedFile::fake()->create('shell.php', 100);

        $response = $this->postJson(route('teknikhub.file-manager.upload'), [
            'disk' => 'public',
            'path' => '',
            'file' => $file,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('result', 'error');

        Storage::disk('public')->assertMissing('shell.php');
    }
}
