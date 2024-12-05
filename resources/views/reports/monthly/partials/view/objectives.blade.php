{{-- resources/views/reports/monthly/partials/view/objectives.blade.php --}}
<!-- Objectives Section -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>Objectives</h4>
    </div>
    <div class="card-body">
        @foreach($report->objectives as $objective)
            <div class="mb-4 objective-card">
                <div class="card-header">
                    <h5>Objective {{ $loop->iteration }}</h5>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Objective:</strong></div>
                    <div class="col-6">{{ $objective->objective }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Expected Outcome:</strong></div>
                    <div class="col-6">
                        @if(is_array($objective->expected_outcome))
                            <ul>
                                @foreach($objective->expected_outcome as $outcome)
                                    <li>{{ $outcome }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ $objective->expected_outcome }}
                        @endif
                    </div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>What Did Not Happen:</strong></div>
                    <div class="col-6">
                        @if(is_array($objective->not_happened))
                            <ul>
                                @foreach($objective->not_happened as $notHappened)
                                    <li>{{ $notHappened }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ $objective->not_happened }}
                        @endif
                    </div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Why Some Activities Could Not Be Undertaken:</strong></div>
                    <div class="col-6">
                        @if(is_array($objective->why_not_happened))
                            <ul>
                                @foreach($objective->why_not_happened as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ $objective->why_not_happened }}
                        @endif
                    </div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Changes:</strong></div>
                    <div class="col-6">{{ $objective->changes ? 'Yes' : 'No' }}</div>
                </div>
                @if($objective->changes)
                    <div class="mb-2 row">
                        <div class="col-6"><strong>Why Changes Were Needed:</strong></div>
                        <div class="col-6">{{ $objective->why_changes }}</div>
                    </div>
                @endif
                <div class="mb-2 row">
                    <div class="col-6"><strong>Lessons Learnt:</strong></div>
                    <div class="col-6">{{ $objective->lessons_learnt }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>What Will Be Done Differently:</strong></div>
                    <div class="col-6">{{ $objective->todo_lessons_learnt }}</div>
                </div>

                <!-- Activities Section -->
                <div class="mb-3">
                    <h6>Activities</h6>
                    @foreach($objective->activities as $activity)
                        <div class="mb-2 row">
                            <div class="col-6"><strong>Month:</strong></div>
                            <div class="col-6">
                                @if(is_numeric($activity->month))
                                    {{ \Carbon\Carbon::createFromFormat('m', $activity->month)->format('F') }}
                                @else
                                    {{ $activity->month }}
                                @endif
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <div class="col-6"><strong>Summary of Activities:</strong></div>
                            <div class="col-6">
                                @if(is_array($activity->summary_activities))
                                    <ul>
                                        @foreach($activity->summary_activities as $summary)
                                            <li>{{ $summary }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $activity->summary_activities }}
                                @endif
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <div class="col-6"><strong>Qualitative & Quantitative Data:</strong></div>
                            <div class="col-6">
                                @if(is_array($activity->qualitative_quantitative_data))
                                    <ul>
                                        @foreach($activity->qualitative_quantitative_data as $data)
                                            <li>{{ $data }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $activity->qualitative_quantitative_data }}
                                @endif
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <div class="col-6"><strong>Intermediate Outcomes:</strong></div>
                            <div class="col-6">
                                @if(is_array($activity->intermediate_outcomes))
                                    <ul>
                                        @foreach($activity->intermediate_outcomes as $outcome)
                                            <li>{{ $outcome }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $activity->intermediate_outcomes }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
