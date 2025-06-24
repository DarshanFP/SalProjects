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
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <select name="project_type" class="form-control">
                                    <option value="">Filter by Project Type</option>
                                    @foreach($projectTypes as $type)
                                        <option value="{{ $type }}" {{ request('project_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="user_id" class="form-control">
                                    <option value="">Filter by Executor</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

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
                                        <td class="amount-sanctioned">{{ number_format($project->amount_sanctioned, 2) }}</td>
                                        <td class="amount-forwarded">{{ number_format($project->amount_forwarded, 2) }}</td>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
