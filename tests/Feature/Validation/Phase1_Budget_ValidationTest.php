<?php

namespace Tests\Feature\Validation;

use App\Http\Requests\Projects\StoreBudgetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class Phase1_Budget_ValidationTest extends TestCase
{
    public function test_value_above_max_returns_validation_error(): void
    {
        $data = [
            'phases' => [
                [
                    'budget' => [
                        [
                            'particular' => 'Test',
                            'rate_quantity' => '1',
                            'rate_multiplier' => '1',
                            'rate_duration' => '100000000',
                            'rate_increase' => '0',
                            'this_phase' => '0',
                            'next_phase' => '0',
                        ],
                    ],
                ],
            ],
        ];
        $request = Request::create('/dummy', 'POST', $data);
        $formRequest = StoreBudgetRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue(
            $validator->errors()->has('phases.0.budget.0.rate_duration'),
            'Validation should fail for rate_duration above max'
        );
    }
}
