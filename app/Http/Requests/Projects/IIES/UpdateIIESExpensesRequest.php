<?php

namespace App\Http\Requests\Projects\IIES;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\NumericBoundsRule;
use App\Support\Normalization\PlaceholderNormalizer;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\Project;

class UpdateIIESExpensesRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        $projectId = $this->route('projectId') ?? $this->input('project_id');

        if (! $projectId) {
            return false;
        }

        $project = Project::where('project_id', $projectId)->first();

        if (! $project) {
            return false;
        }

        return ProjectPermissionHelper::canEdit($project, Auth::user());
    }

    protected function normalizeInput(array $input): array
    {
        $notNullDecimals = [
            'iies_total_expenses',
            'iies_expected_scholarship_govt',
            'iies_support_other_sources',
            'iies_beneficiary_contribution',
            'iies_balance_requested',
        ];
        foreach ($notNullDecimals as $key) {
            if (array_key_exists($key, $input)) {
                $before = $input[$key];
                $input[$key] = PlaceholderNormalizer::normalizeToZero($input[$key]);
                if ($before !== $input[$key]) {
                    Log::debug('IIES Expenses normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
                }
            }
        }
        if (isset($input['iies_amounts']) && is_array($input['iies_amounts'])) {
            foreach ($input['iies_amounts'] as $i => $val) {
                $input['iies_amounts'][$i] = PlaceholderNormalizer::normalizeToNull($val);
            }
        }
        return $input;
    }

    public function rules(): array
    {
        $required = ! $this->boolean('save_as_draft');
        $decimalRule = ['numeric', 'min:0', new NumericBoundsRule];
        $mainRules = [
            'iies_total_expenses' => $required ? array_merge(['required'], $decimalRule) : array_merge(['nullable'], $decimalRule),
            'iies_expected_scholarship_govt' => $required ? array_merge(['required'], $decimalRule) : array_merge(['nullable'], $decimalRule),
            'iies_support_other_sources' => $required ? array_merge(['required'], $decimalRule) : array_merge(['nullable'], $decimalRule),
            'iies_beneficiary_contribution' => $required ? array_merge(['required'], $decimalRule) : array_merge(['nullable'], $decimalRule),
            'iies_balance_requested' => $required ? array_merge(['required'], $decimalRule) : array_merge(['nullable'], $decimalRule),
            'iies_particulars' => 'array',
            'iies_particulars.*' => 'nullable|string|max:255',
            'iies_amounts' => 'array',
            'iies_amounts.*' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
        ];
        return $mainRules;
    }
}
