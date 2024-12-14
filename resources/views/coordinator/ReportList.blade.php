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

                            <div class="col-md-4">
                                <select name="project_type" class="form-control">
                                    <option value="">Filter by Project Type</option>
                                    <option value="CHILD CARE INSTITUTION">CHILD CARE INSTITUTION - Welfare home for children - Ongoing</option>
                                    <option value="Development Projects">Development Projects - Application</option>
                                    <option value="Rural-Urban-Tribal">Education Rural-Urban-Tribal</option>
                                    <option value="Institutional Ongoing Group Educational proposal">Institutional Ongoing Group Educational proposal</option>
                                    <option value="Livelihood Development Projects">Livelihood Development Projects</option>
                                    <option value="PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER">PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER - Application</option>
                                    <option value="NEXT PHASE - DEVELOPMENT PROPOSAL">NEXT PHASE - DEVELOPMENT PROPOSAL</option>
                                    <option value="Residential Skill Training Proposal 2">Residential Skill Training Proposal 2</option>
                                    <option value="Individual - Ongoing Educational support">Individual - Ongoing Educational support - Project Application</option>
                                    <option value="Individual - Livelihood Application">Individual - Livelihood Application</option>
                                    <option value="Individual - Access to Health">Individual - Access to Health - Project Application</option>
                                    <option value="Individual - Initial - Educational support">Individual - Initial - Educational support - Project Application</option>
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
