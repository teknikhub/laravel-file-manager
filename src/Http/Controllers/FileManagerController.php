<?php

namespace TeknikHub\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TeknikHub\FileManager\Services\FileManagerService;
use TeknikHub\FileManager\Services\FileUploadService;
use TeknikHub\FileManager\Services\PathService;
use Illuminate\Support\Facades\Storage;
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManagerController extends Controller
{
    public function __construct(
        protected FileManagerService $fileManagerService,
        protected FileUploadService $fileUploadService,
        protected PathService $pathService
    ) {}

    /**
     * Standalone UI View
     */
    public function index(Request $request)
    {
        $disk = $request->query('disk', config('teknikhub-file-manager.default_disk', 'public'));
        return view('teknikhub-file-manager::index', compact('disk'));
    }

    /**
     * List files and folders
     */
    public function browse(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $path = $request->input('path', '');
            $search = $request->input('search', '');
            
            if (!empty($search)) {
                $items = $this->fileManagerService->search($disk, $path, $search);
            } else {
                $items = $this->fileManagerService->list($disk, $path);
            }
            
            return response()->json(['result' => 'success', 'data' => $items]);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create a new folder
     */
    public function createFolder(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $path = $request->input('path', '');
            $name = $request->input('name');
            
            $folder = $this->fileManagerService->createFolder($disk, $path, $name);
            
            return response()->json(['result' => 'success', 'data' => $folder]);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Upload files
     */
    public function upload(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $path = $request->input('path', '');
            
            if (!$request->hasFile('file')) {
                throw new Exception('No files uploaded.');
            }

            $files = $request->file('file');
            if (!is_array($files)) {
                $files = [$files];
            }

            $uploaded = [];
            foreach ($files as $file) {
                $uploaded[] = $this->fileUploadService->upload($disk, $path, $file);
            }

            return response()->json(['result' => 'success', 'data' => $uploaded]);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Rename a file or folder
     */
    public function rename(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $oldPath = $request->input('path');
            $newName = $request->input('newName');
            
            $item = $this->fileManagerService->rename($disk, $oldPath, $newName);
            
            return response()->json(['result' => 'success', 'data' => $item]);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete files or folders
     */
    public function delete(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $paths = $request->input('paths', []);
            
            $this->fileManagerService->delete($disk, $paths);
            
            return response()->json(['result' => 'success']);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Copy or cut (move) items
     */
    public function paste(Request $request)
    {
        try {
            $disk = $request->input('disk', config('teknikhub-file-manager.default_disk'));
            $paths = $request->input('paths', []);
            $destination = (string) $request->input('destination', '');
            $isMove = $request->boolean('isMove', false);
            
            $this->fileManagerService->copyOrMove($disk, $paths, $destination, $isMove);
            
            return response()->json(['result' => 'success']);
        } catch (Exception $e) {
            return response()->json(['result' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Preview (stream file content for images/audio/video)
     */
    public function preview(Request $request)
    {
        try {
            $disk = $request->query('disk', config('teknikhub-file-manager.default_disk'));
            $path = $request->query('path', '');
            
            $safePath = $this->pathService->sanitize($path, $disk);
            $storage = Storage::disk($disk);
            
            if (!$storage->exists($safePath)) {
                abort(404, 'File not found');
            }

            $mimeType = $storage->mimeType($safePath);
            $stream = $storage->readStream($safePath);

            return response()->stream(
                function () use ($stream) {
                    fpassthru($stream);
                },
                200,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=86400',
                ]
            );
        } catch (Exception $e) {
            abort(404);
        }
    }

    /**
     * Download file
     */
    public function download(Request $request)
    {
        try {
            $disk = $request->query('disk', config('teknikhub-file-manager.default_disk'));
            $path = $request->query('path', '');
            
            $safePath = $this->pathService->sanitize($path, $disk);
            $storage = Storage::disk($disk);
            
            if (!$storage->exists($safePath)) {
                abort(404, 'File not found');
            }

            return $storage->download($safePath);
        } catch (Exception $e) {
            abort(404);
        }
    }
}
