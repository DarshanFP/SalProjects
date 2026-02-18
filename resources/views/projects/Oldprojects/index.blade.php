{{-- resources/views/projects/Oldprojects/index.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">My Projects (Pending)</h4>
                </div>
                <div class="card-body">
                    <style>
                        /* Center buttons horizontally and vertically in their cells */
                        table.my-projects-table td {
                            text-align: center;
                            vertical-align: middle;
                        }

                        /* Left-align text for specific columns */
                        table.my-projects-table td.project-title,
                        table.my-projects-table td.project-type,
                        table.my-projects-table td.project-status {
                            text-align: left;
                            white-space: normal !important;
                            word-break: break-word !important;
                            overflow: visible !important;
                        }

                        /* Style buttons for proper sizing */
                        table.my-projects-table td a.btn,
                        table.my-projects-table td button.btn {
                            font-size: 12px;
                            padding: 4px 8px;
                            height: auto;
                            line-height: 1;
                            display: inline-block;
                            white-space: nowrap;
                            vertical-align: middle;
                        }
                        table.my-projects-table td form.d-inline {
                            display: inline;
                            vertical-align: middle;
                        }

                        /* Style badges */
                        table.my-projects-table .badge {
                            font-size: 11px;
                            padding: 5px 10px;
                            font-weight: 500;
                        }

                        .table {
                            table-layout: fixed;
                            width: 100%;
                        }

                        tr, td, th {
                            height: auto;
                            overflow: visible;
                        }

                        td, th {
                            padding: 8px;
                        }
                    </style>

                    @php
                        use App\Constants\ProjectStatus;
                        use App\Helpers\ProjectPermissionHelper;
                        $editableStatuses = ProjectStatus::getEditableStatuses();
                    @endphp

                    <table class="table table-bordered my-projects-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Project ID</th>
                                <th style="width: 30%;">Project Title</th>
                                <th style="width: 15%;">Project Type</th>
                                <th style="width: 12%;">Role</th>
                                <th style="width: 13%;">Status</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                @php
                                    $user = auth()->user();
                                    $isOwner = $project->user_id === $user->id;
                                    $isInCharge = $project->in_charge === $user->id && !$isOwner;
                                @endphp
                                <tr>
                                    <td>{{ $project->project_id }}</td>
                                    <td class="project-title">{{ $project->project_title }}</td>
                                    <td class="project-type">{{ $project->project_type }}</td>
                                    <td class="text-center">
                                        @if($isOwner)
                                            <span class="badge bg-success">Executor</span>
                                        @elseif($isInCharge)
                                            <span class="badge bg-info">Applicant</span>
                                        @endif
                                    </td>
                                    <td class="project-status">
                                        @include('projects.partials.status-badge', ['project' => $project])
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-info btn-sm">View</a>
                                        @if(in_array($project->status, $editableStatuses))
                                            @php
                                                $canEdit = ProjectPermissionHelper::canEdit($project, $user);
                                            @endphp
                                            @if($canEdit)
                                                <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-primary btn-sm">Edit</a>
                                            @endif
                                        @endif
                                        @if(ProjectPermissionHelper::canDelete($project, $user))
                                            <form action="{{ route('projects.trash', $project->project_id) }}" method="POST" class="d-inline align-middle" onsubmit="return confirm('Move this project to trash? You can restore it later.');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm ms-1">Trash</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
