<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function downloadPdf($report_id)
    {
        try {
            Log::info('ExportController@downloadPdf - Fetching report', ['report_id' => $report_id]);
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->findOrFail($report_id);

            Log::info('ExportController@downloadPdf - Generating PDF', ['report_id' => $report_id]);
            $pdf = PDF::loadView('reports.monthly.pdf', compact('report'));

            Log::info('ExportController@downloadPdf - PDF generated', ['report_id' => $report_id]);
            return $pdf->download("report_{$report_id}.pdf");
        } catch (\Exception $e) {
            Log::error('ExportController@downloadPdf - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }

    public function downloadDoc($report_id)
    {
        try {
            Log::info('ExportController@downloadDoc - Fetching report', ['report_id' => $report_id]);
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->findOrFail($report_id);

            Log::info('ExportController@downloadDoc - Initializing PHPWord', ['report_id' => $report_id]);
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            Log::info('ExportController@downloadDoc - Adding general information', ['report_id' => $report_id]);
            $section->addText("Monthly Report Details", ['bold' => true, 'size' => 16]);
            $section->addText("Report ID: {$report->report_id}");
            $section->addText("Project ID: {$report->project_id}");
            $section->addText("Project Title: {$report->project_title}");
            $section->addText("Project Type: {$report->project_type}");
            $section->addText("Place: {$report->place}");
            $section->addText("Society Name: {$report->society_name}");
            $section->addText("In Charge: {$report->in_charge}");
            $section->addText("Total Beneficiaries: {$report->total_beneficiaries}");
            $section->addText("Report Month Year: " . \Carbon\Carbon::parse($report->report_month_year)->format('F Y'));
            $section->addText("Goal: {$report->goal}");
            $section->addText("Account Period Start: {$report->account_period_start}");
            $section->addText("Account Period End: {$report->account_period_end}");
            $section->addText("Amount Sanctioned Overview: Rs. " . number_format($report->amount_sanctioned_overview, 2));
            $section->addText("Amount Forwarded Overview: Rs. " . number_format($report->amount_forwarded_overview, 2));
            $section->addText("Amount In Hand: Rs. " . number_format($report->amount_in_hand, 2));
            $section->addText("Total Balance Forwarded: Rs. " . number_format($report->total_balance_forwarded, 2));

            Log::info('ExportController@downloadDoc - Adding objectives', ['report_id' => $report_id]);
            $section->addTextBreak(1);
            $section->addText("Objectives", ['bold' => true, 'size' => 14]);
            foreach ($report->objectives as $objective) {
                $section->addText("Objective: {$objective->objective}");
                $section->addText("Expected Outcome: {$objective->expected_outcome}");
                $section->addText("Not Happened: {$objective->not_happened}");
                $section->addText("Why Not Happened: {$objective->why_not_happened}");
                $section->addText("Changes: " . ($objective->changes ? 'Yes' : 'No'));
                $section->addText("Why Changes: {$objective->why_changes}");
                $section->addText("Lessons Learnt: {$objective->lessons_learnt}");
                $section->addText("ToDo Lessons Learnt: {$objective->todo_lessons_learnt}");

                $section->addTextBreak(1);
                $section->addText("Activities", ['bold' => true, 'size' => 14]);
                foreach ($objective->activities as $activity) {
                    $section->addText("Activity Month: {$activity->month}");
                    $section->addText("Summary Activities: {$activity->summary_activities}");
                    $section->addText("Qualitative Quantitative Data: {$activity->qualitative_quantitative_data}");
                    $section->addText("Intermediate Outcomes: {$activity->intermediate_outcomes}");
                }
            }

            Log::info('ExportController@downloadDoc - Adding account details', ['report_id' => $report_id]);
            $section->addTextBreak(1);
            $section->addText("Account Details", ['bold' => true, 'size' => 14]);
            $section->addText("Account Period: {$report->account_period_start} to {$report->account_period_end}");
            $section->addText("Amount Sanctioned: Rs. " . number_format($report->amount_sanctioned_overview, 2));
            $section->addText("Amount Forwarded: Rs. " . number_format($report->amount_forwarded_overview, 2));
            $section->addText("Total Amount: Rs. " . number_format($report->amount_in_hand, 2));
            $section->addText("Balance Forwarded: Rs. " . number_format($report->total_balance_forwarded, 2));

            $table = $section->addTable();
            $table->addRow();
            $table->addCell(2000)->addText("Particulars");
            $table->addCell(2000)->addText("Amount Forwarded");
            $table->addCell(2000)->addText("Amount Sanctioned");
            $table->addCell(2000)->addText("Total Amount");
            $table->addCell(2000)->addText("Expenses Last Month");
            $table->addCell(2000)->addText("Expenses This Month");
            $table->addCell(2000)->addText("Total Expenses");
            $table->addCell(2000)->addText("Balance Amount");

            foreach ($report->accountDetails as $accountDetail) {
                $table->addRow();
                $table->addCell(2000)->addText($accountDetail->particulars);
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->amount_forwarded, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->amount_sanctioned, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->total_amount, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->expenses_last_month, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->expenses_this_month, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->total_expenses, 2));
                $table->addCell(2000)->addText("Rs. " . number_format($accountDetail->balance_amount, 2));
            }

            Log::info('ExportController@downloadDoc - Adding outlooks', ['report_id' => $report_id]);
            $section->addTextBreak(1);
            $section->addText("Outlooks", ['bold' => true, 'size' => 14]);
            foreach ($report->outlooks as $outlook) {
                $section->addText("Date: {$outlook->date}");
                $section->addText("Plan Next Month: {$outlook->plan_next_month}");
            }

            Log::info('ExportController@downloadDoc - Adding photos', ['report_id' => $report_id]);
            $section->addTextBreak(1);
            $section->addText("Photos", ['bold' => true, 'size' => 14]);
            foreach ($report->photos as $photo) {
                $section->addText("Description: {$photo->description}");
                $section->addImage(asset('storage/' . $photo->photo_path), ['width' => 600, 'height' => 400]);
            }

            Log::info('ExportController@downloadDoc - Saving Word document', ['report_id' => $report_id]);
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $filePath = storage_path("app/public/Report_{$report->report_id}.docx");
            $objWriter->save($filePath);

            Log::info('ExportController@downloadDoc - DOC generated', ['report_id' => $report_id]);
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('ExportController@downloadDoc - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }
}
