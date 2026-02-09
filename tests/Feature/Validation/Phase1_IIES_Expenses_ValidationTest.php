<?php

namespace Tests\Feature\Validation;

use App\Http\Requests\Projects\IIES\StoreIIESExpensesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1_IIES_Expenses_ValidationTest extends TestCase
{
    public function test_normalized_empty_string_stored_as_zero(): void
    {
        $data = [
            'iies_total_expenses' => '',
            'iies_expected_scholarship_govt' => '',
            'iies_support_other_sources' => '',
            'iies_beneficiary_contribution' => '',
            'iies_balance_requested' => '0',
            'iies_particulars' => [],
            'iies_amounts' => [],
        ];
        $request = Request::create('/dummy', 'POST', $data);
        $formRequest = StoreIIESExpensesRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        $this->assertSame(0.0, (float) $validated['iies_total_expenses']);
        $this->assertSame(0.0, (float) $validated['iies_expected_scholarship_govt']);
    }

    public function test_normalized_placeholder_stored_as_zero(): void
    {
        $data = [
            'iies_total_expenses' => '-',
            'iies_expected_scholarship_govt' => 'N/A',
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
        $validated = $validator->validated();

        $this->assertSame(0, $validated['iies_total_expenses']);
        $this->assertSame(0, $validated['iies_expected_scholarship_govt']);
    }

    public function test_value_above_max_returns_validation_error(): void
    {
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

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('iies_total_expenses', $validator->errors()->toArray());
    }

    public function test_validation_above_max_throws_validation_exception(): void
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
