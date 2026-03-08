{{-- resources/views/projects/Oldprojects/approved.blade.php --}}
@extends('executor.dashboard')

@section('content')
<div class="page-content">
    <div class="row justify-content-center">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="fp-text-center1">My Approved Projects</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="dashboard-controls mb-3">
                        <select name="fy" class="dashboard-select">
                            <option value="">All Financial Years</option>
                            @foreach($availableFY as $year)
                                <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
                            @endforeach
                        </select>
                        <select name="role" class="dashboard-select">
                            <option value="owned" {{ ($role ?? '') == 'owned' ? 'selected' : '' }}>Owner / Executor</option>
                            <option value="in_charge" {{ ($role ?? '') == 'in_charge' ? 'selected' : '' }}>In-Charge / Applicant</option>
                            <option value="owned_and_in_charge" {{ ($role ?? '') == 'owned_and_in_charge' ? 'selected' : '' }}>All My Projects</option>
                        </select>
                    </form>
                    <script>
                        document.querySelectorAll('.dashboard-select').forEach(function(el) {
                            el.addEventListener('change', function() { this.closest('form').submit(); });
                        });
                    </script>

                    <style>
                        /* Center buttons horizontally and vertically in their cells */
                        table.my-projects-table td {
                            text-align: center;
                            vertical-align: middle;
                        }

                        /* Left-align text for specific columns */
                        table.my-projects-table td.project-title,
                        table.my-projects-table td.project-type,
                        table.my-projects-table td.project-status {
                            text-align: left;
                            white-space: normal !important;
                            word-break: break-word !important;
                            overflow: visible !important;
                        }

                        /* Style buttons for proper sizing */
                        table.my-projects-table td a.btn {
                            font-size: 12px;
                            padding: 4px 8px;
                            height: auto;
                            line-height: 1;
                            display: inline-block;
                            white-space: nowrap;
                        }

                        /* Style badges */
                        table.my-projects-table .badge {
                            font-size: 11px;
                            padding: 5px 10px;
                            font-weight: 500;
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
                                <th style="width: 25%;">Project Title</th>
                                <th style="width: 12%;">Project Type</th>
                                <th style="width: 10%;">Start</th>
                                <th style="width: 10%;">Role</th>
                                <th style="width: 13%;">Status</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $project)
                                @php
                                    $user = auth()->user();
                                    $isOwner = $project->user_id === $user->id;
                                    $isInCharge = $project->in_charge === $user->id && !$isOwner;
                                @endphp
                                <tr>
                                    <td>{{ $project->project_id }}</td>
                                    <td class="project-title">{{ $project->project_title }}</td>
                                    <td class="project-type">{{ $project->project_type }}</td>
                                    <td class="text-center">
                                        @php
                                            $date = $project->commencement_month_year;
                                            $fyBadge = $date ? \App\Support\FinancialYearHelper::fromDate(\Carbon\Carbon::parse($date)) : null;
                                        @endphp
                                        @if($date)
                                            <span class="badge bg-info">{{ \Carbon\Carbon::parse($date)->format('M Y') }}</span>
                                            @if($fyBadge)
                                                <span class="badge bg-secondary">FY {{ $fyBadge }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isOwner)
                                            <span class="badge bg-success">Executor</span>
                                        @elseif($isInCharge)
                                            <span class="badge bg-info">Applicant</span>
                                        @endif
                                    </td>
                                    <td class="project-status">
                                        @include('projects.partials.status-badge', ['project' => $project])
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->project_id) }}" class="btn btn-info">View</a>
                                        <a href="{{ route('monthly.report.create', ['project_id' => $project->project_id]) }}" class="btn btn-success">Write Report</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No approved projects found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $projects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
