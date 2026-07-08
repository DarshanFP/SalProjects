<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Report PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .info-table, .details-table, .activities-table, .account-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        .section-header {
            page-break-after: avoid;
        }
        .info-table td, .details-table td, .activities-table td, .account-table td {
            padding: 5px 10px;
            border: 1px solid #ddd;
        }
        .info-label, .details-label { font-weight: bold; width: 30%; }
        .section-header {
            background-color: #f2f2f2;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            border-left: 4px solid #007bff;
        }
        .header-row { background-color: #f2f2f2; font-weight: bold; }
        .photo-container {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .photo {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .photo-description {
            font-style: italic;
            color: #666;
            margin-top: 5px;
            font-size: 12px;
        }
        .budget-row { background-color: #e8f4fd; }
        .budget-badge {
            background-color: #0f766e;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
        }
        .total-row {
            background-color: #d4edda;
            font-weight: bold;
            border-top: 2px solid #28a745;
        }
        .photo-category {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f8f9fa;
            border-left: 3px solid #28a745;
        }
        .photo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
        }
        .photo-item {
            width: calc(33.33% - 7px);
            min-width: 120px;
            max-width: 150px;
            text-align: center;
            margin-bottom: 15px;
        }
        .photo-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .no-photos {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
        }
        .page-break {
            page-break-before: always;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <h1>Monthly Report</h1>

    <!-- Basic Information -->
    <div class="section-header">Basic Information</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Report ID:</td>
            <td>{{ $report->report_id }}</td>
        </tr>
        <tr>
            <td class="info-label">Project Type:</td>
            <td>{{ $report->project_type }}</td>
        </tr>
        <tr>
            <td class="info-label">Project Title:</td>
            <td>{{ $report->project_title }}</td>
        </tr>
        <tr>
            <td class="info-label">Society Name:</td>
            <td>{{ $report->society_name }}</td>
        </tr>
        <tr>
            <td class="info-label">Place:</td>
            <td>{{ $report->place }}</td>
        </tr>
        <tr>
            <td class="info-label">In Charge:</td>
            <td>{{ $report->in_charge }}</td>
        </tr>
        <tr>
            <td class="info-label">Total Beneficiaries:</td>
            <td>{{ $report->total_beneficiaries }}</td>
        </tr>
        <tr>
            <td class="info-label">Goal:</td>
            <td>{{ $report->goal }}</td>
        </tr>
        <tr>
            <td class="info-label">Report Month & Year:</td>
            <td>{{ $report->report_month_year ? \Carbon\Carbon::parse($report->report_month_year)->format('F Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Commencement Month & Year:</td>
            <td>{{ $report->commencement_month_year ? \Carbon\Carbon::parse($report->commencement_month_year)->format('F Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Submitted By:</td>
            <td>{{ $report->user->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Submission Date:</td>
            <td>{{ $report->created_at ? $report->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
        </tr>
    </table>

    <!-- Project Specific Information -->
    @if($report->project_type == 'Livelihood Development Projects' && !empty($annexures))
        <div class="section-header">Annexed Target Group</div>
        <table class="details-table">
            <tr class="header-row">
                <td>Beneficiary Name</td>
                <td>Support Date</td>
                <td>Self Employment</td>
                <td>Amount Sanctioned</td>
                <td>Monthly Profit</td>
                <td>Annual Profit</td>
                <td>Impact</td>
                <td>Challenges</td>
            </tr>
            @foreach($annexures as $annexure)
                <tr>
                    <td>{{ $annexure->dla_beneficiary_name ?? 'N/A' }}</td>
                    <td>{{ $annexure->dla_support_date ?? 'N/A' }}</td>
                    <td>{{ $annexure->dla_self_employment ?? 'N/A' }}</td>
                    <td>{{ $annexure->dla_amount_sanctioned ? format_indian_currency($annexure->dla_amount_sanctioned, 2) : 'N/A' }}</td>
                    <td>{{ $annexure->dla_monthly_profit ? format_indian_currency($annexure->dla_monthly_profit, 2) : 'N/A' }}</td>
                    <td>{{ $annexure->dla_annual_profit ? format_indian_currency($annexure->dla_annual_profit, 2) : 'N/A' }}</td>
                    <td>{{ $annexure->dla_impact ?? 'N/A' }}</td>
                    <td>{{ $annexure->dla_challenges ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($report->project_type == 'Institutional Ongoing Group Educational proposal' && !empty($ageProfiles))
        <div class="section-header">Age Profiles</div>
        <table class="details-table">
            <tr class="header-row">
                <td>Age Group</td>
                <td>Education</td>
                <td>Up to Previous Year</td>
                <td>Present Academic Year</td>
            </tr>
            @foreach($ageProfiles as $profile)
                <tr>
                    <td>{{ $profile->age_group ?? 'N/A' }}</td>
                    <td>{{ $profile->education ?? 'N/A' }}</td>
                    <td>{{ $profile->up_to_previous_year ?? 0 }}</td>
                    <td>{{ $profile->present_academic_year ?? 0 }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($report->project_type == 'Residential Skill Training Proposal 2' && !empty($traineeProfiles))
        <div class="section-header">Trainee Profiles</div>
        <table class="details-table">
            <tr class="header-row">
                <td>Education Category</td>
                <td>Number</td>
            </tr>
            @foreach($traineeProfiles as $trainee)
                <tr>
                    <td>{{ $trainee->education_category ?? 'N/A' }}</td>
                    <td>{{ $trainee->number ?? 0 }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($report->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' && !empty($inmateProfiles))
        <div class="section-header">Inmate Profiles</div>
        <table class="details-table">
            <tr class="header-row">
                <td>Name</td>
                <td>Age</td>
                <td>Gender</td>
                <td>Case Type</td>
                <td>Status</td>
            </tr>
            @foreach($inmateProfiles as $inmate)
                <tr>
                    <td>{{ $inmate->name ?? 'N/A' }}</td>
                    <td>{{ $inmate->age ?? 'N/A' }}</td>
                    <td>{{ $inmate->gender ?? 'N/A' }}</td>
                    <td>{{ $inmate->case_type ?? 'N/A' }}</td>
                    <td>{{ $inmate->status ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <!-- Objectives and Activities -->
    @if($report->objectives && $report->objectives->count() > 0)
        <div class="section-header">Objectives and Activities</div>
        @foreach($report->objectives as $objective)
            <div class="avoid-break">
                <h3>Objective: {{ $objective->objective ?? 'N/A' }}</h3>
                <p><strong>Expected Outcome:</strong> {{ is_array($objective->expected_outcome) ? implode(', ', $objective->expected_outcome) : $objective->expected_outcome ?? 'N/A' }}</p>
                <p><strong>What Did Not Happen:</strong> {{ $objective->not_happened ?? 'N/A' }}</p>
                <p><strong>Why Some Activities Could Not Be Undertaken:</strong> {{ $objective->why_not_happened ?? 'N/A' }}</p>
                <p><strong>Changes:</strong> {{ $objective->changes ? 'Yes' : 'No' }}</p>
                @if($objective->changes)
                    <p><strong>Why Changes Were Needed:</strong> {{ $objective->why_changes ?? 'N/A' }}</p>
                @endif
                <p><strong>Lessons Learnt:</strong> {{ $objective->lessons_learnt ?? 'N/A' }}</p>
                <p><strong>What Will Be Done Differently:</strong> {{ $objective->todo_lessons_learnt ?? 'N/A' }}</p>

                @if($objective->activities && $objective->activities->count() > 0)
                    <table class="activities-table">
                        <tr class="header-row">
                            <td>No.</td>
                            <td>Activity</td>
                            <td>Month</td>
                            <td>Summary of Activities</td>
                            <td>Qualitative & Quantitative Data</td>
                            <td>Intermediate Outcomes</td>
                        </tr>
                        @foreach($objective->activities as $activity)
                            <tr>
                                <td>{{ $loop->iteration }} of {{ $loop->count }}</td>
                                <td>{{ $activity->activity ?? 'N/A' }}</td>
                                <td>{{ $activity->month ?? 'N/A' }}</td>
                                <td>{{ $activity->summary_activities ?? 'N/A' }}</td>
                                <td>{{ $activity->qualitative_quantitative_data ?? 'N/A' }}</td>
                                <td>{{ $activity->intermediate_outcomes ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <p><em>No activities found for this objective.</em></p>
                @endif
            </div>
        @endforeach
    @else
        <div class="section-header">Objectives and Activities</div>
        <p><em>No objectives found for this report.</em></p>
    @endif

    <!-- Outlooks -->
    @if($report->outlooks && $report->outlooks->count() > 0)
        <div class="section-header">Outlooks</div>
        <table class="details-table">
            <tr class="header-row">
                <td>Date</td>
                <td>Action Plan for Next Month</td>
            </tr>
            @foreach($report->outlooks as $outlook)
                <tr>
                    <td>{{ $outlook->date ? \Carbon\Carbon::parse($outlook->date)->format('d-m-Y') : 'N/A' }}</td>
                    <td>{{ $outlook->plan_next_month ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="section-header">Outlooks</div>
        <p><em>No outlooks found for this report.</em></p>
    @endif

    <!-- Statements of Account -->
    @if($budgets && count($budgets) > 0)
        <div class="section-header">Statements of Account</div>
        <table class="account-table">
            <tr class="header-row">
                <td>Particulars</td>
                <td>Amount Sanctioned</td>
                <td>Total Amount</td>
                <td>Expenses Last Month</td>
                <td>Expenses This Month</td>
                <td>Total Expenses</td>
                <td>Balance Amount</td>
            </tr>
            @foreach($budgets as $budget)
                <tr class="{{ $budget->is_budget_row ? 'budget-row' : '' }}">
                    <td>
                        {{ $budget->particulars ?? 'N/A' }}
                        @if($budget->is_budget_row)
                            <span class="budget-badge">Budget Row</span>
                        @endif
                    </td>
                    <td>{{ format_indian($budget->amount_sanctioned ?? 0, 2) }}</td>
                    <td>{{ format_indian($budget->total_amount ?? 0, 2) }}</td>
                    <td>{{ format_indian($budget->expenses_last_month ?? 0, 2) }}</td>
                    <td>{{ format_indian($budget->expenses_this_month ?? 0, 2) }}</td>
                    <td>{{ format_indian($budget->total_expenses ?? 0, 2) }}</td>
                    <td>{{ format_indian($budget->balance_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
            {{-- Total Row --}}
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td><strong>{{ format_indian($budgets->sum('amount_sanctioned'), 2) }}</strong></td>
                <td><strong>{{ format_indian($budgets->sum('total_amount'), 2) }}</strong></td>
                <td><strong>{{ format_indian($budgets->sum('expenses_last_month'), 2) }}</strong></td>
                <td><strong>{{ format_indian($budgets->sum('expenses_this_month'), 2) }}</strong></td>
                <td><strong>{{ format_indian($budgets->sum('total_expenses'), 2) }}</strong></td>
                <td><strong>{{ format_indian($budgets->sum('balance_amount'), 2) }}</strong></td>
            </tr>
        </table>
    @else
        <div class="section-header">Statements of Account</div>
        <p><em>No budget data found for this report.</em></p>
    @endif

    <!-- Photos and Documentation -->
    @if(isset($excludePhotos) && $excludePhotos)
        <div class="section-header">Photos and Documentation</div>
        <div class="no-photos">
            <p>Photos were excluded from this PDF due to file size limitations.</p>
            <p>Please view the report online to see all photos and documentation.</p>
        </div>
    @elseif($groupedPhotos && count($groupedPhotos) > 0)
        <div class="section-header">Photos and Documentation</div>
        @if(isset($totalPhotos) && $totalPhotos >= 15)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                <p><strong>Note:</strong> Only the first 15 photos are included in this PDF due to file size limitations. Please view the report online to see all photos.</p>
            </div>
        @endif
        @foreach($groupedPhotos as $category => $photos)
            <div class="photo-container avoid-break">
                <div class="photo-category">{{ $category }}</div>
                <div class="photo-grid">
                    @foreach($photos as $photo)
                        <div class="photo-item">
                            @if($photo['file_exists'] && $photo['full_path'])
                                <img src="{{ $photo['full_path'] }}" alt="{{ $photo['photo_name'] }}" class="photo">
                            @else
                                <div style="width: 100%; height: 80px; background-color: #f8f9fa; border: 1px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666; border-radius: 4px;">
                                    Photo Not Found
                                </div>
                            @endif
                            <div class="photo-description">{{ $photo['photo_name'] }}</div>
                            @if($photo['description'])
                                <div class="photo-description">{{ $photo['description'] }}</div>
                            @endif
                            @if(!empty($photo['photo_location']))
                                <div class="photo-location" style="font-size: 10pt;">{{ $photo['photo_location'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="section-header">Photos and Documentation</div>
        <div class="no-photos">
            <p>No photos or documentation found for this report.</p>
        </div>
    @endif

    <!-- Type-Specific Annexures & Profiles -->
    @if(isset($annexures) && count($annexures) > 0)
        <div class="section-header">Project's Impact in the Life of the Beneficiaries</div>
        @foreach($annexures as $index => $annexure)
            <div style="margin-bottom: 15px; page-break-inside: avoid;">
                <div style="font-weight: bold; background-color: #e9ecef; padding: 5px; margin-bottom: 5px;">Impact {{ $index + 1 }}</div>
                <table class="details-table" style="margin-bottom: 10px;">
                    <tr><td class="details-label">Beneficiary Name</td><td>{{ $annexure->dla_beneficiary_name }}</td></tr>
                    <tr><td class="details-label">Date of Support</td><td>{{ \Carbon\Carbon::parse($annexure->dla_support_date)->format('d-m-Y') }}</td></tr>
                    <tr><td class="details-label">Self-Employment Nature</td><td>{{ $annexure->dla_self_employment }}</td></tr>
                    <tr><td class="details-label">Amount Sanctioned</td><td>{{ format_indian($annexure->dla_amount_sanctioned ?? 0, 2) }}</td></tr>
                    <tr><td class="details-label">Monthly Profit Gained</td><td>{{ format_indian($annexure->dla_monthly_profit ?? 0, 2) }}</td></tr>
                    <tr><td class="details-label">Annual Profit Gained</td><td>{{ format_indian($annexure->dla_annual_profit ?? 0, 2) }}</td></tr>
                    <tr><td class="details-label">Impact</td><td>{{ $annexure->dla_impact }}</td></tr>
                    <tr><td class="details-label">Challenges Faced</td><td>{{ $annexure->dla_challenges }}</td></tr>
                </table>
            </div>
        @endforeach
    @endif

    @if(isset($ageProfiles) && count($ageProfiles) > 0)
        <div class="section-header">Age Profile of Children in the Institution</div>
        <table class="account-table">
            <tr class="header-row">
                <td>Age Group</td>
                <td>Education</td>
                <td>Up to Previous Year</td>
                <td>Present Academic Year</td>
            </tr>
            @php
                $ageGroups = [
                    'Children below 5 years' => 'below_5',
                    'Children between 6 to 10 years' => '6_10',
                    'Children between 11 to 15 years' => '11_15',
                    '16 and above' => '16_above',
                ];
                $ageProfilesGrouped = $ageProfiles->groupBy('age_group');
            @endphp
            @foreach($ageGroups as $ageGroup => $prefix)
                @php
                    $ageGroupData = $ageProfilesGrouped->get($ageGroup, collect());
                    $ageGroupEntries = $ageGroupData->where('education', '!=', 'Total')->values();
                    $totalEntry = $ageGroupData->where('education', 'Total')->first();
                @endphp
                @foreach($ageGroupEntries as $entry)
                    <tr>
                        <td>{{ $ageGroup }}</td>
                        <td>{{ $entry->education }}</td>
                        <td>{{ $entry->up_to_previous_year }}</td>
                        <td>{{ $entry->present_academic_year }}</td>
                    </tr>
                @endforeach
                @if($totalEntry)
                    <tr class="budget-row">
                        <td colspan="2"><strong>Total {{ $ageGroup }}</strong></td>
                        <td><strong>{{ $totalEntry->up_to_previous_year }}</strong></td>
                        <td><strong>{{ $totalEntry->present_academic_year }}</strong></td>
                    </tr>
                @endif
            @endforeach
            @php
                $grandTotalEntry = $ageProfiles->where('age_group', 'All Categories')->where('education', 'Grand Total')->first();
            @endphp
            @if($grandTotalEntry)
                <tr class="total-row">
                    <td colspan="2"><strong>Grand Total</strong></td>
                    <td><strong>{{ $grandTotalEntry->up_to_previous_year }}</strong></td>
                    <td><strong>{{ $grandTotalEntry->present_academic_year }}</strong></td>
                </tr>
            @endif
        </table>
    @endif

    @if(isset($report->education) && !empty($report->education))
        <div class="section-header">Information about the Trainees</div>
        <table class="account-table">
            <tr class="header-row">
                <td>Education of Trainees</td>
                <td>Number</td>
            </tr>
            <tr><td>Below 9th standard</td><td>{{ $report->education['below_9'] ?? 0 }}</td></tr>
            <tr><td>10th class failed</td><td>{{ $report->education['class_10_fail'] ?? 0 }}</td></tr>
            <tr><td>10th class passed</td><td>{{ $report->education['class_10_pass'] ?? 0 }}</td></tr>
            <tr><td>Intermediate</td><td>{{ $report->education['intermediate'] ?? 0 }}</td></tr>
            <tr><td>Intermediate and above</td><td>{{ $report->education['above_intermediate'] ?? 0 }}</td></tr>
            <tr><td>{{ $report->education['other'] ?? 'Other (if any)' }}</td><td>{{ $report->education['other_count'] ?? 0 }}</td></tr>
            <tr class="total-row"><td><strong>Total</strong></td><td><strong>{{ $report->education['total'] ?? 0 }}</strong></td></tr>
        </table>
    @endif

    @if(isset($inmateProfiles) && count($inmateProfiles) > 0)
        <div class="section-header">Profile of Inmates for the Last Four Months</div>
        <table class="account-table">
            <tr class="header-row">
                <td>Age Category</td>
                <td>Status</td>
                <td>Number</td>
            </tr>
            @php
                $cicGroups = [
                    'Children below 18 yrs' => 'children_below_18',
                    'Women between 18 – 30 years' => 'women_18_30',
                    'Women between 31 – 50 years' => 'women_31_50',
                    'Women above 50' => 'women_above_50',
                ];
                $cicStatuses = ['unmarried', 'married', 'divorcee', 'deserted'];
                $cicProfilesGrouped = $inmateProfiles->groupBy('age_category');
            @endphp
            @foreach($cicGroups as $ageGroupName => $ageGroupKey)
                @php
                    $profiles = $cicProfilesGrouped->get($ageGroupName, collect());
                    $profilesByStatus = $profiles->groupBy(fn($item) => strtolower(trim($item->status)));
                @endphp
                @foreach($cicStatuses as $status)
                    @php $cnt = $profilesByStatus->get(strtolower($status))?->sum('number') ?? 0; @endphp
                    <tr>
                        <td>{{ $ageGroupName }}</td>
                        <td>{{ ucfirst($status) }}</td>
                        <td>{{ $cnt }}</td>
                    </tr>
                @endforeach
                @php
                    $tot = $profilesByStatus->get('total')?->sum('number') ?? 0;
                @endphp
                <tr class="budget-row">
                    <td colspan="2"><strong>Total {{ $ageGroupName }}</strong></td>
                    <td><strong>{{ $tot }}</strong></td>
                </tr>
            @endforeach
        </table>
    @endif

    <!-- Comments by Project Monitoring Committee (PMC) -->
    @if(trim((string)($report->pmc_comments ?? '')) !== '')
        <div class="section-header">Comments by Project Monitoring Committee</div>
        <div class="avoid-break" style="margin-bottom: 20px;">{!! nl2br(e(trim($report->pmc_comments))) !!}</div>
    @endif

    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
        <p><strong>Report Generated:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Generated By:</strong> {{ Auth::user()->name ?? 'System' }}</p>
    </div>
</body>
</html>
