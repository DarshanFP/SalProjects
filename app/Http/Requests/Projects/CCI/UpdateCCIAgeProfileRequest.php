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

class UpdateCCIAgeProfileRequest extends FormRequest
{
    use NormalizesInput;

    private const INTEGER_KEYS = [
        'education_below_5_bridge_course_prev_year',
        'education_below_5_bridge_course_current_year',
        'education_below_5_kindergarten_prev_year',
        'education_below_5_kindergarten_current_year',
        'education_below_5_other_prev_year',
        'education_below_5_other_current_year',
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
                    Log::debug('CCI Age Profile normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        $rules = [
            'education_below_5_other_specify' => 'nullable|string|max:255',
        ];
        foreach (self::INTEGER_KEYS as $key) {
            $rules[$key] = ['nullable', new OptionalIntegerRule];
        }
        return $rules;
    }
}
