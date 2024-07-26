@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="mb-3 card">
                <div class="card-header">
                    <h4 class="fp-text-center1">Projects List</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Project ID</th>
                                <th>Project Title</th>
                                {{-- <th>Place</th> --}}
                                {{-- <th>Society Name</th> --}}
                                <th>Commencement Month & Year</th>
                                {{-- <th>In Charge</th>
                                <th>Goal</th> --}}
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td>{{ $project->id }}</td>
                                    <td>{{ $project->project_title }}</td>
                                    {{-- <td>{{ $project->place }}</td>
                                    <td>{{ $project->society_name }}</td> --}}
                                    <td>{{ $project->commencement_month_year }}</td>
                                    {{-- <td>{{ $project->in_charge }}</td>
                                    <td>{{ $project->goal }}</td> --}}
                                    <td>
                                        <a href="{{ route('projects.developmentProjects.show', $project->id) }}" class="btn btn-info btn-sm">View</a>
                                        <a href="{{ route('projects.developmentProjects.edit', $project->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                        {{-- <a href="{{ route('projects.developmentProjects.submit', $project->id) }}" class="btn btn-primary btn-sm">Submit to Provincial</a> --}}
                                        <a href="{{ route('projects.developmentProjects.createMonthlyReport', $project->id) }}" class="btn btn-success btn-sm">Create Monthly Report</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $projects->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
