@php
    $isProject = ($type ?? 'project') === 'project';
    $contextLabel = $context === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : ($context === 'direct_team' ? 'Direct Team' : 'All');
    $contextBadgeClass = $context === 'coordinator_hierarchy' ? 'bg-primary' : ($context === 'direct_team' ? 'bg-success' : 'bg-secondary');
@endphp

<div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
    <table class="table table-sm table-hover pending-approvals-table">
        <thead class="thead-light sticky-top">
            <tr>
                @if($context === 'all')
                    <th style="width: 140px;">Context</th>
                @endif
                <th style="width: 120px;">{{ $isProject ? 'Project ID' : 'Report ID' }}</th>
                <th style="width: 200px; min-width: 150px; max-width: 250px;">{{ $isProject ? 'Title' : 'Project' }}</th>
                <th style="width: 140px;">Executor/Applicant</th>
                @if($context !== 'direct_team')
                    <th style="width: 120px;">Province</th>
                    <th style="width: 140px;">Provincial</th>
                @else
                    <th style="width: 120px;">Province</th>
                    <th style="width: 140px;">Center</th>
                @endif
                <th style="width: 120px;">Days Pending</th>
                <th style="width: 100px;">Priority</th>
                <th style="width: auto;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $daysPending = $item->days_pending ?? $item->created_at->diffInDays(now());
                    $urgency = $item->urgency ?? ($daysPending > 7 ? 'urgent' : ($daysPending > 3 ? 'normal' : 'low'));
                    $urgencyClass = $urgency === 'urgent' ? 'danger' : ($urgency === 'normal' ? 'warning' : 'success');
                    $urgencyBadge = $urgency === 'urgent' ? 'Urgent' : ($urgency === 'normal' ? 'Normal' : 'Low');

                    $itemContext = $item->context ?? $context;
                    $itemContextLabel = $itemContext === 'coordinator_hierarchy' ? 'Coordinator Hierarchy' : 'Direct Team';
                    $itemContextBadgeClass = $itemContext === 'coordinator_hierarchy' ? 'bg-primary' : 'bg-success';

                    if ($isProject) {
                        $itemId = $item->project_id;
                        $itemTitle = $item->project_title ?? 'N/A';
                        $showRoute = route('general.showProject', $itemId);
                        $approveRoute = route('general.approveProject', $itemId);
                        $revertRoute = route('general.revertProject', $itemId);
                        $downloadRoute = route('projects.downloadPdf', $itemId);
                        $approvalContext = $itemContext === 'coordinator_hierarchy' ? 'coordinator' : 'provincial';
                    } else {
                        $itemId = $item->report_id;
                        $itemTitle = $item->project_title ?? $item->report_id ?? 'N/A';
                        $showRoute = route('general.showReport', $itemId);
                        $approveRoute = route('general.approveReport', $itemId);
                        $revertRoute = route('general.revertReport', $itemId);
                        $downloadRoute = route('monthly.report.downloadPdf', $itemId);
                        $approvalContext = $itemContext === 'coordinator_hierarchy' ? 'coordinator' : 'provincial';
                    }
                @endphp
                <tr class="align-middle">
                    @if($context === 'all')
                        <td>
                            <span class="badge {{ $itemContextBadgeClass }}">{{ $itemContextLabel }}</span>
                        </td>
                    @endif
                    <td>
                        <a href="{{ $showRoute }}" class="text-decoration-none font-weight-bold">
                            {{ $itemId }}
                        </a>
                    </td>
                    <td class="text-wrap" style="word-wrap: break-word; white-space: normal; max-width: 250px;">
                        <small class="text-muted">{{ $itemTitle }}</small>
                    </td>
                    <td>
                        <small>{{ $item->user->name ?? 'N/A' }}</small>
                    </td>
                    @if($context !== 'direct_team')
                        <td>
                            <span class="badge bg-secondary">{{ $item->user->province ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <small>{{ ($item->provincial ?? $item->user->parent ?? null)?->name ?? 'N/A' }}</small>
                        </td>
                    @else
                        <td>
                            <span class="badge bg-secondary">{{ $item->user->province ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <small>{{ $item->user->center ?? 'N/A' }}</small>
                        </td>
                    @endif
                    <td>
                        <span class="badge bg-{{ $urgencyClass }}">{{ $daysPending }} days</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $urgencyClass }}">{{ $urgencyBadge }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="{{ $showRoute }}" class="btn btn-sm btn-primary">View</a>
                            <form method="POST" action="{{ $approveRoute }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="approval_context" value="{{ $approvalContext }}">
                                @if($isProject && $approvalContext === 'coordinator')
                                    {{-- Coordinator approval requires commencement date, handled in modal --}}
                                    <button type="button"
                                            class="btn btn-sm btn-success approve-project-btn"
                                            data-project-id="{{ $itemId }}"
                                            data-approval-context="{{ $approvalContext }}">
                                        Approve
                                    </button>
                                @else
                                    <button type="submit"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Approve this {{ $isProject ? 'project' : 'report' }}?');">
                                        Approve
                                    </button>
                                @endif
                            </form>
                            <button type="button"
                                    class="btn btn-sm btn-warning revert-item-btn"
                                    data-item-id="{{ $itemId }}"
                                    data-item-title="{{ $itemTitle }}"
                                    data-item-type="{{ $isProject ? 'project' : 'report' }}"
                                    data-approval-context="{{ $approvalContext }}">
                                Revert
                            </button>
                            <a href="{{ $downloadRoute }}"
                               class="btn btn-sm btn-secondary"
                               target="_blank">
                                Download PDF
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Styles moved to public/css/custom/common-tables.css --}}
