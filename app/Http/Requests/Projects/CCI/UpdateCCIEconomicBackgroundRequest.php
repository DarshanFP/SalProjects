<?php

namespace App\Http\Requests\Projects\CCI;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\OptionalIntegerRule;
use App\Support\Normalization\PlaceholderNormalizer;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\Project;

class UpdateCCIEconomicBackgroundRequest extends FormRequest
{
    use NormalizesInput;

    private const INTEGER_KEYS = [
        'agricultural_labour_number',
        'marginal_farmers_number',
        'self_employed_parents_number',
        'informal_sector_parents_number',
        'any_other_number',
    ];

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
        foreach (self::INTEGER_KEYS as $key) {
            if (array_key_exists($key, $input)) {
                $before = $input[$key];
                $input[$key] = PlaceholderNormalizer::normalizeToNull($input[$key]);
                if ($before !== $input[$key]) {
                    Log::debug('CCI Economic Background normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        $rules = [
            'general_remarks' => 'nullable|string',
        ];
        foreach (self::INTEGER_KEYS as $key) {
            $rules[$key] = ['nullable', new OptionalIntegerRule];
        }
        return $rules;
    }
}
