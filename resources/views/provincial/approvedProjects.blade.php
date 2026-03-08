{{-- resources/views/provincial/approvedProjects.blade.php  --}}
@extends('provincial.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Approved Projects (Provincial)</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provincial.approved.projects') }}">
                        <div class="mb-3 row g-2 align-items-end">
                            <div class="col-md-2">
                                <label for="fy" class="form-label">Financial Year</label>
                                <select name="fy" id="fy" class="form-select auto-filter">
                                    @foreach($fyList ?? [] as $year)
                                        <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="project_type" class="form-label">Project Type</label>
                                <select name="project_type" id="project_type" class="form-select auto-filter">
                                    <option value="">All Project Types</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Team Member</label>
                                <select name="user_id" id="user_id" class="form-select auto-filter">
                                    <option value="">All Team Members</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('provincial.approved.projects', ['fy' => \App\Support\FinancialYearHelper::currentFY()]) }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var filterSubmitting = false;
                        document.querySelectorAll('.auto-filter').forEach(function(el) {
                            el.addEventListener('change', function() {
                                if (filterSubmitting) return;
                                filterSubmitting = true;
                                this.closest('form').submit();
                            });
                        });
                    });
                    </script>

                    <style>
                        table.my-projects-table {
                            table-layout: fixed;
                            width: 100%;
                        }

                        table.my-projects-table th,
                        table.my-projects-table td {
                            word-break: break-word;
                            white-space: normal !important;
                            overflow-wrap: break-word;
                            vertical-align: middle;
                            text-align: center;
                            padding: 8px;
                        }

                        /* Specifically target the Amount columns to allow wrapping and set a narrower width */
                        table.my-projects-table th.amount-sanctioned,
                        table.my-projects-table td.amount-sanctioned,
                        table.my-projects-table th.amount-forwarded,
                        table.my-projects-table td.amount-forwarded {
                            width: 10%; /* Adjust as needed */
                            white-space: normal !important;
                            word-break: break-word !important;
                            text-align: center; /* If you want center, otherwise can be left */
                        }

                        /* For columns like project title and type, you can also assign classes and adjust styling if needed */
                        table.my-projects-table th.project-title,
                        table.my-projects-table td.project-title,
                        table.my-projects-table th.project-type,
                        table.my-projects-table td.project-type {
                            text-align: left;
                        }
                    </style>

                    <div class="table-responsive">
                        <table class="table table-bordered my-projects-table">
                            <thead>
                                <tr>
                                    <th>Project ID</th>
                                    <th>Executor</th>
                                    <th class="project-title">Project Title</th>
                                    <th class="project-type">Project Type</th>
                                    <th class="amount-sanctioned">Amount Sanctioned</th>
                                    <th class="amount-forwarded">Amount Forwarded</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($projects as $project)
                                    <tr>
                                        <td>{{ $project->project_id }}</td>
                                        <td>{{ $project->user->name }}</td>
                                        <td class="project-title">{{ $project->project_title }}</td>
                                        <td class="project-type">{{ $project->project_type }}</td>
                                        <td class="amount-sanctioned">{{ format_indian((float) (($resolvedFinancials[$project->project_id] ?? [])['amount_sanctioned'] ?? 0), 2) }}</td>
                                        <td class="amount-forwarded">{{ format_indian($project->amount_forwarded, 2) }}</td>
                                        <td>
                                            {{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}
                                        </td>
                                        <td>
                                            <a href="{{ route('provincial.projects.show', $project->project_id) }}" class="btn btn-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No approved projects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(isset($projects) && method_exists($projects, 'links'))
                        <div class="card-footer d-flex justify-content-end mt-3">
                            {{ $projects->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
