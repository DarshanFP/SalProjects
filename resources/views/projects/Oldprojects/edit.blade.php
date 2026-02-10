{{-- resources/views/projects/Oldprojects/edit.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Edit my Project</h4>
                </div>
                <div class="card-body">
                    <form id="editProjectForm" action="{{ route('projects.update', $project->project_id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Project General Information  -->
                        <div class="mb-3 card">
                            <div class="card-header">
                                <h4 class="fp-text-margin">General Information</h4>
                            </div>
                            <div class="card-body">
                                @include('projects.partials.Edit.general_info')
                            </div>
                        </div>

                        <!-- Key Information Section (excluded for Individual project types) -->
                        @if (!in_array($project->project_type, \App\Constants\ProjectType::getIndividualTypes()))
                            @include('projects.partials.Edit.key_information')
                        @endif

                        <!-- Conditional Sections Based on Project Type -->
                        @if ($project->project_type === 'Development Projects')
                            @include('projects.partials.Edit.RST.beneficiaries_area')
                        @elseif ($project->project_type === 'CHILD CARE INSTITUTION')
                            @include('projects.partials.Edit.CCI.rationale')
                            @include('projects.partials.Edit.CCI.statistics')
                            @include('projects.partials.Edit.CCI.annexed_target_group')
                            @include('projects.partials.Edit.CCI.age_profile')
                            @include('projects.partials.Edit.CCI.personal_situation')
                            @include('projects.partials.Edit.CCI.economic_background')
                            @include('projects.partials.Edit.CCI.achievements')
                            @include('projects.partials.Edit.CCI.present_situation')
                        @elseif ($project->project_type === 'Residential Skill Training Proposal 2')
                            @include('projects.partials.Edit.RST.beneficiaries_area')
                            @include('projects.partials.Edit.RST.institution_info')
                            @include('projects.partials.Edit.RST.target_group')
                            @include('projects.partials.Edit.RST.target_group_annexure')
                            @include('projects.partials.Edit.RST.geographical_area')
                        @elseif ($project->project_type === 'Rural-Urban-Tribal')
                            @include('projects.partials.Edit.Edu-RUT.basic_info')
                            @include('projects.partials.Edit.Edu-RUT.target_group')
                            @include('projects.partials.Edit.Edu-RUT.annexed_target_group')

                        @elseif ($project->project_type === 'Individual - Ongoing Educational support')
                            @include('projects.partials.Edit.IES.personal_info')
                            @include('projects.partials.Edit.IES.family_working_members')
                            @include('projects.partials.Edit.IES.immediate_family_details')
                            @include('projects.partials.Edit.IES.educational_background')
                            @include('projects.partials.Edit.IES.estimated_expenses', ['IESExpenses' => $IESExpenses])
                            @include('projects.partials.Edit.IES.attachments')

                        @elseif ($project->project_type === 'Individual - Initial - Educational support')
                            @include('projects.partials.Edit.IIES.personal_info')
                            @include('projects.partials.Edit.IIES.family_working_members')
                            @include('projects.partials.Edit.IIES.immediate_family_details')
                            @include('projects.partials.Edit.IIES.education_background')
                            @include('projects.partials.Edit.IIES.scope_financial_support')
                            @include('projects.partials.Edit.IIES.estimated_expenses', ['iiesExpenses' => $iiesExpenses])
                            @include('projects.partials.Edit.IIES.attachments')

                        @elseif ($project->project_type === 'Individual - Livelihood Application')
                            @include('projects.partials.Edit.ILP.personal_info', ['personalInfo' => $ILPPersonalInfo])
                            @include('projects.partials.Edit.ILP.revenue_goals', ['revenueGoals' => $ILPRevenueGoals])
                            @include('projects.partials.Edit.ILP.strength_weakness', ['strengths' => $ILPStrengthWeakness['strengths'] ?? [], 'weaknesses' => $ILPStrengthWeakness['weaknesses'] ?? []])
                            @include('projects.partials.Edit.ILP.risk_analysis', ['riskAnalysis' => $ILPRiskAnalysis])
                            @include('projects.partials.Edit.ILP.attached_docs', ['attachedDocs' => $ILPAttachedDocuments])
                            @include('projects.partials.Edit.ILP.budget', ['budgets' => $ILPBudget['budgets'], 'total_amount' => $ILPBudget['total_amount'], 'beneficiary_contribution' => $ILPBudget['beneficiary_contribution'], 'amount_requested' => $ILPBudget['amount_requested']])

                        @elseif ($project->project_type === 'Individual - Access to Health')
                            @include('projects.partials.Edit.IAH.personal_info')
                            @include('projects.partials.Edit.IAH.health_conditions')
                            @include('projects.partials.Edit.IAH.earning_members')
                            @include('projects.partials.Edit.IAH.support_details')
                            @include('projects.partials.Edit.IAH.budget_details')
                            @include('projects.partials.Edit.IAH.documents')

                        @elseif ($project->project_type === 'Institutional Ongoing Group Educational proposal')
                            @include('projects.partials.Edit.IGE.institution_info')
                            @include('projects.partials.Edit.IGE.beneficiaries_supported')
                            @include('projects.partials.Edit.IGE.ongoing_beneficiaries')
                            @include('projects.partials.Edit.IGE.new_beneficiaries')
                            @include('projects.partials.Edit.IGE.budget')
                            @include('projects.partials.Edit.IGE.development_monitoring')

                        @elseif ($project->project_type === 'Livelihood Development Projects')
                            @include('projects.partials.Edit.LDP.need_analysis')
                            @include('projects.partials.Edit.LDP.intervention_logic')

                        @elseif ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER')
                            @include('projects.partials.Edit.CIC.basic_info')

                        @endif

                        <!-- Default Partial Sections -->
                        @if (!in_array($project->project_type, [
                            'Individual - Ongoing Educational support',
                            'Individual - Livelihood Application',
                            'Individual - Access to Health',
                            'Individual - Initial - Educational support',
                        ]))
                            @include('projects.partials.Edit.logical_framework')
                            @include('projects.partials.Edit.sustainibility')
                            @include('projects.partials.Edit.budget')
                            @include('projects.partials.Edit.attachment')
                        @endif

                        <div class="text-center mt-4">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary btn-save-action">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('projects.partials.scripts-edit')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const editForm = document.getElementById('editProjectForm');

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            try {
                // Check if this is a draft save (bypass validation)
                const isDraftSave = this.querySelector('input[name="save_as_draft"]');
                if (isDraftSave && isDraftSave.value === '1') {
                    // Allow draft save without validation
                    return true;
                }
                
                // For regular submission, check HTML5 validation
                if (!this.checkValidity()) {
                    this.reportValidity();
                    e.preventDefault();
                    return false;
                }
                
                // Show loading indicator
                if (saveDraftBtn) {
                    saveDraftBtn.disabled = true;
                    saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                }
                
                // Allow form to submit normally
                return true;
            } catch (error) {
                console.error('Form submission error:', error);
                e.preventDefault();
                
                // Show user-friendly error message
                alert('An error occurred while submitting the form. Please try again or contact support if the problem persists.');
                
                // Re-enable button
                if (saveDraftBtn) {
                    saveDraftBtn.disabled = false;
                    saveDraftBtn.innerHTML = 'Save Changes';
                }
                
                return false;
            }
        });
    }

    // Handle "Save Changes" button click
    if (saveDraftBtn && editForm) {
        saveDraftBtn.addEventListener('click', function(e) {
            try {
                e.preventDefault();
                
                // Remove required attributes temporarily to allow submission
                const requiredFields = editForm.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    field.removeAttribute('required');
                });
                
                // Add hidden input to indicate draft save
                let draftInput = editForm.querySelector('input[name="save_as_draft"]');
                if (!draftInput) {
                    draftInput = document.createElement('input');
                    draftInput.type = 'hidden';
                    draftInput.name = 'save_as_draft';
                    draftInput.value = '1';
                    editForm.appendChild(draftInput);
                } else {
                    draftInput.value = '1';
                }
                
                // Show loading indicator
                saveDraftBtn.disabled = true;
                saveDraftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                
                // Submit form
                editForm.submit();
            } catch (error) {
                console.error('Save error:', error);
                alert('An error occurred while saving. Please try again.');
                
                // Re-enable button
                saveDraftBtn.disabled = false;
                saveDraftBtn.innerHTML = 'Save Changes';
            }
        });
    }
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const projectTypeDropdown = document.getElementById('project_type');
        
        if (!projectTypeDropdown) {
            console.warn('Project type dropdown not found');
            return;
        }

        // Note: In edit mode, sections are conditionally rendered server-side via Blade
        // This JavaScript handles project type changes if the dropdown is enabled
        // If project type is changed, user should be warned that sections may not match
        
        projectTypeDropdown.addEventListener('change', function() {
            // Warn user that changing project type may cause section mismatches
            if (confirm('Changing project type may cause section mismatches. Are you sure you want to continue?')) {
                // Reload page to re-render sections with new project type
                // Or submit form to update project type first
                // Project type changed - handled by server-side rendering
                // Note: In a production environment, you might want to:
                // 1. Disable the project_type dropdown in edit mode, OR
                // 2. Submit the form to update project type and reload, OR
                // 3. Use AJAX to reload sections dynamically
            } else {
                // Revert to original value
                this.value = '{{ $project->project_type }}';
            }
        });
    });
</script>

<style>
.readonly-input {
    background-color: #0D1427;
    color: #f4f0f0;
}
.select-input {
    background-color: #112f6b;
    color: #f4f0f0;
}
.readonly-select {
    background-color: #092968;
    color: #f4f0f0;
}
.table th, .table td {
    vertical-align: middle;
    text-align: center;
    padding: 0;
}
.table th {
    white-space: normal;
}
.table td input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.375rem 0.75rem;
}
.table-container {
    overflow-x: auto;
}

/* Fix text wrapping in Activities and Means of Verification table */
.activities-table {
    table-layout: fixed;
    width: 100%;
}

.activities-table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    max-width: 0;
}

.activities-table td textarea {
    width: 100% !important;
    max-width: 100%;
    box-sizing: border-box;
    resize: vertical;
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.activities-table th {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Ensure table doesn't overflow container */
.activities-container {
    overflow-x: auto;
    max-width: 100%;
}

.activities-container .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Consistent word-wrap for all table cells with text content */
.table-cell-wrap {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}

.table-cell-wrap textarea {
    width: 100%;
    box-sizing: border-box;
    resize: vertical;
}

/* Budget table cell wrapping */
.budget-rows td:first-child {
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 200px;
}

/* Save button styling */
.btn-save-action {
    min-width: 220px;
    padding: 12px 24px;
    font-size: 1.1rem;
    transition: all 0.2s ease;
}
.btn-save-action:hover {
    font-weight: bold;
    background-color: #112f6b !important;
    color: #f4f0f0 !important;
    border-color: #112f6b;
}
</style>
@endsection
