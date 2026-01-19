<?php

namespace App\Http\Controllers\Reports\Aggregated;

use App\Http\Controllers\Controller;
use App\Models\Reports\Quarterly\QuarterlyReport;
use App\Models\Reports\HalfYearly\HalfYearlyReport;
use App\Models\Reports\Annual\AnnualReport;
use App\Helpers\NumberFormatHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class AggregatedReportExportController extends Controller
{
    /**
     * Export quarterly report as PDF
     */
    public function exportQuarterlyPdf($report_id)
    {
        ini_set('memory_limit', '512M');

        try {
            $report = QuarterlyReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            // Check permissions
            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant'])) {
                // Check if user owns or is in-charge of the project
                $project = $report->project;
                if (!$project || ($project->user_id !== $user->id && $project->in_charge !== $user->id)) {
                    abort(403, 'Unauthorized');
                }
            }

            // Prepare data
            $data = [
                'report' => $report,
                'report_type' => 'quarterly',
                'user' => $user,
            ];

            // Generate PDF
            $html = view('reports.aggregated.pdf.quarterly', $data)->render();
            $mpdf = $this->initializeMpdf();
            $mpdf->WriteHTML($html);

            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="quarterly_report_' . $report->report_id . '.pdf"');

        } catch (\Exception $e) {
            Log::error('Failed to export quarterly PDF', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export half-yearly report as PDF
     */
    public function exportHalfYearlyPdf($report_id)
    {
        ini_set('memory_limit', '512M');

        try {
            $report = HalfYearlyReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant']) && $report->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }

            $data = [
                'report' => $report,
                'report_type' => 'half_yearly',
                'user' => $user,
            ];

            $html = view('reports.aggregated.pdf.half-yearly', $data)->render();
            $mpdf = $this->initializeMpdf();
            $mpdf->WriteHTML($html);

            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="half_yearly_report_' . $report->report_id . '.pdf"');

        } catch (\Exception $e) {
            Log::error('Failed to export half-yearly PDF', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export annual report as PDF
     */
    public function exportAnnualPdf($report_id)
    {
        ini_set('memory_limit', '512M');

        try {
            $report = AnnualReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant']) && $report->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }

            $data = [
                'report' => $report,
                'report_type' => 'annual',
                'user' => $user,
            ];

            $html = view('reports.aggregated.pdf.annual', $data)->render();
            $mpdf = $this->initializeMpdf();
            $mpdf->WriteHTML($html);

            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="annual_report_' . $report->report_id . '.pdf"');

        } catch (\Exception $e) {
            Log::error('Failed to export annual PDF', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export quarterly report as Word
     */
    public function exportQuarterlyWord($report_id)
    {
        try {
            $report = QuarterlyReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant']) && $report->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }

            $phpWord = new PhpWord();
            $this->addQuarterlyReportSections($phpWord, $report);

            $filePath = storage_path("app/public/Quarterly_Report_{$report->report_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to export quarterly Word', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate Word document: ' . $e->getMessage());
        }
    }

    /**
     * Export half-yearly report as Word
     */
    public function exportHalfYearlyWord($report_id)
    {
        try {
            $report = HalfYearlyReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant']) && $report->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }

            $phpWord = new PhpWord();
            $this->addHalfYearlyReportSections($phpWord, $report);

            $filePath = storage_path("app/public/Half_Yearly_Report_{$report->report_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to export half-yearly Word', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate Word document: ' . $e->getMessage());
        }
    }

    /**
     * Export annual report as Word
     */
    public function exportAnnualWord($report_id)
    {
        try {
            $report = AnnualReport::with([
                'project',
                'generatedBy',
                'details',
                'objectives',
                'photos',
                'aiInsights',
                'aiTitle'
            ])->findOrFail($report_id);

            $user = Auth::user();
            if (in_array($user->role, ['executor', 'applicant']) && $report->generated_by_user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }

            $phpWord = new PhpWord();
            $this->addAnnualReportSections($phpWord, $report);

            $filePath = storage_path("app/public/Annual_Report_{$report->report_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to export annual Word', [
                'report_id' => $report_id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Failed to generate Word document: ' . $e->getMessage());
        }
    }

    /**
     * Initialize mPDF
     */
    private function initializeMpdf()
    {
        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'tempDir' => storage_path('app/tmp'),
            'default_font_size' => 10,
            'default_font' => 'Arial',
        ]);
    }

    /**
     * Add quarterly report sections to PhpWord document
     */
    private function addQuarterlyReportSections(PhpWord $phpWord, QuarterlyReport $report)
    {
        $section = $phpWord->addSection();

        // Title
        $section->addText($report->aiTitle->report_title ?? 'Quarterly Progress Report', [
            'bold' => true,
            'size' => 16
        ], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Basic Information
        $section->addText('Basic Information', ['bold' => true, 'size' => 14]);
        $section->addText("Report ID: {$report->report_id}");
        $section->addText("Project: {$report->project_title}");
        $section->addText("Period: {$report->getPeriodLabel()}");
        $section->addTextBreak(1);

        // AI Insights
        if ($report->aiInsights) {
            if ($report->aiInsights->executive_summary) {
                $section->addText('Executive Summary', ['bold' => true, 'size' => 12]);
                $section->addText($report->aiInsights->executive_summary);
                $section->addTextBreak(1);
            }

            if ($report->aiInsights->key_achievements) {
                $section->addText('Key Achievements', ['bold' => true, 'size' => 12]);
                foreach ($report->aiInsights->key_achievements as $achievement) {
                    $text = is_array($achievement) ? ($achievement['title'] ?? $achievement['description'] ?? '') : $achievement;
                    $section->addText("• {$text}");
                }
                $section->addTextBreak(1);
            }
        }

        // Budget Details
        if ($report->details->isNotEmpty()) {
            $section->addText('Budget Details', ['bold' => true, 'size' => 12]);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            $table->addRow();
            $table->addCell()->addText('Particulars', ['bold' => true]);
            $table->addCell()->addText('Total Expenses', ['bold' => true]);
            $table->addCell()->addText('Closing Balance', ['bold' => true]);

            foreach ($report->details as $detail) {
                $table->addRow();
                $table->addCell()->addText($detail->particulars ?? '');
                $table->addCell()->addText(NumberFormatHelper::formatIndian($detail->total_expenses, 2));
                $table->addCell()->addText(NumberFormatHelper::formatIndian($detail->closing_balance, 2));
            }
            $section->addTextBreak(1);
        }
    }

    /**
     * Add half-yearly report sections to PhpWord document
     */
    private function addHalfYearlyReportSections(PhpWord $phpWord, HalfYearlyReport $report)
    {
        $section = $phpWord->addSection();

        $section->addText($report->aiTitle->report_title ?? 'Half-Yearly Progress Report', [
            'bold' => true,
            'size' => 16
        ], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $section->addText('Basic Information', ['bold' => true, 'size' => 14]);
        $section->addText("Report ID: {$report->report_id}");
        $section->addText("Project: {$report->project_title}");
        $section->addText("Period: {$report->getPeriodLabel()}");
        $section->addTextBreak(1);

        if ($report->aiInsights) {
            if ($report->aiInsights->executive_summary) {
                $section->addText('Executive Summary', ['bold' => true, 'size' => 12]);
                $section->addText($report->aiInsights->executive_summary);
                $section->addTextBreak(1);
            }

            if ($report->aiInsights->strategic_insights) {
                $section->addText('Strategic Insights', ['bold' => true, 'size' => 12]);
                foreach ($report->aiInsights->strategic_insights as $insight) {
                    $text = is_array($insight) ? ($insight['insight'] ?? '') : $insight;
                    $section->addText("• {$text}");
                }
                $section->addTextBreak(1);
            }
        }
    }

    /**
     * Add annual report sections to PhpWord document
     */
    private function addAnnualReportSections(PhpWord $phpWord, AnnualReport $report)
    {
        $section = $phpWord->addSection();

        $section->addText($report->aiTitle->report_title ?? 'Annual Report', [
            'bold' => true,
            'size' => 16
        ], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $section->addText('Basic Information', ['bold' => true, 'size' => 14]);
        $section->addText("Report ID: {$report->report_id}");
        $section->addText("Project: {$report->project_title}");
        $section->addText("Year: {$report->year}");
        $section->addTextBreak(1);

        if ($report->aiInsights) {
            if ($report->aiInsights->executive_summary) {
                $section->addText('Executive Summary', ['bold' => true, 'size' => 12]);
                $section->addText($report->aiInsights->executive_summary);
                $section->addTextBreak(1);
            }

            if ($report->aiInsights->impact_assessment) {
                $section->addText('Impact Assessment', ['bold' => true, 'size' => 12]);
                $assessment = is_array($report->aiInsights->impact_assessment)
                    ? json_encode($report->aiInsights->impact_assessment, JSON_PRETTY_PRINT)
                    : $report->aiInsights->impact_assessment;
                $section->addText($assessment);
                $section->addTextBreak(1);
            }
        }
    }
}
