<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\QRDLAnnexure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\LogHelper;

class PartialDevelopmentLivelihoodController extends Controller
{
    public function storeAnnexure(Request $request, $report_id)
    {
        Log::info('Store Annexure method called');
        LogHelper::logSafeRequest('Request data', $request, LogHelper::getReportAllowedFields());

        // Validate the incoming request data
        $validatedData = $request->validate([
            'beneficiary_name' => 'nullable|array',
            'beneficiary_name.*' => 'nullable|string|max:255',
            'support_date' => 'nullable|array',
            'support_date.*' => 'nullable|date',
            'self_employment' => 'nullable|array',
            'self_employment.*' => 'nullable|string|max:255',
            'amount_sanctioned' => 'nullable|array',
            'amount_sanctioned.*' => 'nullable|numeric',
            'monthly_profit' => 'nullable|array',
            'monthly_profit.*' => 'nullable|numeric',
            'annual_profit' => 'nullable|array',
            'annual_profit.*' => 'nullable|numeric',
            'impact' => 'nullable|array',
            'impact.*' => 'nullable|string',
            'challenges' => 'nullable|array',
            'challenges.*' => 'nullable|string',
        ]);

        Log::info('Validated Data: ', $validatedData);

        // Save annexure data
        foreach ($validatedData['beneficiary_name'] as $index => $beneficiaryName) {
            $annexureData = [
                'report_id' => $report_id,
                'beneficiary_name' => $beneficiaryName,
                'support_date' => $validatedData['support_date'][$index] ?? null,
                'self_employment' => $validatedData['self_employment'][$index] ?? null,
                'amount_sanctioned' => $validatedData['amount_sanctioned'][$index] ?? null,
                'monthly_profit' => $validatedData['monthly_profit'][$index] ?? null,
                'annual_profit' => $validatedData['annual_profit'][$index] ?? null,
                'impact' => $validatedData['impact'][$index] ?? null,
                'challenges' => $validatedData['challenges'][$index] ?? null,
            ];

            Log::info('Annexure Data:', $annexureData);

            $annexure = QRDLAnnexure::create($annexureData);
            Log::info('Annexure Created: ', $annexure->toArray());
        }

        return redirect()->route('monthly.report.index')->with('success', 'Annexure data submitted successfully.');
    }
}
