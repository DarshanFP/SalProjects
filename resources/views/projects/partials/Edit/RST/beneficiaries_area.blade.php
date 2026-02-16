@php
    $totalDirect = (isset($beneficiariesArea) && $beneficiariesArea instanceof \Illuminate\Support\Collection && $beneficiariesArea->isNotEmpty()) ? $beneficiariesArea->sum('direct_beneficiaries') : 0;
    $totalIndirect = (isset($beneficiariesArea) && $beneficiariesArea instanceof \Illuminate\Support\Collection && $beneficiariesArea->isNotEmpty()) ? $beneficiariesArea->sum('indirect_beneficiaries') : 0;
@endphp
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit: Project Area</h4>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="RST-project-area-rows">
                    @if(isset($beneficiariesArea) && count($beneficiariesArea) > 0)
                        @foreach($beneficiariesArea as $index => $area)
                            <tr>
                                <td><input type="text" name="project_area[]" value="{{ $area->project_area }}" class="form-control"></td>
                                <td><input type="text" name="category_beneficiary[]" value="{{ $area->category_beneficiary }}" class="form-control"></td>
                                <td><input type="number" name="direct_beneficiaries[]" value="{{ $area->direct_beneficiaries }}" class="form-control"></td>
                                <td><input type="number" name="indirect_beneficiaries[]" value="{{ $area->indirect_beneficiaries }}" class="form-control"></td>
                                <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" name="project_area[]" class="form-control"></td>
                            <td><input type="text" name="category_beneficiary[]" class="form-control"></td>
                            <td><input type="number" name="direct_beneficiaries[]" class="form-control"></td>
                            <td><input type="number" name="indirect_beneficiaries[]" class="form-control"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRSTProjectAreaRow(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2"><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" value="Total" readonly tabindex="-1" style="font-weight: bold;"></td>
                        <td><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" id="RST-total-direct" value="{{ $totalDirect }}" readonly tabindex="-1" style="font-weight: bold;"></td>
                        <td><input type="text" class="form-control form-control-plaintext border-0 bg-transparent" id="RST-total-indirect" value="{{ $totalIndirect }}" readonly tabindex="-1" style="font-weight: bold;"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-2">
            <strong>Total Beneficiaries:</strong> <span id="RST-total-beneficiaries">{{ $totalDirect + $totalIndirect }}</span>
        </div>
        <button type="button" class="mt-3 btn btn-primary" onclick="addRSTProjectAreaRow()">Add More</button>
    </div>
</div>

<script>
    let RSTprojectAreaRowIndex = {{ isset($beneficiariesArea) ? count($beneficiariesArea) : 1 }};

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

    function addRSTProjectAreaRow() {
        RSTprojectAreaRowIndex++;
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
        if (tbody.children.length > 1) {
            row.remove();
            RSTprojectAreaRowIndex--;
            updateRSTBeneficiariesTotals();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateRSTBeneficiariesTotals();
        document.getElementById('RST-project-area-rows')?.addEventListener('input', function(e) {
            if (e.target.matches('input[name="direct_beneficiaries[]"], input[name="indirect_beneficiaries[]"], input[type="number"]')) {
                updateRSTBeneficiariesTotals();
            }
        });
    });
</script>
