{{-- Province Filter Selector Component --}}
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="provinceFilterDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i data-feather="map-pin"></i>
        <span class="ms-1 d-none d-md-inline-block" id="provinceFilterLabel">All Provinces</span>
        <span class="badge bg-primary ms-1" id="provinceFilterBadge">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="provinceFilterDropdown" style="min-width: 300px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0">Filter by Province</h6>
            <button type="button" class="btn btn-sm btn-link text-primary p-0" id="selectAllProvinces">
                Select All
            </button>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="provinceFilterAll" name="province_filter[]" value="all" checked>
                <label class="form-check-label" for="provinceFilterAll">
                    <strong>All Provinces</strong>
                </label>
            </div>
        </div>
        <hr class="my-2">
        <div id="provinceFilterList" style="max-height: 300px; overflow-y: auto;">
            {{-- Provinces will be loaded via AJAX --}}
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" id="clearProvinceFilter">
                Clear
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="applyProvinceFilter">
                Apply Filter
            </button>
        </div>
        <div class="mt-2">
            <small class="text-muted" id="provinceFilterInfo">Showing data from all provinces</small>
        </div>
    </div>
</li>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceFilterDropdown = document.getElementById('provinceFilterDropdown');
    const provinceFilterList = document.getElementById('provinceFilterList');
    const provinceFilterAll = document.getElementById('provinceFilterAll');
    const applyBtn = document.getElementById('applyProvinceFilter');
    const clearBtn = document.getElementById('clearProvinceFilter');
    const selectAllBtn = document.getElementById('selectAllProvinces');
    const filterLabel = document.getElementById('provinceFilterLabel');
    const filterBadge = document.getElementById('provinceFilterBadge');
    const filterInfo = document.getElementById('provinceFilterInfo');

    let managedProvinces = [];
    let selectedProvinceIds = [];

    // Load provinces on dropdown open
    provinceFilterDropdown.addEventListener('show.bs.dropdown', function() {
        loadProvinces();
    });

    // Load provinces and current filter
    function loadProvinces() {
        fetch('{{ route("province.filter.get") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                managedProvinces = data.managed_provinces;
                selectedProvinceIds = data.selected_ids;

                renderProvinceList();
                updateUI();
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
        });
    }

    // Render province checkboxes
    function renderProvinceList() {
        if (managedProvinces.length === 0) {
            provinceFilterList.innerHTML = '<p class="text-muted text-center py-2">No provinces available</p>';
            return;
        }

        const allSelected = selectedProvinceIds.length === managedProvinces.length;
        provinceFilterAll.checked = allSelected || selectedProvinceIds.length === 0;

        provinceFilterList.innerHTML = managedProvinces.map(province => {
            const provinceId = parseInt(province.id);
            const isSelected = selectedProvinceIds.some(id => parseInt(id) === provinceId);
            return `
            <div class="form-check mb-2">
                <input class="form-check-input province-checkbox" type="checkbox"
                       id="province_${province.id}"
                       name="province_filter[]"
                       value="${province.id}"
                       ${isSelected ? 'checked' : ''}>
                <label class="form-check-label" for="province_${province.id}">
                    ${province.name}
                </label>
            </div>
        `;
        }).join('');
    }

    // Handle "All Provinces" checkbox
    provinceFilterAll.addEventListener('change', function() {
        const checkboxes = provinceFilterList.querySelectorAll('.province-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // Handle individual province checkboxes
    provinceFilterList.addEventListener('change', function(e) {
        if (e.target.classList.contains('province-checkbox')) {
            const allChecked = Array.from(provinceFilterList.querySelectorAll('.province-checkbox'))
                .every(cb => cb.checked);
            provinceFilterAll.checked = allChecked;
        }
    });

    // Select All button
    selectAllBtn.addEventListener('click', function() {
        provinceFilterAll.checked = true;
        provinceFilterList.querySelectorAll('.province-checkbox').forEach(cb => {
            cb.checked = true;
        });
    });

    // Apply filter
    applyBtn.addEventListener('click', function() {
        const selected = [];

        if (provinceFilterAll.checked) {
            selected.push('all');
        } else {
            provinceFilterList.querySelectorAll('.province-checkbox:checked').forEach(cb => {
                selected.push(cb.value);
            });
        }

        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Applying...';

        fetch('{{ route("province.filter.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                province_ids: selected
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                selectedProvinceIds = data.selected_ids || [];
                managedProvinces = data.managed_provinces || [];
                updateUI();

                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(provinceFilterDropdown);
                if (dropdown) {
                    dropdown.hide();
                }

                // Reload current page to apply filter
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update filter'));
            }
        })
        .catch(error => {
            console.error('Error applying filter:', error);
            alert('Error applying filter. Please try again.');
        })
        .finally(() => {
            applyBtn.disabled = false;
            applyBtn.innerHTML = 'Apply Filter';
        });
    });

    // Clear filter
    clearBtn.addEventListener('click', function() {
        if (confirm('Clear province filter and show all provinces?')) {
            clearBtn.disabled = true;
            clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Clearing...';

            fetch('{{ route("province.filter.clear") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectedProvinceIds = [];
                    updateUI();

                    // Close dropdown
                    const dropdown = bootstrap.Dropdown.getInstance(provinceFilterDropdown);
                    if (dropdown) {
                        dropdown.hide();
                    }

                    // Reload current page
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error clearing filter:', error);
                alert('Error clearing filter. Please try again.');
            })
            .finally(() => {
                clearBtn.disabled = false;
                clearBtn.innerHTML = 'Clear';
            });
        }
    });

    // Update UI based on current selection
    function updateUI() {
        const count = selectedProvinceIds.length;
        const total = managedProvinces.length;

        if (count === 0 || count === total) {
            filterLabel.textContent = 'All Provinces';
            filterBadge.textContent = total;
            filterInfo.textContent = `Showing data from all ${total} provinces`;
        } else {
            filterLabel.textContent = `${count} Province${count > 1 ? 's' : ''}`;
            filterBadge.textContent = count;
            const selectedNames = managedProvinces
                .filter(p => selectedProvinceIds.some(id => parseInt(id) === parseInt(p.id)))
                .map(p => p.name)
                .join(', ');
            filterInfo.textContent = `Showing: ${selectedNames}`;
        }
    }

    // Initial load
    loadProvinces();
});
</script>
