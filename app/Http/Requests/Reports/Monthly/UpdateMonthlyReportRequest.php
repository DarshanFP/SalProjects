<?php

namespace App\Http\Requests\Reports\Monthly;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Reports\Monthly\DPReport;
use Illuminate\Support\Facades\Log;

class UpdateMonthlyReportRequest extends FormRequest
{
    private ?string $authFailureReason = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $reportId = $this->route('report_id');
        $context = [
            'report_id' => $reportId,
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role,
            'save_as_draft' => $this->input('save_as_draft'),
        ];

        if (!auth()->check()) {
            $this->authFailureReason = 'unauthenticated';
            Log::warning('Monthly report update denied: user not authenticated', $context);

            return false;
        }

        $user = auth()->user();
        $report = DPReport::with(['project', 'user'])->where('report_id', $reportId)->first();

        if (!$report) {
            $this->authFailureReason = 'report_not_found';
            Log::warning('Monthly report update denied: report not found', $context);

            return false;
        }

        $context['report_status'] = $report->status;
        $context['report_user_id'] = $report->user_id;
        $context['project_id'] = $report->project_id;
        $context['is_editable'] = $report->isEditable();
        $context['allowed_statuses'] = DPReport::executorEditableStatuses();

        if ($user->role === 'coordinator') {
            Log::info('Monthly report update authorized for coordinator', $context);
            return true;
        }

        if ($user->role === 'provincial') {
            // Provincial can update reports from executors under them
            $isParent = $report->user && (int) $report->user->parent_id === (int) $user->id;
            if (!$isParent) {
                $this->authFailureReason = 'not_executor_parent';
                Log::warning('Monthly report update denied: provincial is not executor parent', $context);
                return false;
            }
            Log::info('Monthly report update authorized for provincial', $context);
            return true;
        }

        if (in_array($user->role, ['executor', 'applicant'], true)) {
            if (!$report->isEditable()) {
                $this->authFailureReason = 'status_not_editable';
                Log::warning('Monthly report update denied: status not editable', $context);

                return false;
            }

            $isOwner = (int) $report->user_id === (int) $user->id;
            $isInCharge = $report->project && (int) $report->project->in_charge === (int) $user->id;

            if (!$isOwner && !$isInCharge) {
                $this->authFailureReason = 'not_owner_or_in_charge';
                $context['project_in_charge'] = $report->project?->in_charge;
                Log::warning('Monthly report update denied: user is not owner or in-charge', $context);

                return false;
            }

            Log::info('Monthly report update authorized', array_merge($context, [
                'authorized_via' => $isOwner ? 'owner' : 'in_charge',
            ]));

            return true;
        }

        $this->authFailureReason = 'invalid_role';
        Log::warning('Monthly report update denied: invalid role', $context);
        return false;
    }

    /**
     * Log and respond when authorize() returns false.
     */
    protected function failedAuthorization(): void
    {
        Log::warning('UpdateMonthlyReportRequest failed authorization', [
            'reason' => $this->authFailureReason ?? 'unknown',
            'report_id' => $this->route('report_id'),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role,
        ]);

        parent::failedAuthorization();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

        // Same rules as StoreMonthlyReportRequest
        return [
            // Basic project information
            'project_id' => 'required|string|max:255', // Always required
            'save_as_draft' => 'nullable|boolean',
            'project_title' => 'nullable|string|max:255',
            'project_type' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'society_name' => 'nullable|string|max:255',
            'commencement_month_year' => 'nullable|date',
            'in_charge' => 'nullable|string|max:255',
            'total_beneficiaries' => 'nullable|integer|min:0',

            // Reporting period - required only if not draft
            'report_month' => $isDraft ? 'nullable|integer|between:1,12' : 'required|integer|between:1,12',
            'report_year' => $isDraft ? 'nullable|integer|min:2020|max:' . (date('Y') + 1) : 'required|integer|min:2020|max:' . (date('Y') + 1),
            'goal' => 'nullable|string',

            // Accounting period
            'account_period_start' => 'nullable|date',
            'account_period_end' => 'nullable|date|after_or_equal:account_period_start',

            // Photos and descriptions
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|array',
            'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'photo_descriptions' => 'nullable|array',
            'photo_descriptions.*' => 'nullable|string|max:1000',
            'photo_activity_id' => 'nullable|array',
            'photo_activity_id.*' => 'nullable|string|max:255',

            // Multiple Attachments
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'attachment_descriptions' => 'nullable|array',
            'attachment_descriptions.*' => 'nullable|string|max:1000',

            // New Attachments for Updates
            'new_attachment_files' => 'nullable|array',
            'new_attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'new_attachment_names' => 'nullable|array',
            'new_attachment_names.*' => 'nullable|string|max:255',
            'new_attachment_descriptions' => 'nullable|array',
            'new_attachment_descriptions.*' => 'nullable|string|max:1000',

            // Legacy single attachment
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            'file_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',

            // Objectives
            'objective' => 'nullable|array',
            'objective.*' => 'nullable|string',
            'expected_outcome' => 'nullable|array',
            'expected_outcome.*' => 'nullable|array',
            'expected_outcome.*.*' => 'nullable|string',

            // User-input fields per objective
            'not_happened' => 'nullable|array',
            'not_happened.*' => 'nullable|string',
            'why_not_happened' => 'nullable|array',
            'why_not_happened.*' => 'nullable|string',
            'changes' => 'nullable|array',
            'changes.*' => 'nullable|string|in:yes,no',
            'why_changes' => 'nullable|array',
            'why_changes.*' => 'nullable|string',
            'lessons_learnt' => 'nullable|array',
            'lessons_learnt.*' => 'nullable|string',
            'todo_lessons_learnt' => 'nullable|array',
            'todo_lessons_learnt.*' => 'nullable|string',

            // Activities
            'activity' => 'nullable|array',
            'activity.*' => 'nullable|array',
            'activity.*.*' => 'nullable|string',
            'month' => 'nullable|array',
            'month.*' => 'nullable|array',
            'month.*.*' => 'nullable|integer|between:1,12',
            'summary_activities' => 'nullable|array',
            'summary_activities.*' => 'nullable|array',
            'summary_activities.*.*' => 'nullable|array',
            'summary_activities.*.*.*' => 'nullable|string',

            // Outlooks
            'date' => 'nullable|array',
            'date.*' => 'nullable|date',
            'plan_next_month' => 'nullable|array',
            'plan_next_month.*' => 'nullable|string',

            // Statements of Account - required only if not draft
            'particulars' => $isDraft ? 'nullable|array' : 'required|array',
            'particulars.*' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'amount_forwarded' => 'nullable|array',
            'amount_forwarded.*' => 'nullable|numeric|min:0',
            'amount_sanctioned' => 'nullable|array',
            'amount_sanctioned.*' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|array',
            'total_amount.*' => 'nullable|numeric|min:0',
            'expenses_last_month' => 'nullable|array',
            'expenses_last_month.*' => 'nullable|numeric|min:0',
            'expenses_this_month' => 'nullable|array',
            'expenses_this_month.*' => 'nullable|numeric|min:0',
            'total_expenses' => 'nullable|array',
            'total_expenses.*' => 'nullable|numeric|min:0',
            'balance_amount' => 'nullable|array',
            'balance_amount.*' => 'nullable|numeric',
            'account_detail_id' => 'nullable|array',
            'account_detail_id.*' => 'nullable|string',
            'is_budget_row' => 'nullable|array',
            'is_budget_row.*' => 'nullable|boolean',

            // Overview amounts
            'amount_sanctioned_overview' => 'nullable|numeric|min:0',
            'amount_forwarded_overview' => 'nullable|numeric|min:0',
            'amount_in_hand' => 'nullable|numeric|min:0',
            'total_balance_forwarded' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

        $messages = [
            'project_id.required' => 'Project ID is required.',
            'report_month.between' => 'Reporting month must be between 1 and 12.',
            'report_year.min' => 'Reporting year must be 2020 or later.',
            'report_year.max' => 'Reporting year cannot be more than one year in the future.',
            'photos.*.*.max' => 'Each photo must be less than 5 MB.',
            'photos.*.*.mimes' => 'Photos must be in jpeg, png, jpg, or gif format.',
            'attachment_files.*.max' => 'Each attachment must be less than 2 MB.',
            'attachment_files.*.mimes' => 'Attachments must be in pdf, doc, docx, xls, or xlsx format.',
            'account_period_end.after_or_equal' => 'Account period end date must be after or equal to start date.',
        ];

        // Only add required messages if not draft
        if (!$isDraft) {
            $messages['report_month.required'] = 'Reporting month is required.';
            $messages['report_year.required'] = 'Reporting year is required.';
            $messages['particulars.required'] = 'At least one particular is required in Statements of Account.';
            $messages['particulars.*.required'] = 'Particular description is required.';
        }

        return $messages;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Skip date validation for draft saves
            $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
            if ($isDraft) {
                return;
            }

            // Validate that report month/year is not in the future (beyond current month)
            $reportMonth = $this->input('report_month');
            $reportYear = $this->input('report_year');

            if ($reportMonth && $reportYear) {
                try {
                    $reportDate = Carbon::create($reportYear, $reportMonth, 1)->startOfMonth();
                    $nextMonth = Carbon::now()->addMonth()->startOfMonth();

                    // Report cannot be for a month more than 1 month in the future
                    if ($reportDate->isAfter($nextMonth)) {
                        Log::warning('Monthly report update validation failed: report month too far in future', [
                            'report_id' => $this->route('report_id'),
                            'report_month' => $reportMonth,
                            'report_year' => $reportYear,
                        ]);
                        $validator->errors()->add(
                            'report_month',
                            'Reporting month cannot be more than one month in the future.'
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Monthly report update validation failed: invalid report period', [
                        'report_id' => $this->route('report_id'),
                        'report_month' => $reportMonth,
                        'report_year' => $reportYear,
                        'error' => $e->getMessage(),
                    ]);
                    $validator->errors()->add(
                        'report_month',
                        'Invalid reporting month and year combination.'
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert save_as_draft to boolean if present
        if ($this->has('save_as_draft')) {
            $this->merge([
                'save_as_draft' => filter_var($this->save_as_draft, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
