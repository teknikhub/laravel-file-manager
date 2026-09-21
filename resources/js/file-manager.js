(function() {
    // Basic Configuration from window
    const config = window.TeknikHubFileManagerConfig || {};
    const baseUrl = config.baseUrl || '';
    let currentDisk = config.disk || 'public';
    let currentPath = '';
    
    // State
    let items = [];
    let selectedPaths = [];
    let clipboard = { action: null, paths: [], sourcePath: '' };
    let currentView = 'grid'; // grid or list
    let currentPreviewItem = null;

    // Publish to global window for picker to access
    window.TeknikHubFM = {
        selectedPaths: [],
        getSelection: function() { return this.selectedPaths; }
    };

    // DOM Elements
    const el = {
        containerGrid: document.getElementById('fm-item-container-grid'),
        containerList: document.getElementById('fm-item-container-list'),
        breadcrumb: document.getElementById('fm-breadcrumb-list'),
        loading: document.getElementById('fm-loading'),
        empty: document.getElementById('fm-empty'),
        selectionBar: document.getElementById('fm-selection-actions'),
        selectionCount: document.getElementById('fm-selection-count'),
        clipboardBar: document.getElementById('fm-clipboard-bar'),
        clipboardCount: document.getElementById('fm-clipboard-count'),
        btnBack: document.getElementById('fm-btn-back'),
        btnRefresh: document.getElementById('fm-btn-refresh'),
        btnUpload: document.getElementById('fm-btn-upload'),
        uploadInput: document.getElementById('fm-upload-input'),
        btnNewFolder: document.getElementById('fm-btn-new-folder'),
        viewToggles: document.querySelectorAll('#fm-view-mode-toggle button'),
        searchInput: document.getElementById('fm-search-input'),
        btnSearch: document.getElementById('fm-btn-search'),
        // Modals
        modalNewFolder: document.getElementById('fm-modal-new-folder'),
        modalRename: document.getElementById('fm-modal-rename'),
        modalPreview: document.getElementById('fm-modal-preview'),
        modalDelete: document.getElementById('fm-modal-delete')
    };

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const fetchItems = async (path = '', search = '') => {
        el.loading.style.display = 'block';
        el.empty.style.display = 'none';
        if(el.containerGrid) el.containerGrid.innerHTML = ''; if(el.containerList) el.containerList.innerHTML = '';
        
        
        
        try {
            const res = await fetch(`${baseUrl}/browse?disk=${currentDisk}&path=${encodeURIComponent(path)}&search=${encodeURIComponent(search)}&_t=${new Date().getTime()}`);
            const json = await res.json();
            
            if (json.result === 'success') {
                if (!search) {
                    currentPath = path;
                }
                items = json.data;
                selectedPaths = [];
                window.TeknikHubFM.selectedPaths = [];
                
                updateBreadcrumb(search);
                renderItems();
                updateSelectionState();
                
                if (el.btnBack) el.btnBack.disabled = (currentPath === '');
            } else {
                alert(json.message || 'Error loading directory');
            }
        } catch (e) {
            console.error(e);
            alert('Network error: ' + e.message);
        } finally {
            el.loading.style.display = 'none';
        }
    };

    const formatSize = (bytes) => {
        if (!bytes) return '0 B';
        const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    
    const renderItems = () => {
        el.containerGrid.innerHTML = '';
        el.containerList.innerHTML = '';

        if (items.length === 0) {
            el.empty.style.display = 'block';
            return;
        }

        // Sort folders first
        items.sort((a, b) => {
            if (a.type === b.type) return a.name.localeCompare(b.name);
            return a.type === 'directory' ? -1 : 1;
        });

        items.forEach(item => {
            let iconHtml = item.icon || '';
            if (item.type === 'directory') {
                iconHtml = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-15a3 3 0 00-3 3V18a3 3 0 003 3h15zM1.5 10.146V6a3 3 0 013-3h5.379a2.25 2.25 0 011.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 013 3v1.146A4.483 4.483 0 0019.5 9h-15a4.483 4.483 0 00-3 1.146z" /></svg>`;
            } else if (item.thumbnail) {
                iconHtml = `<img src="${baseUrl}/preview?disk=${currentDisk}&path=${encodeURIComponent(item.thumbnail)}" alt="thumbnail">`;
            } else if (item.previewable && item.icon === 'image') {
                iconHtml = `<img src="${baseUrl}/preview?disk=${currentDisk}&path=${encodeURIComponent(item.path)}" alt="preview">`;
            }

            const dropdownHtml = `
                <div class="dropdown">
                    <a href="" class="dropdown-toggle btn btn-sm btn-icon btn-trigger" data-bs-toggle="dropdown"><em class="icon bi bi-three-dots"></em></a>
                    <div class="dropdown-menu dropdown-menu-end shadow border py-2 bg-white" style="background-color: #fff !important; min-width: 160px; z-index: 1055;">
                        ${item.previewable ? `<a class="dropdown-item fm-item-action d-flex align-items-center mb-1" href="#" data-action="preview" data-path="${item.path}"><em class="icon bi bi-eye me-2 text-primary"></em><span>Preview</span></a>` : ''}
                        <a class="dropdown-item fm-item-action d-flex align-items-center mb-1" href="#" data-action="copy" data-path="${item.path}"><em class="icon bi bi-copy me-2 text-secondary"></em><span>Copy</span></a>
                        <a class="dropdown-item fm-item-action d-flex align-items-center mb-1" href="#" data-action="cut" data-path="${item.path}"><em class="icon bi bi-box-arrow-right me-2 text-secondary"></em><span>Move</span></a>
                        <a class="dropdown-item fm-item-action d-flex align-items-center mb-1" href="#" data-action="download" data-path="${item.path}"><em class="icon bi bi-download me-2 text-info"></em><span>Download</span></a>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item fm-item-action d-flex align-items-center mb-1" href="#" data-action="rename" data-path="${item.path}"><em class="icon bi bi-pencil me-2 text-warning"></em><span>Rename</span></a>
                        <a class="dropdown-item fm-item-action text-danger d-flex align-items-center fw-semibold" href="#" data-action="delete" data-path="${item.path}"><em class="icon bi bi-trash me-2"></em><span>Delete</span></a>
                    </div>
                </div>
            `;

            // --- GRID ITEM ---
            const gridItem = document.createElement('div');
            gridItem.className = 'fm-file fm-item';
            gridItem.dataset.path = item.path;
            gridItem.innerHTML = `
                <div class="fm-file-info">
                    <a href="#" class="fm-file-link" data-path="${item.path}">
                        <div class="fm-file-title">
                            <div class="fm-file-icon text-warning">
                                <span class="fm-file-icon-type" style="display:flex; justify-content:center; align-items:center; height:100%;">
                                    ${iconHtml}
                                </span>
                            </div>
                            <div class="fm-file-name">
                                <div class="fm-file-name-text">
                                    <span class="title" title="${item.name}">${item.name}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="fm-file-actions">
                    ${dropdownHtml}
                </div>
            `;

            // --- LIST ITEM ---
            const listItem = document.createElement('div');
            listItem.className = 'fm-file fm-item';
            listItem.dataset.path = item.path;
            listItem.innerHTML = `
                <div class="fm-file-info d-flex align-items-center">
                    <a href="#" class="fm-file-link flex-grow-1" data-path="${item.path}">
                        <div class="fm-file-title">
                            <div class="fm-file-icon text-warning">
                                <span class="fm-file-icon-type" style="display:flex; justify-content:center; align-items:center; height:100%;">
                                    ${iconHtml}
                                </span>
                            </div>
                            <div class="fm-file-name">
                                <div class="fm-file-name-text">
                                    <span class="title" title="${item.name}">${item.name}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="fm-file-meta">
                    <div class="tb-lead">${item.modified || ''}</div>
                </div>
                <div class="fm-file-size">
                    <div class="tb-lead">${item.type === 'file' ? formatSize(item.size) : '--'}</div>
                </div>
                <div class="fm-file-actions">
                    ${dropdownHtml}
                </div>
            `;

            // Shared logic for both nodes
            [gridItem, listItem].forEach(node => {
                node.addEventListener('click', (e) => {
                    if (e.target.closest('.dropdown, .fm-item-action, .custom-checkbox')) return;
                    e.preventDefault();
                    
                    if (e.ctrlKey || e.metaKey) {
                        toggleSelection(item.path, node);
                    } else {
                        clearSelection();
                        toggleSelection(item.path, node);
                    }
                });

                node.addEventListener('dblclick', (e) => {
                    if (e.target.closest('.dropdown, .fm-item-action, .custom-checkbox')) return;
                    e.preventDefault();
                    if (item.type === 'directory') {
                        fetchItems(item.path);
                    } else if (window.opener) {
                        let fileUrl = item.url || (window.location.origin + '/storage/' + item.path);
                        fileUrl = fileUrl.replace(window.location.origin, '');
                        if (fileUrl.startsWith('/public/')) {
                            fileUrl = fileUrl.substring(8); // remove '/public/'
                        } else if (fileUrl.startsWith('/')) {
                            fileUrl = fileUrl.substring(1); // remove leading slash to match 'images/...' format
                        }
                        const event = new CustomEvent('teknikhub-file-manager:file-selected', { detail: { url: fileUrl } });
                        document.dispatchEvent(event);
                    } else if (item.previewable) {
                        previewFile(item);
                    } else {
                        downloadFile(item.path);
                    }
                });

                // Dropdown actions binding
                node.querySelectorAll('.fm-item-action').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const action = e.currentTarget.dataset.action;
                        const path = e.currentTarget.dataset.path;
                        
                        clearSelection();
                        selectedPaths = [path];
                        window.TeknikHubFM.selectedPaths = selectedPaths;
                        updateSelectionState();
                        node.classList.add('selected');

                        if (action === 'delete') {
                            const countEl = document.getElementById('fm-delete-count');
                            if (countEl) countEl.innerText = 1;
                            showModal('#fm-modal-delete');
                        } else if (action === 'copy' || action === 'cut') {
                            clipboard = { action, paths: [path], sourcePath: currentPath };
                            updateClipboardUI();
                        } else if (action === 'rename') {
                            document.getElementById('fm-rename-input').value = path.split('/').pop();
                            document.getElementById('fm-rename-input').dataset.oldPath = path;
                            showModal('#fm-modal-rename');
                        } else if (action === 'download') {
                            downloadFile(path);
                        } else if (action === 'preview') {
                            previewFile(item);
                        } else if (action === 'select') {
                            let fileUrl = item.url || (window.location.origin + '/storage/' + path);
                            fileUrl = fileUrl.replace(window.location.origin, '');
                            if (fileUrl.startsWith('/public/')) {
                                fileUrl = fileUrl.substring(8);
                            } else if (fileUrl.startsWith('/')) {
                                fileUrl = fileUrl.substring(1);
                            }
                            const event = new CustomEvent('teknikhub-file-manager:file-selected', { detail: { url: fileUrl } });
                            document.dispatchEvent(event);
                        }
                    });
                });
            });

            el.containerGrid.appendChild(gridItem);
            el.containerList.appendChild(listItem);
        });
    };
    
    const syncVisualSelection = () => {
        document.querySelectorAll('.fm-item').forEach(node => {
            const path = node.dataset.path;
            if (selectedPaths.includes(path)) {
                node.classList.add('selected');
            } else {
                node.classList.remove('selected');
            }
        });
    };

    const toggleSelection = (path, elNode) => {
        const idx = selectedPaths.indexOf(path);
        if (idx > -1) {
            selectedPaths.splice(idx, 1);
            elNode.classList.remove('selected');
        } else {
            selectedPaths.push(path);
            elNode.classList.add('selected');
        }
        window.TeknikHubFM.selectedPaths = selectedPaths;
        updateSelectionState();
    };

    const clearSelection = () => {
        selectedPaths = [];
        window.TeknikHubFM.selectedPaths = [];
        document.querySelectorAll('.fm-item.selected').forEach(el => el.classList.remove('selected'));
        updateSelectionState();
    };

    const updateSelectionState = () => {
        el.selectionCount.innerText = selectedPaths.length;
        if (selectedPaths.length > 0) {
            el.selectionBar.classList.remove('d-none');
            el.selectionBar.classList.add('d-flex');
            
            // Toggle specific buttons (e.g. rename only for 1 item)
            document.querySelector('[data-action="rename"]').style.display = selectedPaths.length === 1 ? 'inline-block' : 'none';
        } else {
            el.selectionBar.classList.add('d-none');
            el.selectionBar.classList.remove('d-flex');
        }
    };

    const updateBreadcrumb = (searchQuery = '') => {
        el.breadcrumb.innerHTML = '';
        const parts = currentPath.split('/').filter(p => p !== '');
        
        let pathAccumulator = '';
        
        const homeLi = document.createElement('li');
        homeLi.className = 'breadcrumb-item';
        homeLi.innerHTML = `<a href="#" data-path="">Home</a>`;
        el.breadcrumb.appendChild(homeLi);

        parts.forEach((part, index) => {
            pathAccumulator += (index === 0 ? part : '/' + part);
            const li = document.createElement('li');
            li.className = 'breadcrumb-item';
            
            if (index === parts.length - 1 && !searchQuery) {
                li.classList.add('active');
                li.innerText = part;
            } else {
                li.innerHTML = `<a href="#" data-path="${pathAccumulator}">${part}</a>`;
            }
            el.breadcrumb.appendChild(li);
        });

        if (searchQuery) {
            const searchLi = document.createElement('li');
            searchLi.className = 'breadcrumb-item active text-primary';
            searchLi.innerText = `Search: "${searchQuery}"`;
            el.breadcrumb.appendChild(searchLi);
        }

        el.breadcrumb.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                fetchItems(a.dataset.path);
            });
        });
    };

    // Actions
    if (document.getElementById('fm-btn-clear-selection')) {
        document.getElementById('fm-btn-clear-selection').addEventListener('click', clearSelection);
    }
    
    if(el.btnRefresh) el.btnRefresh.addEventListener('click', () => fetchItems(currentPath));
    updateBreadcrumb();
    
    if (el.btnBack) {
        el.btnBack.addEventListener('click', () => {
            const parts = currentPath.split('/');
            parts.pop();
            fetchItems(parts.join('/'));
        });
    }

    if (el.btnRefresh) {
        el.btnRefresh.addEventListener('click', () => fetchItems(currentPath));
    }

    // XHR Upload logic with progress bars
    const handleUploadFiles = (files) => {
        if (!files.length) return;

        const listContainer = document.getElementById('fm-upload-list-container');
        const list = document.getElementById('fm-upload-list');
        listContainer.style.display = 'block';

        Array.from(files).forEach((file, index) => {
            const formData = new FormData();
            formData.append('disk', currentDisk);
            formData.append('path', currentPath);
            formData.append('file[]', file);

            const progressId = 'upload-' + Date.now() + '-' + index;
            
            const itemHtml = `
                <div class="fm-upload-progress-item d-flex align-items-center position-relative border rounded p-2 mb-2" id="${progressId}">
                    <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2" style="font-size: 0.6rem; opacity: 0.6;" aria-label="Close" onclick="this.closest('.fm-upload-progress-item').remove(); if(document.querySelectorAll('.fm-upload-progress-item').length === 0) document.getElementById('fm-upload-list-container').style.display = 'none';"></button>
                    <div class="fm-item-icon text-primary me-3">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
                    </div>
                    <div class="flex-grow-1 overflow-hidden pe-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-truncate fw-bold text-dark" style="font-size: 0.85rem;">${file.name}</span>
                            <span class="text-muted" style="font-size: 0.75rem;" id="${progressId}-text">0%</span>
                        </div>
                        <div class="fm-upload-progress-bar" id="${progressId}-bar" style="width: 0%;"></div>
                    </div>
                </div>
            `;
            list.insertAdjacentHTML('beforeend', itemHtml);

            const bar = document.getElementById(`${progressId}-bar`);
            const text = document.getElementById(`${progressId}-text`);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${baseUrl}/upload`, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    bar.style.width = percent + '%';
                    text.innerText = percent + '%';
                }
            };

            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (json.result === 'success') {
                            bar.classList.add('bg-success');
                            text.innerText = 'Done';
                            fetchItems(currentPath);
                        } else {
                            bar.classList.add('bg-danger');
                            text.innerText = json.message || 'Failed';
                            text.title = json.message || 'Failed';
                        }
                    } catch (e) {
                        bar.classList.add('bg-danger');
                        text.innerText = 'Failed';
                    }
                } else {
                    bar.classList.add('bg-danger');
                    try {
                        const json = JSON.parse(xhr.responseText);
                        text.innerText = json.message || 'Failed';
                        text.title = json.message || 'Failed';
                    } catch (e) {
                        text.innerText = 'Failed (' + xhr.status + ')';
                    }
                }
            };

            xhr.onerror = () => {
                bar.classList.add('bg-danger');
                text.innerText = 'Failed';
            };

            xhr.send(formData);
        });
    };

    const btnAddFiles = document.getElementById('fm-btn-add-files');
    const dropzoneBrowse = document.getElementById('fm-dropzone-browse');
    
    // In case the old upload button is still used
    if(el.btnUpload) el.btnUpload.addEventListener('click', () => el.uploadInput.click());
    
    if (btnAddFiles) btnAddFiles.addEventListener('click', () => el.uploadInput.click());
    if (dropzoneBrowse) dropzoneBrowse.addEventListener('click', (e) => { e.preventDefault(); el.uploadInput.click(); });
    
    if(el.uploadInput) el.uploadInput.addEventListener('change', (e) => {
        handleUploadFiles(e.target.files);
        e.target.value = ''; 
    });

    const dropzone = document.getElementById('fm-dropzone');
    if (dropzone) {
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleUploadFiles(e.dataTransfer.files);
            }
        });
    }

    const uploadModal = document.getElementById('file-upload');
    if (uploadModal) {
        uploadModal.addEventListener('hidden.bs.modal', function () {
            const listContainer = document.getElementById('fm-upload-list-container');
            const list = document.getElementById('fm-upload-list');
            if (listContainer) listContainer.style.display = 'none';
            if (list) list.innerHTML = '';
        });
    }

    // View Toggles
    el.viewToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            el.viewToggles.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.mode;
            el.container.dataset.view = currentView;
            renderItems();
        });
    });

    // Search
    if(el.searchInput) {
        let searchTimeout;
        el.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            clearTimeout(searchTimeout);
            
            // If empty, fetch immediately to reset
            if (query.length === 0) {
                fetchItems(currentPath, '');
                return;
            }
            
            // If >= 3 chars, debounce and search
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    fetchItems(currentPath, query);
                }, 400); // 400ms debounce
            }
        });
        
        el.searchInput.addEventListener('keyup', (e) => {
            // Also allow Enter to force search instantly regardless of length
            if(e.key === 'Enter') {
                clearTimeout(searchTimeout);
                fetchItems(currentPath, el.searchInput.value.trim());
            }
        });
    }

    // Modal Helpers (Vanilla Bootstrap integration)
    const showModal = (id) => {
        const el = document.querySelector(id);
        if (el) {
            const m = bootstrap.Modal.getOrCreateInstance(el);
            m.show();
        }
    };
    const hideModal = (id) => {
        const el = document.querySelector(id);
        if (el) {
            const m = bootstrap.Modal.getInstance(el);
            if(m) m.hide();
        }
    };

    // New Folder
    document.querySelector('a[href="#fm-modal-new-folder"]')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('fm-new-folder-name').value = '';
        const errorEl = document.getElementById('fm-new-folder-error');
        if (errorEl) errorEl.style.display = 'none';
        showModal('#fm-modal-new-folder');
    });

    document.querySelectorAll('.fm-disk-selector').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const newDisk = e.currentTarget.dataset.disk;
            if (newDisk === currentDisk) return;
            
            document.querySelectorAll('.fm-disk-selector').forEach(i => i.classList.remove('bg-light', 'text-primary'));
            e.currentTarget.classList.add('bg-light', 'text-primary');
            
            const btnSpan = document.querySelector('#fm-disk-dropdown-btn span');
            if (btnSpan) btnSpan.innerText = newDisk.charAt(0).toUpperCase() + newDisk.slice(1);
            
            currentDisk = newDisk;
            currentPath = '';
            fetchItems('');
        });
    });

    document.getElementById('fm-btn-create-folder-submit')?.addEventListener('click', async () => {
        const name = document.getElementById('fm-new-folder-name').value;
        const errorEl = document.getElementById('fm-new-folder-error');
        if (errorEl) errorEl.style.display = 'none';

        if (!name) return;
        
        try {
            const res = await fetch(`${baseUrl}/folder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ disk: currentDisk, path: currentPath, name })
            });
            const json = await res.json();
            if (json.result === 'success') {
                hideModal('#fm-modal-new-folder');
                fetchItems(currentPath);
            } else {
                if (errorEl) {
                    errorEl.innerText = json.message || 'Failed to create folder';
                    errorEl.style.display = 'block';
                } else {
                    alert(json.message || 'Failed to create folder');
                }
            }
        } catch(e) {
            if (errorEl) {
                errorEl.innerText = 'Network error. Failed to create folder.';
                errorEl.style.display = 'block';
            } else {
                alert('Failed');
            }
        }
    });

    // Action Bar Actions
    document.querySelectorAll('.fm-action-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            if (selectedPaths.length === 0) return;

            if (action === 'delete') {
                const countEl = document.getElementById('fm-delete-count');
                if (countEl) countEl.innerText = selectedPaths.length;
                showModal('#fm-modal-delete');
            } else if (action === 'copy' || action === 'cut') {
                clipboard = { action, paths: [...selectedPaths], sourcePath: currentPath };
                updateClipboardUI();
                clearSelection();
            } else if (action === 'rename' && selectedPaths.length === 1) {
                const oldPath = selectedPaths[0];
                document.getElementById('fm-rename-input').value = oldPath.split('/').pop();
                document.getElementById('fm-rename-input').dataset.oldPath = oldPath;
                const renameErrorEl = document.getElementById('fm-rename-error');
                if (renameErrorEl) renameErrorEl.style.display = 'none';
                showModal('#fm-modal-rename');
            } else if (action === 'download') {
                selectedPaths.forEach(p => downloadFile(p));
            }
        });
    });

    const updateClipboardUI = () => {
        if (clipboard.paths.length > 0) {
            el.clipboardBar.classList.remove('d-none');
            el.clipboardBar.classList.add('d-flex');
            el.clipboardCount.innerText = clipboard.paths.length;
        } else {
            el.clipboardBar.classList.add('d-none');
            el.clipboardBar.classList.remove('d-flex');
        }
    };

    document.getElementById('fm-btn-clear-clipboard')?.addEventListener('click', () => {
        clipboard = { action: null, paths: [], sourcePath: '' };
        updateClipboardUI();
    });

    document.getElementById('fm-btn-paste')?.addEventListener('click', async () => {
        if (clipboard.paths.length === 0) return;

        try {
            const res = await fetch(`${baseUrl}/paste`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ 
                    disk: currentDisk, 
                    paths: clipboard.paths, 
                    destination: currentPath,
                    isMove: clipboard.action === 'cut'
                })
            });
            const json = await res.json();
            if (json.result === 'success') {
                clipboard = { action: null, paths: [], sourcePath: '' };
                updateClipboardUI();
                fetchItems(currentPath);
            } else {
                alert(json.message || 'Paste failed');
            }
        } catch(e) {
            alert('Failed');
        }
    });

    const deleteItems = async (paths) => {
        try {
            const res = await fetch(`${baseUrl}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ disk: currentDisk, paths })
            });
            const json = await res.json();
            if (json.result === 'success') {
                clearSelection();
                fetchItems(currentPath);
            } else {
                alert(json.message || 'Delete failed');
            }
        } catch(e) {
            alert('Failed');
        }
    };

    document.getElementById('fm-btn-delete-submit')?.addEventListener('click', () => {
        hideModal('#fm-modal-delete');
        deleteItems(selectedPaths);
    });

    document.getElementById('fm-btn-rename-submit')?.addEventListener('click', async () => {
        if (selectedPaths.length === 0) return;
        const newName = document.getElementById('fm-rename-input').value;
        const errorEl = document.getElementById('fm-rename-error');
        if (errorEl) errorEl.style.display = 'none';

        if (!newName) return;
        
        try {
            const res = await fetch(`${baseUrl}/rename`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    disk: currentDisk,
                    path: selectedPaths[0],
                    newName: newName
                })
            });
            const json = await res.json();
            if (json.result === 'success') {
                hideModal('#fm-modal-rename');
                clearSelection();
                fetchItems(currentPath);
            } else {
                if (errorEl) {
                    errorEl.innerText = json.message || 'Failed to rename';
                    errorEl.style.display = 'block';
                } else {
                    alert(json.message || 'Failed to rename');
                }
            }
        } catch(e) {
            if (errorEl) {
                errorEl.innerText = 'Network error. Failed to rename.';
                errorEl.style.display = 'block';
            } else {
                alert('Failed');
            }
        }
    });

    const downloadFile = (path) => {
        window.open(`${baseUrl}/download?disk=${currentDisk}&path=${encodeURIComponent(path)}`, '_blank');
    };

    const previewFile = (item) => {
        currentPreviewItem = item;
        const body = document.getElementById('fm-preview-body');
        document.getElementById('fm-preview-title').innerText = item.name;
        body.innerHTML = '';
        
        const previewUrl = `${baseUrl}/preview?disk=${currentDisk}&path=${encodeURIComponent(item.path)}`;

        if (item.icon === 'image') {
            body.innerHTML = `<img src="${previewUrl}" class="img-fluid" style="max-height:80vh;">`;
        } else if (item.icon === 'audio') {
            body.innerHTML = `<audio controls src="${previewUrl}" class="w-100 px-4"></audio>`;
        } else if (item.icon === 'video') {
            body.innerHTML = `<video controls src="${previewUrl}" class="w-100" style="max-height:80vh;"></video>`;
        } else {
            body.innerHTML = `<p class="p-4">Cannot preview this file directly. <a href="${baseUrl}/download?disk=${currentDisk}&path=${encodeURIComponent(item.path)}" target="_blank" class="btn btn-primary">Download</a></p>`;
        }

        const footer = document.getElementById('fm-preview-footer');
        if (footer) {
            footer.style.display = 'flex';
        }

        showModal('#fm-modal-preview');
    };

    // Preview Actions
    document.querySelectorAll('.fm-preview-action-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!currentPreviewItem) return;
            const action = btn.dataset.action;
            const path = currentPreviewItem.path;
            
            // Set selection to current preview item for other logic
            selectedPaths = [path];
            window.TeknikHubFM.selectedPaths = selectedPaths;
            updateSelectionState();

            if (action === 'delete') {
                const countEl = document.getElementById('fm-delete-count');
                if (countEl) countEl.innerText = 1;
                showModal('#fm-modal-delete');
                hideModal('#fm-modal-preview');
            } else if (action === 'copy' || action === 'cut') {
                clipboard = { action: action, paths: [path], sourcePath: currentPath };
                updateClipboardUI();
                hideModal('#fm-modal-preview');
            } else if (action === 'rename') {
                document.getElementById('fm-rename-input').value = path.split('/').pop();
                document.getElementById('fm-rename-input').dataset.oldPath = path;
                showModal('#fm-modal-rename');
                hideModal('#fm-modal-preview');
            } else if (action === 'download') {
                downloadFile(path);
            } else if (action === 'copy-url') {
                let fileUrl = currentPreviewItem.url || (window.location.origin + '/storage/' + path);
                if (fileUrl.startsWith('/')) {
                    fileUrl = window.location.origin + fileUrl;
                } else if (!fileUrl.startsWith('http')) {
                    fileUrl = window.location.origin + '/' + fileUrl;
                }
                
                const showCopied = () => {
                    const btnText = btn.innerHTML;
                    btn.innerText = 'Copied!';
                    setTimeout(() => { btn.innerHTML = btnText; }, 2000);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(fileUrl).then(showCopied).catch(e => console.error(e));
                } else {
                    const textArea = document.createElement("textarea");
                    textArea.value = fileUrl;
                    textArea.style.position = "fixed";
                    textArea.style.top = "0";
                    textArea.style.left = "0";
                    textArea.style.width = "2em";
                    textArea.style.height = "2em";
                    textArea.style.padding = "0";
                    textArea.style.border = "none";
                    textArea.style.outline = "none";
                    textArea.style.boxShadow = "none";
                    textArea.style.background = "transparent";
                    
                    btn.parentNode.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        showCopied();
                    } catch (error) {
                        console.error('Failed to copy', error);
                    } finally {
                        textArea.remove();
                    }
                }
            }
        });
    });

    // Initial Load
    if (el.containerGrid) {
        fetchItems('');
    }

})();
