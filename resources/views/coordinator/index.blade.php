@extends('coordinator.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">ALL PROJECT REPORTS</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('coordinator.dashboard') }}">
                        <div class="mb-3 row">
                            <div class="col-md-3">
                                <select name="province" class="form-control">
                                    <option value="">Filter by Province</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="user_id" class="form-control">
                                    <option value="">Filter by Executor</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="project_type" class="form-control">
                                    <option value="">Filter by Project Type</option>
                                    <option value="Development Project">Development Project</option>
                                    <option value="Skill Training">Skill Training</option>
                                    <option value="Institutional Support">Institutional Support</option>
                                    <option value="Women in Distress">Women in Distress</option>
                                    <option value="Development Livelihood">Development Livelihood</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Executor</th>
                                    <th>Province</th>
                                    <th>Project Title</th>
                                    <th>Total Amount</th>
                                    <th>Total Expenses</th>
                                    <th>Expenses This Month</th>
                                    <th>Balance Amount</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                    @php
                                        // Summing up the account details
                                        $totalAmount = $report->accountDetails->sum('total_amount');
                                        $totalExpenses = $report->accountDetails->sum('total_expenses');
                                        $expensesThisMonth = $report->accountDetails->sum('expenses_this_month');
                                        $balanceAmount = $report->accountDetails->sum('balance_amount');
                                    @endphp
                                    <tr>
                                        <td>{{ $report->report_id }}</td>
                                        <td>{{ $report->user->name }}</td>
                                        <td>{{ $report->user->province }}</td>
                                        <td>{{ $report->project_title }}</td>
                                        <td>{{ number_format($totalAmount, 2) }}</td>
                                        <td>{{ number_format($totalExpenses, 2) }}</td>
                                        <td>{{ number_format($expensesThisMonth, 2) }}</td>
                                        <td>{{ number_format($balanceAmount, 2) }}</td>
                                        <td>{{ $report->project_type }}</td>
                                        <td>
                                            <a href="{{ route('coordinator.monthly.report.show', $report->report_id) }}" class="btn btn-primary btn-sm">View</a>
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
