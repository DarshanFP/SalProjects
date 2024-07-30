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

            // Render Blade template to HTML
            $html = view('reports.monthly.doc', compact('report'))->render();

            // Load HTML into PHPWord
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);

            // Save as Word document
            $filePath = storage_path("app/public/Report_{$report->report_id}.docx");
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);

            Log::info('ExportReportController@downloadDoc - DOC generated', ['report_id' => $report_id]);

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('ExportReportController@downloadDoc - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }



}
