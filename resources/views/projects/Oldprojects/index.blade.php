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
                        table.my-projects-table td a.btn {
                            font-size: 12px;
                            padding: 4px 8px;
                            height: auto;
                            line-height: 1;
                            display: inline-block;
                            white-space: nowrap;
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

                    <table class="table table-bordered my-projects-table">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Project ID</th>
                                <th style="width: 35%;">Project Title</th>
                                <th style="width: 20%;">Project Type</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td>{{ $project->project_id }}</td>
                                    <td class="project-title">{{ $project->project_title }}</td>
                                    <td class="project-type">{{ $project->project_type }}</td>
                                    <td class="project-status">
                                        {{ \App\Models\OldProjects\Project::$statusLabels[$project->status] ?? $project->status }}
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-info">View</a>
                                        @if($project->status == 'draft' || $project->status == 'reverted_by_provincial' || $project->status == 'reverted_by_coordinator')
                                            <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-primary">Edit</a>
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
