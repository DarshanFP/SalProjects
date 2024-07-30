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
            $report = DPReport::where('report_id', $report_id)->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->firstOrFail();

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
            $report = DPReport::where('report_id', $report_id)->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks'])->firstOrFail();

            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // General information
            $section->addText("Report Details", ['bold' => true, 'size' => 16]);
            $section->addText("Report ID: {$report->report_id}");
            $section->addText("Project ID: {$report->project_id}");
            $section->addText("Project Title: {$report->project_title}");
            $section->addText("Project Type: {$report->project_type}");
            $section->addText("Place: {$report->place}");
            $section->addText("Society Name: {$report->society_name}");
            $section->addText("In Charge: {$report->in_charge}");
            $section->addText("Total Beneficiaries: {$report->total_beneficiaries}");
            $section->addText("Report Month Year: {$report->report_month_year}");
            $section->addText("Goal: {$report->goal}");
            $section->addText("Account Period Start: {$report->account_period_start}");
            $section->addText("Account Period End: {$report->account_period_end}");
            $section->addText("Amount Sanctioned Overview: Rs. " . number_format($report->amount_sanctioned_overview, 2));
            $section->addText("Amount Forwarded Overview: Rs. " . number_format($report->amount_forwarded_overview, 2));
            $section->addText("Amount In Hand: Rs. " . number_format($report->amount_in_hand, 2));
            $section->addText("Total Balance Forwarded: Rs. " . number_format($report->total_balance_forwarded, 2));

            // Objectives and Activities
            foreach ($report->objectives as $objective) {
                $section->addTextBreak(1);
                $section->addText("Objective: {$objective->objective}", ['bold' => true]);
                $section->addText("Expected Outcome: {$objective->expected_outcome}");
                $section->addText("Not Happened: {$objective->not_happened}");
                $section->addText("Why Not Happened: {$objective->why_not_happened}");
                $section->addText("Changes: " . ($objective->changes ? 'Yes' : 'No'));
                $section->addText("Why Changes: {$objective->why_changes}");
                $section->addText("Lessons Learnt: {$objective->lessons_learnt}");
                $section->addText("ToDo Lessons Learnt: {$objective->todo_lessons_learnt}");

                foreach ($objective->activities as $activity) {
                    $section->addText("Activity Month: {$activity->month}");
                    $section->addText("Summary Activities: {$activity->summary_activities}");
                    $section->addText("Qualitative Quantitative Data: {$activity->qualitative_quantitative_data}");
                    $section->addText("Intermediate Outcomes: {$activity->intermediate_outcomes}");
                }
            }

            // Account Details
            $section->addTextBreak(1);
            $section->addText("Account Details", ['bold' => true]);
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

            // Outlooks
            $section->addTextBreak(1);
            $section->addText("Outlooks", ['bold' => true]);
            foreach ($report->outlooks as $outlook) {
                $section->addText("Date: {$outlook->date}");
                $section->addText("Plan Next Month: {$outlook->plan_next_month}");
            }

            // Photos
            $section->addTextBreak(1);
            $section->addText("Photos", ['bold' => true]);
            foreach ($report->photos as $photo) {
                $section->addText("Photo Description: {$photo->description}");
                $section->addImage(asset('storage/' . $photo->photo_path), [
                    'width' => 600,
                    'height' => 400,
                ]);
            }

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
