<?php

namespace App\Http\Requests\Projects\CCI;

use App\Http\Requests\Concerns\NormalizesInput;
use App\Rules\OptionalIntegerRule;
use App\Support\Normalization\PlaceholderNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreCCIStatisticsRequest extends FormRequest
{
    use NormalizesInput;

    private const INTEGER_KEYS = [
        'total_children_previous_year',
        'total_children_current_year',
        'reintegrated_children_previous_year',
        'reintegrated_children_current_year',
        'shifted_children_previous_year',
        'shifted_children_current_year',
        'pursuing_higher_studies_previous_year',
        'pursuing_higher_studies_current_year',
        'settled_children_previous_year',
        'settled_children_current_year',
        'working_children_previous_year',
        'working_children_current_year',
        'other_category_previous_year',
        'other_category_current_year',
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
                    Log::debug('CCI Statistics normalized', ['field' => $key, 'before' => $before, 'after' => $input[$key]]);
                }
            }
        }
        return $input;
    }

    public function rules(): array
    {
        $rules = [];
        foreach (self::INTEGER_KEYS as $key) {
            $rules[$key] = ['nullable', new OptionalIntegerRule];
        }
        return $rules;
    }
}
