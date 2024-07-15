@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">DEVELOPMENT PROJECT REPORTS</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Project Title</th>
                                <th>Place</th>
                                <th>Society Name</th>
                                <th>Reporting Period</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                            <tr>
                                <td>{{ $report->id }}</td>
                                <td>{{ $report->project_title }}</td>
                                <td>{{ $report->place }}</td>
                                <td>{{ $report->society_name }}</td>
                                <td>{{ $report->reporting_period }}</td>
                                <td>
                                    <a href="#" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="#" class="btn btn-secondary btn-sm">Review</a>
                                    <form action="#"  style="display:inline;">
                                    <a href="{{ route('quarterly.developmentProject.show', $report->id) }}" class="btn btn-primary btn-sm">View</a>
                                    {{-- <a href="{{ route('quarterly.developmentLivelihood.review', $report->id) }}" class="btn btn-secondary btn-sm">Review</a>
                                    <form action="{{ route('quarterly.developmentLivelihood.revert', $report->id) }}" method="POST" style="display:inline;"> --}}
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">Revert</button>
                                    </form>
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
