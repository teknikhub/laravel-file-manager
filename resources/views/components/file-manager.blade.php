<style>
    .fm-app .fm-item.selected,
    .fm-app .fm-item.selected > div,
    .fm-app .fm-item.selected .fm-file-info,
    .fm-app .fm-item.selected .fm-file-meta,
    .fm-app .fm-item.selected .fm-file-size {
        background-color: #e6f2ff !important;
        border-color: #b3d4ff !important;
    }
</style>
<div class="teknikhub-file-manager-wrapper h-100 fm-app" id="teknikhub-file-manager-app">

    <div class="fm-fmg-body">
        @php
            $disks = config('teknikhub-file-manager.disks', ['public']);
            $currentDisk = request()->query('disk', !empty($disks) ? $disks[0] : config('teknikhub-file-manager.default_disk', 'public'));
        @endphp
        <div class="fm-fmg-body-head d-none d-lg-flex align-items-center justify-content-between p-3 bg-white border-bottom shadow-sm rounded-3 mb-3 gap-3">
            <div class="d-flex align-items-stretch gap-2 flex-grow-1" style="max-width: 600px; height: 38px;">
                @if(count($disks) > 1)
                    <div class="dropdown flex-shrink-0 h-100">
                        <button class="btn btn-light border fw-semibold shadow-sm dropdown-toggle rounded-3 px-3 h-100 d-flex align-items-center justify-content-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #fff !important;" id="fm-disk-dropdown-btn">
                            <em class="icon bi bi-hdd text-primary"></em> <span>{{ ucfirst($currentDisk) }}</span>
                        </button>
                        <ul class="dropdown-menu shadow-sm border-0" style="background-color: #fff !important;">
                            @foreach($disks as $diskName)
                                <li>
                                    <a class="dropdown-item py-2 fw-medium fm-disk-selector {{ $diskName === $currentDisk ? 'bg-light text-primary' : '' }}" href="#" data-disk="{{ $diskName }}">
                                        {{ ucfirst($diskName) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="fm-fmg-search flex-grow-1 d-flex align-items-center bg-light rounded-3 px-3 h-100 border">
                    <em class="icon bi bi-search text-muted me-2"></em>
                    <input type="text" id="fm-search-input" class="form-control border-0 bg-transparent shadow-none p-0 h-100" placeholder="Search files, folders" style="outline: none; box-shadow: none;">
                </div>
            </div>
            <div class="fm-fmg-actions d-flex align-items-center gap-2">
                <ul class="fm-block-tools g-3 m-0 p-0 d-flex gap-2 list-unstyled">
                    <li>
                        <a href="#fm-modal-new-folder" data-bs-toggle="modal" class="btn btn-light border fw-semibold shadow-sm rounded-3 px-3" style="background-color: #fff !important;">
                            <em class="icon bi bi-folder-plus text-primary"></em> <span>Create</span>
                        </a>
                    </li>
                    <li>
                        <a href="#file-upload" class="btn btn-primary fw-semibold shadow-sm rounded-3 px-3" data-bs-toggle="modal">
                            <em class="icon bi bi-cloud-upload"></em> <span>Upload</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="modal fade" id="fm-modal-preview" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 pb-0" style="background-color: #fff !important;">
                        <h5 class="modal-title fw-bold text-dark" id="fm-preview-title">Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-3" id="fm-preview-body" style="background-color: #fff !important;">
                        <!-- Preview content will be injected here -->
                    </div>
                    <div class="modal-footer bg-light border-top justify-content-center gap-2 pb-3" id="fm-preview-footer">
                        <button type="button" class="btn btn-sm btn-light border fw-semibold shadow-sm fm-preview-action-btn" data-action="rename">Rename</button>
                        <button type="button" class="btn btn-sm btn-light border fw-semibold shadow-sm fm-preview-action-btn" data-action="copy">Copy</button>
                        <button type="button" class="btn btn-sm btn-light border fw-semibold shadow-sm fm-preview-action-btn" data-action="cut">Move</button>
                        <button type="button" class="btn btn-sm btn-light border fw-semibold shadow-sm fm-preview-action-btn" data-action="copy-url">Copy URL</button>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold shadow-sm fm-preview-action-btn" data-action="download">Download</button>
                        <button type="button" class="btn btn-sm fw-semibold shadow-sm fm-preview-action-btn" style="background-color: #dc3545 !important; color: white !important; border: none;" data-action="delete">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="fm-fmg-body-content" data-simplebar>
            <div class="fm-block-head fm-block-head-sm">
                <div class="fm-block-between position-relative">
                    <div class="fm-block-head-content">
                        <h3 class="fm-block-title page-title">Files</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb fm-breadcrumb mb-0" id="fm-breadcrumb-list"
                                style="font-size: 0.85rem;"></ol>
                        </nav>
                    </div>
                    <div class="fm-block-head-content">
                        <ul class="fm-block-tools g-1">
                            <li class="d-lg-none"><a href="#"
                                    class="btn btn-trigger btn-icon search-toggle toggle-search"
                                    data-target="search"><em class="icon bi bi-search"></em></a></li>
                            <li class="d-lg-none">
                                <div class="dropdown"><a href="#" class="btn btn-trigger btn-icon"
                                        data-bs-toggle="dropdown"><em class="icon bi bi-grid"></em></a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="lifm-list-opt no-bdr nav m-0 flex-column" role="tablist">
                                            <li><a data-bs-toggle="tab" href="#file-grid-view" class="active"
                                                    aria-selected="true" role="tab"><em
                                                        class="icon bi bi-grid"></em><span>Grid View</span></a></li>
                                            <li><a data-bs-toggle="tab" href="#file-list-view" aria-selected="false"
                                                    tabindex="-1" role="tab"><em
                                                        class="icon bi bi-list-ul"></em><span>List View</span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <li class="d-lg-none">
                                <div class="dropdown"><a href="#" class="btn btn-trigger btn-icon"
                                        data-bs-toggle="dropdown"><em class="icon bi bi-plus-lg"></em></a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <ul class="lifm-list-opt no-bdr">
                                            <li><a href="#file-upload" data-bs-toggle="modal"><em
                                                        class="icon bi bi-cloud-upload"></em><span>Upload
                                                        File</span></a></li>
                                            <li><a href="#"><em class="icon bi bi-folder-plus"></em><span>Create
                                                        Folder</span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="search-wrap px-2 d-lg-none" data-search="search">
                        <div class="search-content"><a href="#" class="search-back btn btn-icon toggle-search"
                                data-target="search"><em class="icon bi bi-arrow-left"></em></a><input type="text"
                                class="form-control border-transparent form-focus-none"
                                placeholder="Search files, folders"><button id="fm-btn-search"
                                class="search-submit btn btn-icon"><em class="icon bi bi-search"></em></button></div>
                    </div>
                    <div class="fm-block-head-content d-none d-lg-block">
                        <ul class="fm-block-tools g-3 nav" role="tablist">
                            <li><a data-bs-toggle="tab" href="#file-grid-view" class="fm-switch-icon active"
                                    aria-selected="true" role="tab"><em class="icon bi bi-grid"></em></a></li>
                            <li><a data-bs-toggle="tab" href="#file-list-view" class="fm-switch-icon"
                                    aria-selected="false" role="tab" tabindex="-1"><em
                                        class="icon bi bi-list-ul"></em></a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Loading / Empty / Selection -->
            <div id="fm-loading" class="text-center py-5" style="display:none;">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading files...</div>
            </div>
            <div id="fm-empty" class="text-center py-5" style="display:none;">
                <div class="text-muted mb-2">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                    </svg>
                </div>
                <div class="text-muted">No files found.</div>
            </div>
            <div id="fm-selection-actions"
                class="d-none justify-content-between align-items-center bg-light p-2 mb-3 rounded border">
                <div class="d-flex align-items-center"><strong id="fm-selection-count">0</strong>&nbsp;selected <button class="btn-close ms-2" id="fm-btn-clear-selection" style="font-size: 0.65rem;" title="Clear selection"></button></div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-light border fw-semibold shadow-sm fm-action-btn" data-action="rename">Rename</button>
                    <button class="btn btn-sm btn-light border fw-semibold shadow-sm fm-action-btn" data-action="copy">Copy</button>
                    <button class="btn btn-sm btn-light border fw-semibold shadow-sm fm-action-btn" data-action="cut">Move</button>
                    <button class="btn btn-sm btn-primary fw-semibold shadow-sm fm-action-btn"
                        data-action="download">Download</button>
                    <button class="btn btn-sm fw-semibold shadow-sm fm-action-btn" style="background-color: #dc3545 !important; color: white !important; border: none;" data-action="delete">Delete</button>
                </div>
            </div>
            <div id="fm-clipboard-bar"
                class="d-none justify-content-between align-items-center bg-info bg-opacity-10 p-2 mb-3 rounded border border-info">
                <div class="text-info"><strong id="fm-clipboard-count">0</strong> item(s) in clipboard</div>
                <div>
                    <button class="btn btn-sm btn-outline-info me-2 fw-bold" id="fm-btn-clear-clipboard" style="display: inline-block !important;">Clear</button>
                    <button class="btn btn-sm btn-info text-white fw-bold shadow-sm" id="fm-btn-paste" style="display: inline-block !important; background-color: #0dcaf0 !important; border: none !important;">Paste Here</button>
                </div>
            </div>
            <div class="tab-content">
                <div class="tab-pane active show" id="file-grid-view" role="tabpanel">
                    <div class="fm-files fm-files-view-grid">

                        <div class="fm-files-list" id="fm-item-container-grid"></div>
                    </div>
                </div>
                <div class="tab-pane" id="file-list-view" role="tabpanel">
                    <div class="fm-files fm-files-view-list">
                        <div class="fm-files-head">
                            <div class="fm-file-item">
                                <div class="fm-file-info">
                                    <div class="tb-head">Name</div>
                                </div>
                            </div>
                        </div>
                        <div class="fm-files-list" id="fm-item-container-list"></div>
                    </div>
                </div>
            </div>


            <!-- Upload Input -->
            <input type="file" id="fm-upload-input" class="d-none" multiple>

            <!-- Modals (Outside simplebar) -->
            <div class="modal fade" id="fm-modal-new-folder" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-dark">Create New Folder</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="fm-new-folder-name" class="form-label text-muted fw-semibold mb-2">Folder Name</label>
                                <input type="text" class="form-control" id="fm-new-folder-name" placeholder="e.g. Documents">
                                <div id="fm-new-folder-error" class="text-danger mt-2 fw-semibold" style="display: none; font-size: 0.85rem;"></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary fw-semibold px-4 shadow-sm" id="fm-btn-create-folder-submit">Create Folder</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="fm-modal-rename" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold text-dark">Rename Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="fm-rename-input" class="form-label text-muted fw-semibold mb-2">New Name</label>
                                <input type="text" class="form-control" id="fm-rename-input" placeholder="Enter new name">
                                <div id="fm-rename-error" class="text-danger mt-2 fw-semibold" style="display: none; font-size: 0.85rem;"></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary fw-semibold px-4 shadow-sm" id="fm-btn-rename-submit">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="fm-modal-delete" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-body text-center p-4">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="text-danger mb-3 mt-2 d-inline-flex justify-content-center align-items-center" style="width: 60px; height: 60px; border-radius: 50%; background-color: rgba(220, 53, 69, 0.1);">
                                <svg width="30" height="30" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                </svg>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Confirm Delete</h5>
                            <p class="text-muted mb-4">Are you sure you want to delete <strong id="fm-delete-count" class="text-dark"></strong> item(s)? This action cannot be undone.</p>
                            <div class="d-flex justify-content-center gap-3 w-100">
                                <button type="button" class="btn btn-light fw-semibold" style="min-width: 120px;" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn fw-semibold shadow-sm" style="background-color: #dc3545 !important; color: white !important; min-width: 120px;" id="fm-btn-delete-submit">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="file-upload" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom-0 pb-0 pt-4 px-4" style="background-color: #fff !important;">
                            <h5 class="modal-title fw-bold text-dark">Upload Files</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div id="fm-dropzone" class="border rounded-4 p-5 text-center bg-light position-relative"
                                style="border: 2px dashed #a8b8d8 !important; cursor: pointer; transition: all 0.2s ease;">
                                <div class="text-primary mb-3 d-inline-flex justify-content-center align-items-center" style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(13, 110, 253, 0.1);">
                                    <svg width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
                                        <path
                                            d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                                        <path
                                            d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z" />
                                    </svg>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">Drag and drop your files here</h5>
                                <p class="text-muted mb-0">or <span class="text-primary fw-semibold text-decoration-none" id="fm-dropzone-browse">browse from your computer</span></p>
                            </div>

                            <div class="mt-4" id="fm-upload-list-container" style="display: none;">
                                <h6 class="fw-bold mb-3 fs-7 text-muted text-uppercase tracking-wider">Uploading Files...</h6>
                                <div id="fm-upload-list" class="d-flex flex-column gap-3">
                                    <!-- Progress items injected here -->
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 pb-4 px-4">
                            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary fw-semibold px-4 shadow-sm" id="fm-btn-add-files">Select Files</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>