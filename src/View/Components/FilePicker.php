<?php

namespace TeknikHub\FileManager\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class FilePicker extends Component
{
    public string $name;
    public string $label;
    public string $disk;
    public ?string $value;
    public bool $multiple;
    public string $id;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $name,
        string $label = 'Select File',
        string $disk = '',
        ?string $value = null,
        bool $multiple = false,
        ?string $id = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->disk = $disk ?: config('teknikhub-file-manager.default_disk', 'public');
        $this->value = $value;
        $this->multiple = $multiple;
        $this->id = $id ?? 'picker_' . md5($name . microtime());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('teknikhub-file-manager::components.file-picker');
    }
}
