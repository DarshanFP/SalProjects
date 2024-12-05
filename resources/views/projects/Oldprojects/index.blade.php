{{-- resources/views/projects/Oldprojects/index.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">My Projects</h4>
                </div>
                <div class="card-body">
                    <style>
                        /* Center buttons horizontally and vertically in their cells */
                        table.my-projects-table td {
                            text-align: center; /* Center content by default */
                            vertical-align: middle; /* Center vertically */
                        }

                        /* Left-align text for specific columns */
                        table.my-projects-table td.project-title,
                        table.my-projects-table td.project-type {
                            text-align: left; /* Left-align text */
                            white-space: normal !important;
                            word-break: break-word !important;
                            overflow: visible !important;
                        }

                        /* Style buttons for proper sizing */
                        table.my-projects-table td a.btn {
                            font-size: 12px; /* Adjust font size */
                            padding: 4px 8px; /* Reduce padding */
                            height: auto; /* Allow height to adjust */
                            line-height: 1; /* Adjust line height */
                            display: inline-block; /* Ensure proper button display */
                            white-space: nowrap; /* Prevent text wrapping */
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
                                    <td>{{ $project->status }}</td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-info">View</a>
                                        @if($project->status == 'underwriting' || $project->status == 'reverted')
                                            <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-primary">Edit</a>
                                        @endif
                                        <a href="{{ route('monthly.report.create', ['project_id' => $project->project_id]) }}" class="btn btn-success">Write Report</a>
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
