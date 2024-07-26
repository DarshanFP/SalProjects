@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1>Projects</h1>
    <table class="table">
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
                        <a href="{{ route('monthly.developmentProject.create', ['project_id' => $project->project_id]) }}" class="btn btn-success">Report</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
