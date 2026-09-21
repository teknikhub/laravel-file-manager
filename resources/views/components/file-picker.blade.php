<div>
    @php
        $toggleAttr = 'data-bs-toggle="modal" data-bs-target="#fm-picker-modal-'.$id.'"';
    @endphp

    <label for="{{ $id }}_input" class="form-label">{{ $label }}</label>
    <div class="input-group">
        <input type="text" 
               class="form-control" 
               id="{{ $id }}_input" 
               name="{{ $name }}" 
               value="{{ old($name, $value) }}" 
               readonly>
        <button class="btn btn-outline-secondary" type="button" {!! $toggleAttr !!}>Browse</button>
        <button class="btn btn-outline-danger" type="button" onclick="document.getElementById('{{ $id }}_input').value=''">Clear</button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="fm-picker-modal-{{ $id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="height: 85vh;">
                <div class="modal-header">
                    <h5 class="modal-title">Select File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="fm-picker-container-{{ $id }}">
                    <!-- Iframe or dynamically loaded component -->
                    <iframe src="{{ route('teknikhub.file-manager.index', ['disk' => $disk]) }}" 
                            class="w-100 h-100 border-0" 
                            id="fm-picker-iframe-{{ $id }}"></iframe>
                </div>
                <div class="modal-footer">
                    <span class="text-muted me-auto d-none" id="fm-picker-selected-text-{{ $id }}">No file selected</span>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="fm-btn-select-{{ $id }}">Select</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectBtn = document.getElementById('fm-btn-select-{{ $id }}');
            const iframe = document.getElementById('fm-picker-iframe-{{ $id }}');
            const input = document.getElementById('{{ $id }}_input');

            selectBtn.addEventListener('click', function() {
                try {
                    const fmWindow = iframe.contentWindow;
                    // Our custom JS in the iframe sets this global variable when items are selected
                    if (fmWindow.TeknikHubFM && fmWindow.TeknikHubFM.selectedPaths && fmWindow.TeknikHubFM.selectedPaths.length > 0) {
                        const selectedPath = fmWindow.TeknikHubFM.selectedPaths[0]; // For single selection
                        input.value = selectedPath;
                        
                        // Close modal
                        const modalEl = document.getElementById('fm-picker-modal-{{ $id }}');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if(modal) modal.hide();
                    } else {
                        alert('Please select a file.');
                    }
                } catch(e) {
                    console.error("Cannot access iframe content", e);
                }
            });
        });
    </script>
</div>
