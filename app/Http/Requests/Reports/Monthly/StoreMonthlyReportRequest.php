<?php

namespace App\Http\Requests\Reports\Monthly;

use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Services\Reports\MonthlyReportCreateAuthorization;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreMonthlyReportRequest extends FormRequest
{
    private ?string $authFailureReason = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $context = [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role,
            'project_id' => $this->input('project_id'),
        ];

        if (!auth()->check()) {
            $this->authFailureReason = 'unauthenticated';
            Log::warning('Monthly report store denied: user not authenticated', $context);

            return false;
        }

        $user = auth()->user();
        if (!in_array($user->role, ['executor', 'applicant'], true)) {
            $this->authFailureReason = 'invalid_role';
            Log::warning('Monthly report store denied: invalid role', $context);

            return false;
        }

        $projectId = $this->input('project_id');
        if (!$projectId) {
            $this->authFailureReason = 'missing_project_id';
            Log::warning('Monthly report store denied: missing project_id', $context);

            return false;
        }

        $project = Project::where('project_id', $projectId)->first();
        if (!$project) {
            $this->authFailureReason = 'project_not_found';
            Log::warning('Monthly report store denied: project not found', $context);

            return false;
        }

        $result = MonthlyReportCreateAuthorization::check($user, $project);
        if (!$result['allowed']) {
            $this->authFailureReason = $result['reason'];
            return false;
        }

        return true;
    }

    protected function failedAuthorization(): void
    {
        Log::warning('Monthly report store authorization failed', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role,
            'project_id' => $this->input('project_id'),
            'auth_failure_reason' => $this->authFailureReason,
        ]);

        parent::failedAuthorization();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

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
            'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5 MB
            'photo_descriptions' => 'nullable|array',
            'photo_descriptions.*' => 'nullable|string|max:1000',
            'photo_activity_id' => 'nullable|array',
            'photo_activity_id.*' => 'nullable|string|max:255',

            // Multiple Attachments
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:2048', // 2 MB
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

            // Legacy single attachment (for backward compatibility)
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

            // Activities under objectives
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
            $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
            $reportMonth = $this->input('report_month');
            $reportYear = $this->input('report_year');
            $projectId = $this->input('project_id');

            if (!$isDraft && $reportMonth && $reportYear) {
                try {
                    $reportDate = Carbon::create($reportYear, $reportMonth, 1)->startOfMonth();
                    $nextMonth = Carbon::now()->addMonth()->startOfMonth();

                    if ($reportDate->isAfter($nextMonth)) {
                        $validator->errors()->add(
                            'report_month',
                            'Reporting month cannot be more than one month in the future.'
                        );
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add(
                        'report_month',
                        'Invalid reporting month and year combination.'
                    );
                }
            }

            if ($projectId && $reportMonth && $reportYear) {
                try {
                    if (MonthlyReportCreateAuthorization::reportExistsForPeriod(
                        $projectId,
                        (int) $reportMonth,
                        (int) $reportYear
                    )) {
                        $validator->errors()->add(
                            'report_month',
                            'A report already exists for this project and reporting period.'
                        );
                    }
                } catch (\Exception $e) {
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
