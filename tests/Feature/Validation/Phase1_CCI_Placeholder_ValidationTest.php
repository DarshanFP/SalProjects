<?php

namespace Tests\Feature\Validation;

use App\Http\Requests\Projects\CCI\StoreCCIStatisticsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class Phase1_CCI_Placeholder_ValidationTest extends TestCase
{
    public function test_placeholder_normalized_to_null(): void
    {
        $data = [
            'total_children_previous_year' => '-',
            'total_children_current_year' => 'N/A',
            'shifted_children_current_year' => '--',
            'reintegrated_children_previous_year' => null,
            'reintegrated_children_current_year' => null,
            'shifted_children_previous_year' => null,
            'pursuing_higher_studies_previous_year' => null,
            'pursuing_higher_studies_current_year' => null,
            'settled_children_previous_year' => null,
            'settled_children_current_year' => null,
            'working_children_previous_year' => null,
            'working_children_current_year' => null,
            'other_category_previous_year' => null,
            'other_category_current_year' => null,
        ];
        $request = Request::create('/dummy', 'POST', $data);
        $formRequest = StoreCCIStatisticsRequest::createFrom($request);
        $normalized = $formRequest->getNormalizedInput();
        $validator = Validator::make($normalized, $formRequest->rules());
        $validator->validate();
        $validated = $validator->validated();

        $this->assertNull($validated['total_children_previous_year']);
        $this->assertNull($validated['total_children_current_year']);
        $this->assertNull($validated['shifted_children_current_year']);
    }
}
