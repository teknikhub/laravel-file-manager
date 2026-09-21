<?php

namespace TeknikHub\FileManager\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class FileManager extends Component
{
    public string $disk;
    
    /**
     * Create a new component instance.
     */
    public function __construct(string $disk = '')
    {
        $this->disk = $disk ?: config('teknikhub-file-manager.default_disk', 'public');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('teknikhub-file-manager::components.file-manager');
    }
}
