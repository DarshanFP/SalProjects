@extends('admin.layout')
@section('title', 'Budget Reconciliation')
@section('content')
<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4">
        <div>
            <h4 class="mb-2">Project Budget Reconciliation</h4>
            <p class="text-muted mb-0">Project: {{ $project->project_id ?? $project->id }} — {{ Str::limit($project->project_title ?? '-', 50) }}</p>
        </div>
        <a href="{{ route('admin.budget-reconciliation.index') }}" class="btn btn-outline-secondary">Back to list</a>
    </div>

    <div class="alert alert-warning border-warning mb-4" role="alert">
        <strong>You are modifying an APPROVED project.</strong> Budget corrections affect financial records. Only correct when you have verified the discrepancy and have authority to do so. Every action is logged and attributable.
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Side-by-side comparison</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Stored (current)</th>
                            <th>Resolved (expected)</th>
                            <th>Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fields = [
                                'overall_project_budget' => 'Overall budget',
                                'amount_forwarded' => 'Amount forwarded',
                                'local_contribution' => 'Local contribution',
                                'amount_sanctioned' => 'Amount sanctioned',
                                'opening_balance' => 'Opening balance',
                            ];
                            $tolerance = 0.01;
                        @endphp
                        @foreach($fields as $key => $label)
                            @php
                                $s = $stored[$key] ?? 0;
                                $r = $resolved[$key] ?? 0;
                                $diff = abs($r - $s) > $tolerance;
                            @endphp
                            <tr class="{{ $diff ? 'table-warning' : '' }}">
                                <td>{{ $label }}</td>
                                <td>{{ number_format($s, 2) }}</td>
                                <td>{{ number_format($r, 2) }}</td>
                                <td>
                                    @if($diff)
                                        <span class="text-warning">{{ number_format($r - $s, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Admin decision</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Choose one action. Each requires confirmation before applying.</p>

            {{-- 1. Accept system suggestion --}}
            <div class="border rounded p-3 mb-3">
                <h6 class="mb-2">1. Accept system suggestion</h6>
                <p class="small text-muted mb-2">Apply resolver-computed values to this project. Optional reason below.</p>
                <form method="post" action="{{ route('admin.budget-reconciliation.accept', $project->id) }}" class="d-inline" onsubmit="return confirm('Apply system-suggested values to this project? This will update the project budget. Confirm?');">
                    @csrf
                    <input type="text" name="admin_comment" class="form-control form-control-sm d-inline-block w-50 me-2" placeholder="Reason (optional)" maxlength="500">
                    <button type="submit" class="btn btn-success btn-sm">Accept suggested</button>
                </form>
            </div>

            {{-- 2. Manual correction --}}
            <div class="border rounded p-3 mb-3">
                <h6 class="mb-2">2. Manual correction</h6>
                <p class="small text-muted mb-2">Edit overall, forwarded, and local; sanctioned and opening will be recomputed. <strong>Reason required.</strong></p>
                <form method="post" action="{{ route('admin.budget-reconciliation.manual', $project->id) }}" id="formManual">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small">Overall budget</label>
                            <input type="number" name="overall_project_budget" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('overall_project_budget', $stored['overall_project_budget'] ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Amount forwarded</label>
                            <input type="number" name="amount_forwarded" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('amount_forwarded', $stored['amount_forwarded'] ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Local contribution</label>
                            <input type="number" name="local_contribution" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('local_contribution', $stored['local_contribution'] ?? 0) }}" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Reason (required)</label>
                        <textarea name="admin_comment" class="form-control form-control-sm" rows="2" required maxlength="2000" placeholder="Mandatory reason for manual correction">{{ old('admin_comment') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Apply manual correction? Changes and reason will be logged. Confirm?');">Apply manual correction</button>
                </form>
            </div>

            {{-- 3. Reject correction --}}
            <div class="border rounded p-3">
                <h6 class="mb-2">3. Reject correction</h6>
                <p class="small text-muted mb-2">No data change. Project marked as reviewed in audit log.</p>
                <form method="post" action="{{ route('admin.budget-reconciliation.reject', $project->id) }}" class="d-inline" onsubmit="return confirm('Record rejection? No project data will change. Confirm?');">
                    @csrf
                    <input type="text" name="admin_comment" class="form-control form-control-sm d-inline-block w-50 me-2" placeholder="Comment (optional)" maxlength="500">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Reject correction</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Audit history (this project)</h5>
        </div>
        <div class="card-body">
            @if($audit_history->isEmpty())
                <p class="text-muted mb-0">No correction actions recorded yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Who</th>
                                <th>Action</th>
                                <th>Old → New (sanctioned)</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($audit_history as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $log->adminUser->name ?? $log->admin_user_id }}</td>
                                    <td><span class="badge bg-secondary">{{ $log->action_type }}</span></td>
                                    <td>{{ number_format($log->old_sanctioned ?? 0, 2) }} → {{ $log->new_sanctioned !== null ? number_format($log->new_sanctioned, 2) : '—' }}</td>
                                    <td>{{ Str::limit($log->admin_comment ?? '—', 40) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
