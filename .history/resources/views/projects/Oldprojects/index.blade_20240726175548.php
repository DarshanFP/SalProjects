@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">ALL my PROJECT REPORTS</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Project ID</th>
                                    <th>Project Title</th>
                                    <th>Project Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                    <tr>
                                        <td>{{ $project->project_id }}</td>
                                        <td>{{ $project->project_title }}</td>
                                        <td>{{ $project->project_type }}</td>
                                        <td>{{ $project->status }}</td>
                                        <td>
                                            <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-info">View</a>
                                            @if($project->status == 'underwriting' || $project->status == 'reverted')
                                                <a href="{{ route('projects.edit', $project->project_id) }}" class="btn btn-primary">Edit</a>
                                            @endif
                                            <a href="{{ route('monthly.developmentProject.create', ['project_id' => $project->project_id]) }}" class="btn btn-success">Write Report</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
</div>
@endsection
