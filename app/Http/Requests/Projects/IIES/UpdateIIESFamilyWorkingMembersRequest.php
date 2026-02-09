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

class UpdateIIESFamilyWorkingMembersRequest extends FormRequest
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
        if (isset($input['iies_monthly_income']) && is_array($input['iies_monthly_income'])) {
            foreach ($input['iies_monthly_income'] as $i => $val) {
                $before = $input['iies_monthly_income'][$i];
                $input['iies_monthly_income'][$i] = PlaceholderNormalizer::normalizeToZero($val);
                if ($before !== $input['iies_monthly_income'][$i]) {
                    Log::debug('IIES Family Working Members normalized', ['field' => "iies_monthly_income.{$i}", 'before' => $before, 'after' => $input['iies_monthly_income'][$i]]);
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        return [
            'iies_member_name' => 'array',
            'iies_member_name.*' => 'nullable|string|max:255',
            'iies_work_nature' => 'array',
            'iies_work_nature.*' => 'nullable|string|max:255',
            'iies_monthly_income' => 'array',
            'iies_monthly_income.*' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
        ];
    }
}
