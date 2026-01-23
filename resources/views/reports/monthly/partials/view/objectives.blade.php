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
                    <div class="col-2 report-label-col"><strong>Objective:</strong></div>
                    <div class="col-10 report-value-col">{{ $objective->objective }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-2 report-label-col"><strong>Expected Outcome:</strong></div>
                    <div class="col-10 report-value-col">
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
                    <div class="col-2 report-label-col"><strong>What Did Not Happen:</strong></div>
                    <div class="col-10 report-value-col report-value-entered">
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
                    <div class="col-2 report-label-col"><strong>Why Some Activities Could Not Be Undertaken:</strong></div>
                    <div class="col-10 report-value-col report-value-entered">
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
                    <div class="col-2 report-label-col"><strong>Changes:</strong></div>
                    <div class="col-10 report-value-col report-value-entered">{{ $objective->changes ? 'Yes' : 'No' }}</div>
                </div>
                @if($objective->changes)
                    <div class="mb-2 row">
                        <div class="col-2 report-label-col"><strong>Why Changes Were Needed:</strong></div>
                        <div class="col-10 report-value-col report-value-entered">{{ $objective->why_changes }}</div>
                    </div>
                @endif
                <div class="mb-2 row">
                    <div class="col-2 report-label-col"><strong>Lessons Learnt:</strong></div>
                    <div class="col-10 report-value-col report-value-entered">{{ $objective->lessons_learnt }}</div>
                </div>
                <div class="mb-2 row">
                    <div class="col-2 report-label-col"><strong>What Will Be Done Differently:</strong></div>
                    <div class="col-10 report-value-col report-value-entered">{{ $objective->todo_lessons_learnt }}</div>
                </div>

                <!-- Activities Section -->
                <div class="mb-3">
                    <h6>Activities</h6>
                    @foreach($objective->activities as $activity)
                        <h6 class="activity-block-heading mb-2 mt-2 ps-2 border-start border-2 border-primary text-white fw-bold">Activity {{ $loop->iteration }} of {{ $loop->count }}: {{ $activity->activity ?? '—' }}@if(in_array(auth()->user()->role ?? '', ['provincial','coordinator']) && in_array($report->status ?? '', ['submitted_to_provincial','forwarded_to_coordinator'])) @php $st = ($reportedActivityScheduleStatus ?? [])[$activity->activity_id] ?? 'not_scheduled_reported'; @endphp<span class="badge {{ $st === 'scheduled_reported' ? 'bg-success' : 'bg-warning text-dark' }} ms-2">{{ $st === 'scheduled_reported' ? 'SCHEDULED – REPORTED' : 'NOT SCHEDULED – REPORTED' }}</span>@endif</h6>
                        <div class="mb-2 row">
                            <div class="col-2 report-label-col"><strong>Month:</strong></div>
                            <div class="col-10 report-value-col report-value-entered">
                                @if(is_numeric($activity->month))
                                    {{ \Carbon\Carbon::createFromFormat('m', $activity->month)->format('F') }}
                                @else
                                    {{ $activity->month }}
                                @endif
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <div class="col-2 report-label-col"><strong>Summary of Activities:</strong></div>
                            <div class="col-10 report-value-col report-value-entered">
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
                            <div class="col-2 report-label-col"><strong>Qualitative & Quantitative Data:</strong></div>
                            <div class="col-10 report-value-col report-value-entered">
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
                            <div class="col-2 report-label-col"><strong>Intermediate Outcomes:</strong></div>
                            <div class="col-10 report-value-col report-value-entered">
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

                        @php $activityPhotos = $activity->photos ?? collect(); @endphp
                        @if($activityPhotos->isNotEmpty())
                            <div class="mb-2 row">
                                <div class="col-12"><strong>Photos:</strong></div>
                                <div class="col-12">
                                    <div class="row justify-content-start">
                                        @foreach($activityPhotos as $photo)
                                            <div class="mb-3 col-md-4 col-sm-6 col-xs-12">
                                                <div class="photo-wrapper">
                                                    @php $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->photo_path); @endphp
                                                    @if($fileExists)
                                                        <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" class="img-thumbnail" onclick="openImageModal('{{ asset('storage/' . $photo->photo_path) }}', '{{ $photo->photo_name ?? 'Photo' }}')" style="cursor: pointer;">
                                                        <div class="mt-2">
                                                            <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> View Full Size</a>
                                                        </div>
                                                        @if(!empty($photo->photo_location))<div class="mt-1" style="font-size: 1.5rem;">{{ $photo->photo_location }}</div>@endif
                                                    @else
                                                        <div class="text-center text-danger">
                                                            <i class="mb-2 fas fa-exclamation-triangle fa-3x"></i>
                                                            <p>Photo not found</p>
                                                            <small>{{ $photo->photo_name ?? 'Unknown file' }}</small>
                                                            <br>
                                                            <small class="text-muted">Path: {{ $photo->photo_path }}</small>
                                                        </div>
                                                        @if(!empty($photo->photo_location))<div class="mt-1" style="font-size: 1.5rem;">{{ $photo->photo_location }}</div>@endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

            </div>
        @endforeach
    </div>
</div>
