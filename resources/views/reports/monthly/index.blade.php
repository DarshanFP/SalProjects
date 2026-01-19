@extends('executor.dashboard')

@section('content')
@php
    use App\Constants\ProjectStatus;
@endphp
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

                        /* Status badge styles */
                        .status-badge {
                            font-size: 11px;
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-weight: 500;
                        }
                    </style>

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <table class="table table-bordered monthly-reports-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Project ID</th>
                                <th style="width: 12%;">Report ID</th>
                                <th style="width: 15%;">Report Month Year</th>
                                <th style="width: 25%;">Project Title</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 21%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td>{{ $report->project_id }}</td>
                                    <td>{{ $report->report_id }}</td>
                                    <td>{{ $report->report_month_year ? \Carbon\Carbon::parse($report->report_month_year)->format('M Y') : 'N/A' }}</td>
                                    <td>{{ $report->project_title }}</td>
                                    <td>
                                        @php
                                            $statusClass = '';
                                            switch($report->status) {
                                                case ProjectStatus::DRAFT:
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                case ProjectStatus::SUBMITTED_TO_PROVINCIAL:
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case ProjectStatus::REVERTED_BY_PROVINCIAL:
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case ProjectStatus::FORWARDED_TO_COORDINATOR:
                                                    $statusClass = 'bg-primary';
                                                    break;
                                                case ProjectStatus::REVERTED_BY_COORDINATOR:
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case ProjectStatus::APPROVED_BY_COORDINATOR:
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case ProjectStatus::REJECTED_BY_COORDINATOR:
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                        @endphp
                                        <span class="badge {{ $statusClass }} status-badge">
                                            {{ \App\Models\Reports\Monthly\DPReport::$statusLabels[$report->status] ?? $report->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('monthly.report.show', $report->report_id) }}" class="btn btn-info btn-sm">View</a>

                                        @php
                                            $editableStatuses = ProjectStatus::getEditableStatuses();
                                        @endphp
                                        @if(auth()->user()->role === 'executor')
                                            @if(in_array($report->status, $editableStatuses))
                                                <a href="{{ route('monthly.report.edit', $report->report_id) }}" class="btn btn-warning btn-sm">Edit</a>
                                                <form action="{{ route('monthly.report.submit', $report->report_id) }}" method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to submit this report?')">Submit</button>
                                                </form>
                                            @endif
                                        @endif

                                        @if(auth()->user()->role === 'provincial')
                                            @if($report->status === ProjectStatus::SUBMITTED_TO_PROVINCIAL)
                                                <form action="{{ route('monthly.report.forward', $report->report_id) }}" method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Forward to Coordinator?')">Forward</button>
                                                </form>
                                                <button type="button" class="btn btn-warning btn-sm" onclick="showRevertModal('{{ $report->report_id }}')">Revert</button>
                                            @endif
                                            @if($report->status === ProjectStatus::REVERTED_BY_COORDINATOR)
                                                <button type="button" class="btn btn-warning btn-sm" onclick="showRevertModal('{{ $report->report_id }}')">Revert to Executor</button>
                                            @endif
                                        @endif

                                        @if(auth()->user()->role === 'coordinator')
                                            @if($report->status === ProjectStatus::FORWARDED_TO_COORDINATOR)
                                                <form action="{{ route('monthly.report.approve', $report->report_id) }}" method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this report?')">Approve</button>
                                                </form>
                                                <button type="button" class="btn btn-warning btn-sm" onclick="showRevertModal('{{ $report->report_id }}')">Revert</button>
                                            @endif
                                        @endif
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

<!-- Revert Modal -->
<div class="modal fade" id="revertModal" tabindex="-1" aria-labelledby="revertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="revertModalLabel">Revert Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="revertForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="revert_reason" class="form-label">Reason for Reverting</label>
                        <textarea class="form-control auto-resize-textarea" id="revert_reason" name="revert_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Revert Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRevertModal(reportId) {
    const modal = new bootstrap.Modal(document.getElementById('revertModal'));
    const form = document.getElementById('revertForm');
    form.action = `/reports/monthly/revert/${reportId}`;
    modal.show();
}
</script>
@endsection
