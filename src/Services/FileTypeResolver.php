<?php

namespace TeknikHub\FileManager\Services;

class FileTypeResolver
{
    /**
     * Map common extensions to general categories.
     */
    protected array $extensionMap = [
        // Images
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
        'webp' => 'image', 'svg' => 'image', 'bmp' => 'image', 'ico' => 'image',
        // Documents
        'pdf' => 'pdf',
        'doc' => 'document', 'docx' => 'document', 'odt' => 'document',
        'xls' => 'spreadsheet', 'xlsx' => 'spreadsheet', 'csv' => 'spreadsheet',
        'ppt' => 'presentation', 'pptx' => 'presentation',
        // Archives
        'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive', 'tar' => 'archive', 'gz' => 'archive',
        // Audio
        'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio', 'flac' => 'audio',
        // Video
        'mp4' => 'video', 'mkv' => 'video', 'webm' => 'video', 'avi' => 'video', 'mov' => 'video',
        // Code/Text
        'txt' => 'text', 'rtf' => 'text',
        'php' => 'code', 'js' => 'code', 'css' => 'code', 'html' => 'code',
        'json' => 'code', 'xml' => 'code', 'yml' => 'code', 'yaml' => 'code',
    ];

    /**
     * Get the category based on file extension.
     */
    public function getType(string $extension): string
    {
        $extension = strtolower($extension);
        return $this->extensionMap[$extension] ?? 'unknown';
    }

    /**
     * Determine if the file is previewable based on extension.
     */
    public function isPreviewable(string $extension): bool
    {
        $type = $this->getType($extension);
        $previewableTypes = ['image', 'pdf', 'audio', 'video', 'text', 'code'];
        return in_array($type, $previewableTypes);
    }

    /**
     * Get an SVG icon string based on the category type.
     */
    public function getIconSvg(string $type): string
    {
        return match ($type) {
            'folder' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-15a3 3 0 00-3 3V18a3 3 0 003 3h15zM1.5 10.146V6a3 3 0 013-3h5.379a2.25 2.25 0 011.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 013 3v1.146A4.483 4.483 0 0019.5 9h-15a4.483 4.483 0 00-3 1.146z" /></svg>',
            'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" /></svg>',
            'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" /></svg>',
            'audio' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M19.364 8.936a.75.75 0 010 1.06l-1.591 1.591a.75.75 0 01-1.06-1.06l1.59-1.59a.75.75 0 011.06 0zm-2.828-2.828a.75.75 0 010 1.06l-1.591 1.591a.75.75 0 11-1.06-1.06l1.59-1.59a.75.75 0 011.06 0zm-2.829-2.828a.75.75 0 010 1.061L12.116 5.93a.75.75 0 11-1.06-1.06l1.59-1.591a.75.75 0 011.061 0zm-4.414 4.414A5.25 5.25 0 007.5 12.5v4.5A2.25 2.25 0 015.25 19.25h-.375a.375.375 0 01-.375-.375v-13.5a.375.375 0 01.375-.375H5.25a2.25 2.25 0 012.25 2.25v2.25h.375a.75.75 0 00.75-.75V7.5a3.75 3.75 0 117.5 0v3a.75.75 0 00.75.75h.375A2.25 2.25 0 0119.5 13.5v1.875a.375.375 0 01-.375.375H18.75a2.25 2.25 0 01-2.25-2.25V7.5a6.75 6.75 0 10-13.5 0v11.625A1.875 1.875 0 004.875 21h1.5A3.75 3.75 0 0010.125 17.25v-4.5a3.75 3.75 0 10-1.875-6.936v.936z" clip-rule="evenodd" /></svg>',
            'video' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 4.5a3 3 0 00-3 3v9a3 3 0 003 3h8.25a3 3 0 003-3v-9a3 3 0 00-3-3H4.5zM19.94 6.873a.75.75 0 00-1.19-.623l-3.5 2.45v6.598l3.5 2.45a.75.75 0 001.19-.623V6.873z" /></svg>',
            'document', 'spreadsheet', 'presentation', 'text', 'code' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" /></svg>',
            'archive' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm.53 5.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 101.06 1.06l1.72-1.72v5.69a.75.75 0 001.5 0v-5.69l1.72 1.72a.75.75 0 101.06-1.06l-3-3z" clip-rule="evenodd" /></svg>',
            default => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z" clip-rule="evenodd" /><path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" /></svg>',
        };
    }
}
