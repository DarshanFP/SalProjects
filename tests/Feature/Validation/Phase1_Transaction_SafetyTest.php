<?php

namespace Tests\Feature\Validation;

use App\Http\Requests\Projects\IIES\StoreIIESExpensesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * When validation fails (e.g. value above max), ValidationException is thrown.
 * The parent controller catches it, rolls back, and rethrows so the user sees 422.
 * This test verifies that Strategy B validation throws ValidationException on invalid data.
 */
class Phase1_Transaction_SafetyTest extends TestCase
{
    public function test_validation_failure_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'iies_total_expenses' => '100000000',
            'iies_expected_scholarship_govt' => '0',
            'iies_support_other_sources' => '0',
            'iies_beneficiary_contribution' => '0',
            'iies_balance_requested' => '0',
            'iies_particulars' => [],
            'iies_amounts' => [],
        ];
        $request = Request::create('/dummy', 'POST', $data);
        $formRequest = StoreIIESExpensesRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
    }
}
