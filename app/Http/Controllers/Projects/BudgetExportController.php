<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Services\BudgetValidationService;
use App\Helpers\ProjectPermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use App\Exports\BudgetExport;
use App\Exports\BudgetReportExport;

class BudgetExportController extends Controller
{
    /**
     * Export project budget to Excel
     *
     * @param string $project_id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with(['budgets', 'reports.accountDetails', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Check permissions
            if (!ProjectPermissionHelper::canView($project, $user)) {
                abort(403, 'You do not have permission to export this budget.');
            }

            $filename = 'budget_' . $project->project_id . '_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new BudgetExport($project), $filename);
        } catch (\Exception $e) {
            Log::error('BudgetExportController@exportExcel - Error', [
                'project_id' => $project_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to export budget to Excel.']);
        }
    }

    /**
     * Export project budget to PDF
     *
     * @param string $project_id
     * @return \Illuminate\Http\Response
     */
    public function exportPdf($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with(['budgets', 'reports.accountDetails', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Check permissions
            if (!ProjectPermissionHelper::canView($project, $user)) {
                abort(403, 'You do not have permission to export this budget.');
            }

            // Get budget summary with validation
            $budgetSummary = BudgetValidationService::getBudgetSummary($project);
            $budgetData = $budgetSummary['budget_data'];
            $validation = $budgetSummary['validation'];

            // Generate HTML for PDF
            $html = view('projects.exports.budget-pdf', [
                'project' => $project,
                'budgetData' => $budgetData,
                'validation' => $validation,
            ])->render();

            // Initialize mPDF
            $mpdf = new Mpdf([
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

            $mpdf->WriteHTML($html);

            $filename = 'budget_' . $project->project_id . '_' . date('Y-m-d') . '.pdf';

            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            Log::error('BudgetExportController@exportPdf - Error', [
                'project_id' => $project_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to export budget to PDF.']);
        }
    }

    /**
     * Generate budget report (Budget vs Actual, Expense breakdown, Trend analysis)
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\View\View
     */
    public function generateReport(Request $request)
    {
        try {
            $filters = [
                'project_type' => $request->input('project_type'),
                'status' => $request->input('status'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'format' => $request->input('format', 'view'), // view, excel, pdf
            ];

            // Get projects based on filters
            $query = Project::with(['budgets', 'reports.accountDetails', 'user']);

            if ($filters['project_type']) {
                $query->where('project_type', $filters['project_type']);
            }

            if ($filters['status']) {
                $query->where('status', $filters['status']);
            }

            if ($filters['start_date']) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if ($filters['end_date']) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            $projects = $query->get();

            // Prepare report data
            $reportData = $this->prepareReportData($projects, $filters);

            // Return based on format
            switch ($filters['format']) {
                case 'excel':
                    $filename = 'budget_report_' . date('Y-m-d') . '.xlsx';
                    return Excel::download(new BudgetReportExport($reportData), $filename);

                case 'pdf':
                    return $this->generatePdfReport($reportData);

                default:
                    return view('projects.exports.budget-report', [
                        'reportData' => $reportData,
                        'filters' => $filters,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('BudgetExportController@generateReport - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to generate budget report.']);
        }
    }

    /**
     * Prepare report data for display/export
     *
     * @param \Illuminate\Database\Eloquent\Collection $projects
     * @param array $filters
     * @return array
     */
    private function prepareReportData($projects, $filters)
    {
        $reportData = [
            'budget_vs_actual' => [],
            'expense_breakdown' => [],
            'trend_analysis' => [],
            'summary' => [
                'total_projects' => $projects->count(),
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_remaining' => 0,
            ],
        ];

        foreach ($projects as $project) {
            $budgetSummary = BudgetValidationService::getBudgetSummary($project);
            $budgetData = $budgetSummary['budget_data'];

            // Budget vs Actual
            $reportData['budget_vs_actual'][] = [
                'project_id' => $project->project_id,
                'project_title' => $project->project_title,
                'project_type' => $project->project_type,
                'budget' => $budgetData['opening_balance'],
                'actual' => $budgetData['total_expenses'],
                'variance' => $budgetData['remaining_balance'],
                'variance_percentage' => $budgetData['opening_balance'] > 0
                    ? ($budgetData['remaining_balance'] / $budgetData['opening_balance']) * 100
                    : 0,
            ];

            // Expense breakdown by project
            $reportData['expense_breakdown'][] = [
                'project_id' => $project->project_id,
                'project_title' => $project->project_title,
                'project_type' => $project->project_type,
                'total_expenses' => $budgetData['total_expenses'],
                'percentage_of_budget' => $budgetData['opening_balance'] > 0
                    ? ($budgetData['total_expenses'] / $budgetData['opening_balance']) * 100
                    : 0,
            ];

            // Trend analysis (by month if reports exist)
            if ($project->relationLoaded('reports') && $project->reports->isNotEmpty()) {
                foreach ($project->reports as $report) {
                    $monthYear = $report->report_month_year ?? $report->created_at->format('Y-m');
                    if (!isset($reportData['trend_analysis'][$monthYear])) {
                        $reportData['trend_analysis'][$monthYear] = [
                            'month' => $monthYear,
                            'total_expenses' => 0,
                            'project_count' => 0,
                        ];
                    }
                    $reportData['trend_analysis'][$monthYear]['total_expenses'] +=
                        $report->accountDetails->sum('total_expenses') ?? 0;
                    $reportData['trend_analysis'][$monthYear]['project_count']++;
                }
            }

            // Update summary
            $reportData['summary']['total_budget'] += $budgetData['opening_balance'];
            $reportData['summary']['total_expenses'] += $budgetData['total_expenses'];
            $reportData['summary']['total_remaining'] += $budgetData['remaining_balance'];
        }

        // Sort trend analysis by month
        ksort($reportData['trend_analysis']);
        $reportData['trend_analysis'] = array_values($reportData['trend_analysis']);

        return $reportData;
    }

    /**
     * Generate PDF report
     *
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    private function generatePdfReport($reportData)
    {
        $html = view('projects.exports.budget-report-pdf', [
            'reportData' => $reportData,
        ])->render();

        $mpdf = new Mpdf([
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

        $mpdf->WriteHTML($html);

        $filename = 'budget_report_' . date('Y-m-d') . '.pdf';

        return response($mpdf->Output('', 'S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
