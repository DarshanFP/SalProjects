<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class ExportReportController extends Controller
{
    public function downloadPdf($report_id)
    {
        set_time_limit(300); // Increase execution time

        try {
            // Use eager loading to reduce the number of queries
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                              ->findOrFail($report_id);

            // Log start time
            $startTime = microtime(true);

            // Generate PDF
            $pdf = PDF::loadView('reports.monthly.pdf', compact('report'));

            // Log end time
            $endTime = microtime(true);
            Log::info('Time taken to generate PDF: ' . ($endTime - $startTime) . ' seconds');

            Log::info('ExportReportController@downloadPdf - PDF generated', ['report_id' => $report_id]);

            return $pdf->download("report_{$report_id}.pdf");
        } catch (\Exception $e) {
            Log::error('ExportReportController@downloadPdf - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }

        public function downloadDoc($report_id)
    {
        set_time_limit(300); // Increase execution time

        try {
            // Use eager loading to reduce the number of queries
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])
                            ->findOrFail($report_id);

            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // General Information
            $section->addText("Monthly Report Details", ['bold' => true, 'size' => 16]);
            $section->addText("Project ID: {$report->project_id}");
            $section->addText("Report ID: {$report->report_id}");
            $section->addText("Project Title: {$report->project_title}");
            $section->addText("Project Type: {$report->project_type}");
            $section->addText("Place: {$report->place}");
            $section->addText("Society Name: {$report->society_name}");
            $section->addText("In Charge: {$report->in_charge}");
            $section->addText("Total Beneficiaries: {$report->total_beneficiaries}");
            $section->addText("Report Month & Year: " . \Carbon\Carbon::parse($report->report_month_year)->format('F Y'));
            $section->addText("Goal: {$report->goal}");

            // Objectives and Activities
            $section->addTextBreak(1);
            $section->addText("Objectives and Activities:", ['bold' => true]);

            foreach ($report->objectives as $objective) {
                $section->addText("Objective: {$objective->objective}");
                $section->addText("Expected Outcome: {$objective->expected_outcome}");
                $section->addText("What Did Not Happen: {$objective->not_happened}");
                $section->addText("Why Not Happened: {$objective->why_not_happened}");
                $section->addText("Changes: " . ($objective->changes ? 'Yes' : 'No'));
                if ($objective->changes) {
                    $section->addText("Why Changes: {$objective->why_changes}");
                }
                $section->addText("Lessons Learnt: {$objective->lessons_learnt}");
                $section->addText("What Will Be Done Differently: {$objective->todo_lessons_learnt}");

                foreach ($objective->activities as $activity) {
                    $section->addTextBreak(1);
                    $section->addText("Activity:", ['bold' => true]);
                    $section->addText("Month: " . \Carbon\Carbon::create()->month($activity->month)->format('F'));
                    $section->addText("Summary of Activities: {$activity->summary_activities}");
                    $section->addText("Qualitative & Quantitative Data: {$activity->qualitative_quantitative_data}");
                    $section->addText("Intermediate Outcomes: {$activity->intermediate_outcomes}");
                }
            }

            // Outlooks
            $section->addTextBreak(1);
            $section->addText("Outlooks:", ['bold' => true]);
            foreach ($report->outlooks as $outlook) {
                $section->addText("Date: " . \Carbon\Carbon::parse($outlook->date)->format('d-m-Y'));
                $section->addText("Action Plan for Next Month: {$outlook->plan_next_month}");
            }

            // Account Details
            $section->addTextBreak(1);
            $section->addText("Account Details:", ['bold' => true]);
            $section->addText("Account Period: " . \Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') . " to " . \Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y'));
            $section->addText("Amount Sanctioned: Rs. " . number_format($report->amount_sanctioned_overview, 2));
            $section->addText("Amount Forwarded: Rs. " . number_format($report->amount_forwarded_overview, 2));
            $section->addText("Total Amount: Rs. " . number_format($report->amount_in_hand, 2));
            $section->addText("Balance Forwarded: Rs. " . number_format($report->total_balance_forwarded, 2));

            $table = $section->addTable();
            $table->addRow();
            $table->addCell(2000)->addText("Particulars");
            $table->addCell(1500)->addText("Amount Forwarded");
            $table->addCell(1500)->addText("Amount Sanctioned");
            $table->addCell(1500)->addText("Total Amount");
            $table->addCell(1500)->addText("Expenses Last Month");
            $table->addCell(1500)->addText("Expenses This Month");
            $table->addCell(1500)->addText("Total Expenses");
            $table->addCell(1500)->addText("Balance Amount");

            foreach ($report->accountDetails as $account) {
                $table->addRow();
                $table->addCell(2000)->addText($account->particulars);
                $table->addCell(1500)->addText(number_format($account->amount_forwarded, 2));
                $table->addCell(1500)->addText(number_format($account->amount_sanctioned, 2));
                $table->addCell(1500)->addText(number_format($account->total_amount, 2));
                $table->addCell(1500)->addText(number_format($account->expenses_last_month, 2));
                $table->addCell(1500)->addText(number_format($account->expenses_this_month, 2));
                $table->addCell(1500)->addText(number_format($account->total_expenses, 2));
                $table->addCell(1500)->addText(number_format($account->balance_amount, 2));
            }

            // Photos
            $section->addTextBreak(1);
            $section->addText("Photos:", ['bold' => true]);
            foreach ($report->photos as $photo) {
                $section->addText("Description: {$photo->description}");
                $imagePath = public_path('storage/' . $photo->photo_path);
                if (file_exists($imagePath)) {
                    $section->addImage($imagePath, [
                        'width' => 300,
                        'height' => 200,
                        'align' => 'center'
                    ]);
                }
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $filePath = storage_path("app/public/Report_{$report->report_id}.docx");
            $objWriter->save($filePath);

            Log::info('ExportReportController@downloadDoc - DOC generated', ['report_id' => $report_id]);

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('ExportReportController@downloadDoc - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }

}
