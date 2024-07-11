<!-- resources/views/coordinator/index.blade.php -->
@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">ALL DEVELOPMENT PROJECT REPORTS</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Provincial</th>
                                    <th>Executor</th>
                                    <th>Project Title</th>
                                    <th>Place</th>
                                    <th>Society Name</th>
                                    <th>Reporting Period</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rqwdReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>{{ $report->place }}</td>
                                    <td>{{ $report->society_name }}</td>
                                    <td>{{ $report->reporting_period }}</td>
                                    <td>Women in Distress</td>
                                    <td>
                                        <a href="{{ route('coordinator.reports.show', ['type' => 'rqwd', 'id' => $report->id]) }}" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                                @endforeach
                                @foreach($rqstReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>{{ $report->place }}</td>
                                    <td>{{ $report->society_name }}</td>
                                    <td>{{ $report->reporting_period }}</td>
                                    <td>Skill Training</td>
                                    <td>
                                        <a href="{{ route('coordinator.reports.show', ['type' => 'rqst', 'id' => $report->id]) }}" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                                @endforeach
                                @foreach($rqisReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>{{ $report->place }}</td>
                                    <td>{{ $report->society_name }}</td>
                                    <td>{{ $report->reporting_period }}</td>
                                    <td>Institutional Support</td>
                                    <td>
                                        <a href="{{ route('coordinator.reports.show', ['type' => 'rqis', 'id' => $report->id]) }}" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                                @endforeach
                                @foreach($rqdpReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>{{ $report->place }}</td>
                                    <td>{{ $report->society_name }}</td>
                                    <td>{{ $report->reporting_period }}</td>
                                    <td>Development Project</td>
                                    <td>
                                        <a href="{{ route('coordinator.reports.show', ['type' => 'rqdp', 'id' => $report->id]) }}" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                                @endforeach
                                @foreach($rqdlReports as $report)
                                <tr>
                                    <td>{{ $report->id }}</td>
                                    <td>{{ $report->user->parent->name ?? 'N/A' }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>{{ $report->place }}</td>
                                    <td>{{ $report->society_name }}</td>
                                    <td>{{ $report->reporting_period }}</td>
                                    <td>Development Livelihood</td>
                                    <td>
                                        <a href="{{ route('coordinator.reports.show', ['type' => 'rqdl', 'id' => $report->id]) }}" class="btn btn-primary btn-sm">View</a>
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
</div>
@endsection
