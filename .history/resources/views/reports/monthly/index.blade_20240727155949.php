@extends('executor.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Monthly Reports</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Project ID</th>
                                <th>Report ID</th>
                                <th>Report Month Year</th>
                                <th>Project Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td>{{ $report->project_id }}</td>
                                    <td>{{ $report->report_id }}</td>
                                    <td>{{ $report->report_month_year }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>
                                        <a href="{{ route('monthly.report.edit', $report->report_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="{{ route('monthly.report.show', $report->report_id) }}" class="btn btn-info btn-sm">View</a>
                                        <form action="{{ route('monthly.report.submit', $report->report_id) }}" method="POST" style="display: inline-block;">
                                        <form action="{{ route('monthly.report.submit', $report->report_id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">Submit</button>
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
