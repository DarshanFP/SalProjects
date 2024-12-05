@extends('executor.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Monthly Reports</div>

                <div class="card-body">
                    <style>
                        /* Center buttons in the Actions column */
                        table.monthly-reports-table td {
                            text-align: center; /* Center content horizontally */
                            vertical-align: middle; /* Center content vertically */
                        }

                        /* Left-align text for specific columns */
                        table.monthly-reports-table td:nth-child(4) { /* Project Title column */
                            text-align: left !important; /* Left-align text */
                        }

                        /* Style buttons */
                        table.monthly-reports-table td a.btn,
                        table.monthly-reports-table td button.btn {
                            font-size: 12px; /* Adjust font size */
                            padding: 4px 8px; /* Reduce padding */
                            height: auto; /* Allow height to adjust */
                            line-height: 1; /* Adjust line height */
                            display: inline-block; /* Ensure proper button display */
                            white-space: nowrap; /* Prevent text wrapping */
                        }

                        /* Adjust table layout */
                        table.monthly-reports-table td,
                        table.monthly-reports-table th {
                            white-space: normal;
                            word-break: break-word;
                            overflow: visible;
                        }

                        table {
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

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <table class="table table-bordered monthly-reports-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Project ID</th>
                                <th style="width: 15%;">Report ID</th>
                                <th style="width: 20%;">Report Month Year</th>
                                <th style="width: 30%;">Project Title</th>
                                <th style="width: 20%;">Actions</th>
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
                                        <form action="#" method="POST" style="display: inline-block;">
                                        {{-- <form action="{{ route('monthly.report.submit', $report->report_id) }}" method="POST" style="display: inline-block;"> --}}
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
