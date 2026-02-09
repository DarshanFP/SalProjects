<?php

namespace App\Http\Requests\Projects\CCI;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\OptionalIntegerRule;
use App\Support\Normalization\PlaceholderNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreCCIPersonalSituationRequest extends FormRequest
{
    use NormalizesInput;

    private const INTEGER_KEYS = [
        'children_with_parents_last_year',
        'children_with_parents_current_year',
        'semi_orphans_last_year',
        'semi_orphans_current_year',
        'orphans_last_year',
        'orphans_current_year',
        'hiv_infected_last_year',
        'hiv_infected_current_year',
        'differently_abled_last_year',
        'differently_abled_current_year',
        'parents_in_conflict_last_year',
        'parents_in_conflict_current_year',
        'other_ailments_last_year',
        'other_ailments_current_year',
    ];

    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function normalizeInput(array $input): array
    {
        foreach (self::INTEGER_KEYS as $key) {
            if (array_key_exists($key, $input)) {
                $before = $input[$key];
                $input[$key] = PlaceholderNormalizer::normalizeToNull($input[$key]);
                if ($before !== $input[$key]) {
                    Log::debug('CCI Personal Situation normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
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
