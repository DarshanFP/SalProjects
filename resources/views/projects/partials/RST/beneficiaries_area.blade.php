{{-- resources/views/projects/partials/RST/beneficiaries_area.blade.php --}}
{{-- This file is part of the RST project. It contains the HTML and JavaScript code for managing project area beneficiaries. --}}

{{-- Check if the readonly variable is set and not true --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Project Area</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Project Area</th>
                        <th>Category of Beneficiary</th>
                        <th>Direct Beneficiaries</th>
                        <th>Indirect Beneficiaries</th>
                        @if(!isset($readonly) || !$readonly)
                            <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="RST-project-area-rows">
                    @if(isset($readonly) && $readonly && isset($beneficiaries) && !empty($beneficiaries))
                        @foreach($beneficiaries as $beneficiary)
                            <tr>
                                <td><input type="text" class="form-control" value="{{ $beneficiary['project_area'] ?? '' }}" readonly></td>
                                <td><input type="text" class="form-control" value="{{ $beneficiary['category'] ?? '' }}" readonly></td>
                                <td><input type="number" class="form-control" value="{{ $beneficiary['direct'] ?? 0 }}" readonly></td>
                                <td><input type="number" class="form-control" value="{{ $beneficiary['indirect'] ?? 0 }}" readonly></td>
                            </tr>
                        @endforeach
                    @elseif(isset($beneficiaries) && !empty($beneficiaries))
                        @foreach($beneficiaries as $beneficiary)
                            <tr>
                                <td><input type="text" name="project_area[]" class="form-control" value="{{ $beneficiary['project_area'] ?? '' }}"></td>
                                <td><input type="text" name="category_beneficiary[]" class="form-control" value="{{ $beneficiary['category'] ?? '' }}"></td>
                                <td><input type="number" name="direct_beneficiaries[]" class="form-control" value="{{ $beneficiary['direct'] ?? 0 }}"></td>
                                <td><input type="number" name="indirect_beneficiaries[]" class="form-control" value="{{ $beneficiary['indirect'] ?? 0 }}"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <!-- Default empty row when no data is available and not readonly -->
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                            @if(!isset($readonly) || !$readonly)
                                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                            @endif
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2"><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" value="Total" readonly tabindex="-1" style="font-weight: bold;"></td>
                        <td><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" id="RST-total-direct" value="{{ (isset($beneficiaries) && !empty($beneficiaries)) ? collect($beneficiaries)->sum('direct') : 0 }}" readonly tabindex="-1" style="font-weight: bold;"></td>
                        <td><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" id="RST-total-indirect" value="{{ (isset($beneficiaries) && !empty($beneficiaries)) ? collect($beneficiaries)->sum('indirect') : 0 }}" readonly tabindex="-1" style="font-weight: bold;"></td>
                        @if(!isset($readonly) || !$readonly)
                            <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-2">
            <strong>Total Beneficiaries:</strong> <span id="RST-total-beneficiaries">{{ (isset($beneficiaries) && !empty($beneficiaries)) ? collect($beneficiaries)->sum('direct') + collect($beneficiaries)->sum('indirect') : 0 }}</span>
        </div>
        @if(!isset($readonly) || !$readonly)
            <button type="button" class="mt-3 btn btn-primary" onclick="addRSTProjectAreaRow()">Add More</button>
        @endif
    </div>
</div>

<script>
// Define RSTprojectAreaRowIndex globally, initializing based on existing rows
window.RSTprojectAreaRowIndex = window.RSTprojectAreaRowIndex || {{ isset($beneficiaries) && !empty($beneficiaries) ? count($beneficiaries) : 1 }};

function updateRSTBeneficiariesTotals() {
    const tbody = document.getElementById('RST-project-area-rows');
    if (!tbody) return;

    let totalDirect = 0;
    let totalIndirect = 0;

    tbody.querySelectorAll('tr').forEach(function(row) {
        const inputs = row.querySelectorAll('input[type="number"]');
        if (inputs[0]) totalDirect += parseInt(inputs[0].value) || 0;
        if (inputs[1]) totalIndirect += parseInt(inputs[1].value) || 0;
    });

    const totalDirectEl = document.getElementById('RST-total-direct');
    const totalIndirectEl = document.getElementById('RST-total-indirect');
    const totalBeneficiariesEl = document.getElementById('RST-total-beneficiaries');
    if (totalDirectEl) totalDirectEl.value = totalDirect;
    if (totalIndirectEl) totalIndirectEl.value = totalIndirect;
    if (totalBeneficiariesEl) totalBeneficiariesEl.textContent = totalDirect + totalIndirect;
}

@if(!isset($readonly) || !$readonly)
    function addRSTProjectAreaRow() {
        window.RSTprojectAreaRowIndex++;
        const newRow = `
            <tr>
                <td><input type="text" name="project_area[]" class="form-control"></td>
                <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
            </tr>
        `;
        document.getElementById('RST-project-area-rows').insertAdjacentHTML('beforeend', newRow);
        updateRSTBeneficiariesTotals();
    }

    function removeRSTProjectAreaRow(button) {
        const tbody = document.getElementById('RST-project-area-rows');
        const row = button.closest('tr');
        if (tbody.children.length > 1) { // Prevent removing the last row
            row.remove();
            window.RSTprojectAreaRowIndex--;
            updateRSTBeneficiariesTotals();
        }
    }
@endif

document.addEventListener('DOMContentLoaded', function() {
    updateRSTBeneficiariesTotals();
    document.getElementById('RST-project-area-rows')?.addEventListener('input', function(e) {
        if (e.target.matches('input[name="direct_beneficiaries[]"], input[name="indirect_beneficiaries[]"], input[type="number"]')) {
            updateRSTBeneficiariesTotals();
        }
    });
});
</script>
