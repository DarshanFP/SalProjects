<?php

namespace App\Http\Requests\Projects\IIES;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\NumericBoundsRule;
use App\Support\Normalization\BooleanNormalizer;
use App\Support\Normalization\PlaceholderNormalizer;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\Project;

class UpdateIIESFinancialSupportRequest extends FormRequest
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
        foreach (['govt_eligible_scholarship', 'other_eligible_scholarship'] as $key) {
            if (array_key_exists($key, $input)) {
                $before = $input[$key];
                $input[$key] = BooleanNormalizer::toInt($input[$key]);
                if ($before !== $input[$key]) {
                    Log::debug('IIES Financial Support boolean normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
                }
            }
        }
        foreach (['scholarship_amt', 'other_scholarship_amt', 'family_contrib'] as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = PlaceholderNormalizer::normalizeToNull($input[$key]);
                if ($input[$key] !== null && is_numeric($input[$key])) {
                    $input[$key] = (float) $input[$key];
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        return [
            'govt_eligible_scholarship' => 'required|boolean',
            'scholarship_amt' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'other_eligible_scholarship' => 'required|boolean',
            'other_scholarship_amt' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'family_contrib' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'no_contrib_reason' => 'nullable|string',
        ];
    }
}
