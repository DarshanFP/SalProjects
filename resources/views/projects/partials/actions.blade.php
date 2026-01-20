{{-- resources/views/projects/partials/actions.blade.php --}}
{{-- resources/views/projects/partials/actions.blade.php --}}
@php
    use App\Constants\ProjectStatus;
    $user = Auth::user();
    $status = $project->status;
    $userRole = $user->role;
    $editableStatuses = ProjectStatus::getEditableStatuses();
@endphp

<div class="mb-3 card">
    <div class="card-header">
        <h4>Actions</h4>
    </div>
    <div class="card-body">
        @if(in_array($userRole, ['executor', 'applicant']))
            @if(in_array($status, $editableStatuses))
                <!-- Executor/Applicant can submit to provincial for draft, reverted_by_provincial, or reverted_by_coordinator status -->
                <form action="{{ route('projects.submitToProvincial', $project->project_id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Submit to Provincial</button>
                </form>
            @endif
        @endif

        @if($userRole === 'provincial')
            @if($status === ProjectStatus::SUBMITTED_TO_PROVINCIAL || $status === ProjectStatus::REVERTED_BY_COORDINATOR)
                <!-- Provincial can revert to executor or forward to coordinator -->
                <form action="{{ route('projects.revertToExecutor', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Revert to Executor</button>
                </form>

                <form action="{{ route('projects.forwardToCoordinator', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Forward to Coordinator</button>
                </form>
            @endif
        @endif

        @if($userRole === 'coordinator')
            @if($status === ProjectStatus::FORWARDED_TO_COORDINATOR)
                <!-- Coordinator can approve, reject, or revert to provincial -->
                <button type="button"
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#approveProjectModal{{ $project->project_id }}">
                    Approve
                </button>

                <form action="{{ route('projects.reject', $project->project_id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>

                <button type="button"
                        class="btn btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#revertProjectModal{{ $project->project_id }}">
                    Revert to Provincial
                </button>
            @endif
        @endif
    </div>
</div>

{{-- Approval Modal for Coordinator --}}
@if($userRole === 'coordinator' && $status === ProjectStatus::FORWARDED_TO_COORDINATOR)
<div class="modal fade" id="approveProjectModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="approveProjectModalLabel{{ $project->project_id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveProjectModalLabel{{ $project->project_id }}">Approve Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveProjectForm{{ $project->project_id }}"
                  action="{{ route('projects.approve', $project->project_id) }}"
                  method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Note:</strong> Please verify and update the Commencement Month & Year.
                        It cannot be before the current month and year.
                    </div>

                    <div class="mb-3">
                        <label for="commencement_month_{{ $project->project_id }}" class="form-label">
                            Commencement Month <span class="text-danger">*</span>
                        </label>
                        <select name="commencement_month"
                                id="commencement_month_{{ $project->project_id }}"
                                class="form-control" required>
                            <option value="">Select month</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                            @endfor
                        </select>
                        <small class="form-text text-muted">
                            Current: {{ $project->commencement_month ? date('F', mktime(0, 0, 0, $project->commencement_month, 1)) : 'Not set' }}
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="commencement_year_{{ $project->project_id }}" class="form-label">
                            Commencement Year <span class="text-danger">*</span>
                        </label>
                        <select name="commencement_year"
                                id="commencement_year_{{ $project->project_id }}"
                                class="form-control" required>
                            <option value="">Select year</option>
                            @for($y = (int)date('Y'); $y <= (int)date('Y') + 10; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <small class="form-text text-muted">
                            Current: {{ $project->commencement_year ?? 'Not set' }}
                        </small>
                    </div>

                    <div id="commencement_date_error_{{ $project->project_id }}"
                         class="alert alert-danger" style="display: none;">
                        <strong>Error:</strong> Commencement Month & Year cannot be before the current month and year.
                        Please update it to present or future month and year before approving.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Revert to Provincial Modal for Coordinator --}}
<div class="modal fade" id="revertProjectModal{{ $project->project_id }}" tabindex="-1" aria-labelledby="revertProjectModalLabel{{ $project->project_id }}" aria-hidden="true" data-open-on-error="{{ ($errors->has('revert_reason') || $errors->has('error')) ? '1' : '0' }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revertProjectModalLabel{{ $project->project_id }}">Revert Project to Provincial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('projects.revertToProvincial', $project->project_id) }}">
                @csrf
                <div class="modal-body">
                    <p><strong>Project ID:</strong> {{ $project->project_id }}</p>
                    <p><strong>Project Title:</strong> {{ $project->project_title }}</p>
                    <div class="mb-3">
                        <label for="revert_reason{{ $project->project_id }}" class="form-label">Reason for Revert <span class="text-danger">*</span></label>
                        <textarea class="form-control auto-resize-textarea @error('revert_reason') is-invalid @enderror"
                                  id="revert_reason{{ $project->project_id }}"
                                  name="revert_reason"
                                  rows="3"
                                  required>{{ old('revert_reason', '') }}</textarea>
                        @error('revert_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Revert to Provincial</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JavaScript Validation for Commencement Date --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('approveProjectForm{{ $project->project_id }}');
    if (!form) return;

    const monthSelect = document.getElementById('commencement_month_{{ $project->project_id }}');
    const yearInput = document.getElementById('commencement_year_{{ $project->project_id }}');
    const errorDiv = document.getElementById('commencement_date_error_{{ $project->project_id }}');

    function validateCommencementDate() {
        if (!monthSelect || !yearInput) return true;

        const month = parseInt(monthSelect.value);
        const year = parseInt(yearInput.value);
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth() + 1;
        const currentYear = currentDate.getFullYear();

        const commencementDate = new Date(year, month - 1, 1);
        const currentDateStart = new Date(currentYear, currentMonth - 1, 1);

        if (commencementDate < currentDateStart) {
            if (errorDiv) {
                errorDiv.style.display = 'block';
            }
            monthSelect.classList.add('is-invalid');
            yearInput.classList.add('is-invalid');
            return false;
        } else {
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
            monthSelect.classList.remove('is-invalid');
            yearInput.classList.remove('is-invalid');
            return true;
        }
    }

    // Real-time validation
    if (monthSelect) {
        monthSelect.addEventListener('change', validateCommencementDate);
    }
    if (yearInput) {
        yearInput.addEventListener('input', validateCommencementDate);
        yearInput.addEventListener('change', validateCommencementDate);
    }

    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateCommencementDate()) {
            e.preventDefault();
            alert('Commencement Month & Year cannot be before the current month and year. Please update it to present or future month and year before approving.');
            return false;
        }

        if (!confirm('Are you sure you want to approve this project with the updated commencement date?')) {
            e.preventDefault();
            return false;
        }
    });

    // Auto-open Revert modal when returning with revert-related errors
    const revertModalEl = document.querySelector('#revertProjectModal{{ $project->project_id }}[data-open-on-error="1"]');
    if (revertModalEl && typeof bootstrap !== 'undefined') {
        (new bootstrap.Modal(revertModalEl)).show();
    }
});
</script>

<style>
.is-invalid {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
@endif
