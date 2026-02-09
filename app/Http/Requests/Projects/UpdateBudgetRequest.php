<?php

namespace App\Http\Requests\Projects;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\NumericBoundsRule;
use App\Support\Normalization\PlaceholderNormalizer;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\OldProjects\Project;

class UpdateBudgetRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return ProjectPermissionHelper::canEdit($project, Auth::user());
    }

    protected function normalizeInput(array $input): array
    {
        if (empty($input['phases']) || ! is_array($input['phases'])) {
            return $input;
        }
        $decimalKeys = ['amount_sanctioned', 'rate_quantity', 'rate_multiplier', 'rate_duration', 'rate_increase', 'this_phase', 'next_phase'];
        foreach ($input['phases'] as $pi => $phase) {
            if (isset($phase['amount_sanctioned'])) {
                $before = $input['phases'][$pi]['amount_sanctioned'];
                $input['phases'][$pi]['amount_sanctioned'] = PlaceholderNormalizer::normalizeToNull($input['phases'][$pi]['amount_sanctioned']);
                if ($before !== $input['phases'][$pi]['amount_sanctioned']) {
                    Log::debug('Budget normalized', ['path' => "phases.{$pi}.amount_sanctioned", 'before' => $before, 'after' => $input['phases'][$pi]['amount_sanctioned']]);
                }
            }
            if (isset($phase['budget']) && is_array($phase['budget'])) {
                foreach ($phase['budget'] as $bi => $row) {
                    foreach ($decimalKeys as $key) {
                        if (array_key_exists($key, $row)) {
                            $before = $input['phases'][$pi]['budget'][$bi][$key] ?? null;
                            $input['phases'][$pi]['budget'][$bi][$key] = PlaceholderNormalizer::normalizeToZero($row[$key]);
                            if ($before !== $input['phases'][$pi]['budget'][$bi][$key]) {
                                Log::debug('Budget normalized', ['path' => "phases.{$pi}.budget.{$bi}.{$key}", 'before' => $before, 'after' => $input['phases'][$pi]['budget'][$bi][$key]]);
                            }
                        }
                    }
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        return [
            'phases' => 'array',
            'phases.*.amount_sanctioned' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget' => 'nullable|array',
            'phases.*.budget.*.particular' => 'nullable|string|max:255',
            'phases.*.budget.*.rate_quantity' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget.*.rate_multiplier' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget.*.rate_duration' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget.*.rate_increase' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget.*.this_phase' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
            'phases.*.budget.*.next_phase' => ['nullable', 'numeric', 'min:0', new NumericBoundsRule],
        ];
    }

    public function messages(): array
    {
        return [
            'phases.array' => 'Phases must be an array.',
            'phases.*.amount_sanctioned.numeric' => 'Amount sanctioned must be a number.',
            'phases.*.amount_sanctioned.min' => 'Amount sanctioned cannot be negative.',
            'phases.*.budget.array' => 'Budget must be an array.',
            'phases.*.budget.*.particular.string' => 'Particular must be a string.',
            'phases.*.budget.*.particular.max' => 'Particular cannot exceed 255 characters.',
            'phases.*.budget.*.rate_quantity.numeric' => 'Rate quantity must be a number.',
            'phases.*.budget.*.rate_multiplier.numeric' => 'Rate multiplier must be a number.',
            'phases.*.budget.*.rate_duration.numeric' => 'Rate duration must be a number.',
            'phases.*.budget.*.rate_increase.numeric' => 'Rate increase must be a number.',
            'phases.*.budget.*.this_phase.numeric' => 'This phase amount must be a number.',
            'phases.*.budget.*.next_phase.numeric' => 'Next phase amount must be a number.',
        ];
    }
}
