<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\QRDLAnnexure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivelihoodAnnexureController extends Controller
{
    /**
     * Handle storing or updating Livelihood Annexure data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $report_id
     * @return void
     */
    public function handleLivelihoodAnnexure(Request $request, $report_id)
    {
        Log::info('Handling Livelihood Annexure for report_id:', ['report_id' => $report_id]);

        $validatedData = $request->validate([
            'dla_beneficiary_name' => 'nullable|array',
            'dla_beneficiary_name.*' => 'nullable|string|max:255',
            'dla_support_date' => 'nullable|array',
            'dla_support_date.*' => 'nullable|date',
            'dla_self_employment' => 'nullable|array',
            'dla_self_employment.*' => 'nullable|string|max:255',
            'dla_amount_sanctioned' => 'nullable|array',
            'dla_amount_sanctioned.*' => 'nullable|numeric',
            'dla_monthly_profit' => 'nullable|array',
            'dla_monthly_profit.*' => 'nullable|numeric',
            'dla_annual_profit' => 'nullable|array',
            'dla_annual_profit.*' => 'nullable|numeric',
            'dla_impact' => 'nullable|array',
            'dla_impact.*' => 'nullable|string',
            'dla_challenges' => 'nullable|array',
            'dla_challenges.*' => 'nullable|string',
        ]);

        // Save or update annexure data
        foreach ($validatedData['dla_beneficiary_name'] ?? [] as $index => $beneficiaryName) {
            QRDLAnnexure::updateOrCreate(
                [
                    'report_id' => $report_id,
                    'dla_beneficiary_name' => $beneficiaryName,
                ],
                [
                    'dla_support_date' => $validatedData['dla_support_date'][$index] ?? null,
                    'dla_self_employment' => $validatedData['dla_self_employment'][$index] ?? null,
                    'dla_amount_sanctioned' => $validatedData['dla_amount_sanctioned'][$index] ?? null,
                    'dla_monthly_profit' => $validatedData['dla_monthly_profit'][$index] ?? null,
                    'dla_annual_profit' => $validatedData['dla_annual_profit'][$index] ?? null,
                    'dla_impact' => $validatedData['dla_impact'][$index] ?? null,
                    'dla_challenges' => $validatedData['dla_challenges'][$index] ?? null,
                ]
            );
            Log::info('Annexure Data Updated:', ['beneficiary_name' => $beneficiaryName]);
        }
    }

    /**
     * Retrieve annexure data as a collection.
     *
     * @param  int  $report_id
     * @return \Illuminate\Support\Collection
     */
    public function getAnnexures($report_id)
    {
        return QRDLAnnexure::where('report_id', $report_id)->get();
    }
}
