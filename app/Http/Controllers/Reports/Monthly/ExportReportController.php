<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Reports\Monthly\DPObjective;
use App\Models\Reports\Monthly\DPActivity;
use App\Models\Reports\Monthly\DPAccountDetail;
use App\Models\Reports\Monthly\DPPhoto;
use App\Models\Reports\Monthly\DPOutlook;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use App\Models\User;
use App\Helpers\NumberFormatHelper;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;

class ExportReportController extends Controller
{
    protected $livelihoodAnnexureController;
    protected $institutionalGroupController;
    protected $residentialSkillTrainingController;
    protected $crisisInterventionCenterController;

    public function __construct(
        LivelihoodAnnexureController $livelihoodAnnexureController,
        InstitutionalOngoingGroupController $institutionalGroupController,
        ResidentialSkillTrainingController $residentialSkillTrainingController,
        CrisisInterventionCenterController $crisisInterventionCenterController
    ) {
        $this->livelihoodAnnexureController = $livelihoodAnnexureController;
        $this->institutionalGroupController = $institutionalGroupController;
        $this->residentialSkillTrainingController = $residentialSkillTrainingController;
        $this->crisisInterventionCenterController = $crisisInterventionCenterController;
    }

    public function downloadPdf($report_id)
    {
        // Increase memory limit for PDF generation
        ini_set('memory_limit', '512M');

        try {
            $report = DPReport::where('report_id', $report_id)
                ->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                case 'applicant':
                    // Executors/Applicants can download their own reports
                    if ($report->user_id === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'provincial':
                    // Provincials can download reports from executors under them
                    if ($report->user->parent_id === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'coordinator':
                case 'general':
                    // Coordinators and General users can download all reports
                    $hasAccess = true;
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this report.');
            }

            // Get associated project and budgets using project-type-specific method
            $project = Project::where('project_id', $report->project_id)->first();

            // Get actual account details from the report instead of project budgets
            $budgets = $report->accountDetails;

            // Group photos by category and prepare for PDF (with size optimization)
            $groupedPhotos = $this->preparePhotosForPdfOptimized($report->photos);
            $totalPhotos = $this->countTotalPhotos($groupedPhotos);

            // Get annexures and profiles based on project type
            $annexures = [];
            $ageProfiles = [];
            $traineeProfiles = [];
            $inmateProfiles = [];

            switch ($report->project_type) {
                case 'Livelihood Development Projects':
                    $annexures = $this->livelihoodAnnexureController->getAnnexures($report_id);
                    break;
                case 'Institutional Ongoing Group Educational proposal':
                    $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                    break;
                case 'Residential Skill Training Proposal 2':
                    $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                    break;
                case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                    $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                    break;
            }

            // Load all report data
            $data = [
                'report' => $report,
                'project' => $project,
                'budgets' => $budgets,
                'groupedPhotos' => $groupedPhotos,
                'totalPhotos' => $totalPhotos,
                'annexures' => $annexures,
                'ageProfiles' => $ageProfiles,
                'traineeProfiles' => $traineeProfiles,
                'inmateProfiles' => $inmateProfiles,
                'user' => Auth::user(),
            ];

            // Try to generate PDF with photos first
            try {
                $html = view('reports.monthly.PDFReport', $data)->render();
                $mpdf = $this->initializeMpdf();
                $mpdf->WriteHTML($html);
            } catch (\Exception $e) {
                // If photos cause issues, try without photos
                Log::warning('PDF generation failed with photos, retrying without photos', [
                    'error' => $e->getMessage(),
                    'report_id' => $report_id
                ]);

                $data['groupedPhotos'] = [];
                $data['excludePhotos'] = true;
                $html = view('reports.monthly.PDFReport', $data)->render();
                $mpdf = $this->initializeMpdf();
                $mpdf->WriteHTML($html);
            }

            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="monthly_report_' . $report_id . '.pdf"');
        } catch (\Exception $e) {
            Log::error('ExportReportController@downloadPdf - Error', [
                'error' => $e->getMessage(),
                'report_id' => $report_id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize mPDF with optimized settings
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
     * Prepare photos for PDF generation with size optimization
     */
    private function preparePhotosForPdfOptimized($photos)
    {
        $groupedPhotos = [];
        $totalPhotos = 0;
        $maxPhotos = 15; // Reduced from 20 to 15 photos

        if ($photos && $photos->count() > 0) {
            foreach ($photos as $photo) {
                // Limit total photos to prevent memory issues
                if ($totalPhotos >= $maxPhotos) {
                    break;
                }

                $description = $photo->description ?? 'Other';

                if (!isset($groupedPhotos[$description])) {
                    $groupedPhotos[$description] = [];
                }

                // Check file size before including - reduced to 2MB limit
                $filePath = $photo->photo_path;
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    $fileSize = Storage::disk('public')->size($filePath);
                    $maxFileSize = 2 * 1024 * 1024; // 2MB limit per photo (reduced from 5MB)

                    if ($fileSize > $maxFileSize) {
                        // Skip large files to prevent memory issues
                        continue;
                    }
                }

                // For PDF, we'll use file paths instead of base64 to reduce memory usage
                $groupedPhotos[$description][] = [
                    'photo_name' => $photo->photo_name ?? 'Photo',
                    'description' => $photo->description ?? '',
                    'photo_path' => $photo->photo_path,
                    'file_exists' => Storage::disk('public')->exists($photo->photo_path),
                    'full_path' => Storage::disk('public')->exists($photo->photo_path)
                        ? storage_path('app/public/' . $photo->photo_path)
                        : null
                ];

                $totalPhotos++;
            }
        }

        return $groupedPhotos;
    }

    public function downloadDoc($report_id)
    {
        try {
            $report = DPReport::where('report_id', $report_id)
                ->with(['objectives.activities', 'accountDetails', 'photos', 'outlooks', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                case 'applicant':
                    // Executors/Applicants can download their own reports
                    if ($report->user_id === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'provincial':
                    // Provincials can download reports from executors under them
                    if ($report->user->parent_id === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'coordinator':
                case 'general':
                    // Coordinators and General users can download all reports
                    $hasAccess = true;
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this report.');
            }

            // Get associated project and budgets using project-type-specific method
            $project = Project::where('project_id', $report->project_id)->first();

            // Get actual account details from the report instead of project budgets
            $budgets = $report->accountDetails;

            // Group photos by category and prepare for DOC
            $groupedPhotos = $this->preparePhotosForDoc($report->photos);

            // Get annexures and profiles based on project type
            $annexures = [];
            $ageProfiles = [];
            $traineeProfiles = [];
            $inmateProfiles = [];

            switch ($report->project_type) {
                case 'Livelihood Development Projects':
                    $annexures = $this->livelihoodAnnexureController->getAnnexures($report_id);
                    break;
                case 'Institutional Ongoing Group Educational proposal':
                    $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                    break;
                case 'Residential Skill Training Proposal 2':
                    $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                    break;
                case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                    $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                    break;
            }

            $phpWord = new PhpWord();

            // Add sections
            $this->addGeneralInfoSection($phpWord, $report, $project);
            $this->addSpecificProjectSection($phpWord, $report, $annexures, $ageProfiles, $traineeProfiles, $inmateProfiles);
            $this->addObjectivesSection($phpWord, $report);
            $this->addOutlookSection($phpWord, $report);
            $this->addStatementsOfAccountSection($phpWord, $report, $budgets);
            $this->addPhotosSection($phpWord, $report, $groupedPhotos);
            $this->addAttachmentsSection($phpWord, $report);

            // Save the file and return response
            $filePath = storage_path("app/public/Monthly_Report_{$report->report_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            Log::info('ExportReportController@downloadDoc - DOC generated', ['report_id' => $report_id]);
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('ExportReportController@downloadDoc - Error', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }

    /**
     * Prepare photos for DOC generation
     */
    private function preparePhotosForDoc($photos)
    {
        $groupedPhotos = [];

        if ($photos && $photos->count() > 0) {
            foreach ($photos as $photo) {
                $description = $photo->description ?? 'Other';

                if (!isset($groupedPhotos[$description])) {
                    $groupedPhotos[$description] = [];
                }

                $groupedPhotos[$description][] = [
                    'photo_name' => $photo->photo_name ?? 'Photo',
                    'description' => $photo->description ?? '',
                    'photo_path' => $photo->photo_path,
                    'file_exists' => Storage::disk('public')->exists($photo->photo_path)
                ];
            }
        }

        return $groupedPhotos;
    }

    /**
     * Get budget data based on project type
     */
    private function getBudgetDataByProjectType($project)
    {
        if (!$project) {
            return collect();
        }

        return \App\Services\Budget\BudgetCalculationService::getBudgetsForExport($project);
    }

    // Budget calculation methods removed - now using BudgetCalculationService
    // See: app/Services/Budget/BudgetCalculationService.php

    // General info
    private function addGeneralInfoSection(PhpWord $phpWord, $report, $project)
    {
        $section = $phpWord->addSection();
        $section->addText("Monthly Report Details", ['bold' => true, 'size' => 16]);
        $section->addTextBreak(1);

        $section->addText("Basic Information", ['bold' => true, 'size' => 14]);
        $section->addText("Project ID: {$report->project_id}");
        $section->addText("Report ID: {$report->report_id}");
        $section->addText("Project Title: {$report->project_title}");
        $section->addText("Project Type: {$report->project_type}");
        $section->addText("Society Name: {$report->society_name}");
        $section->addText("Place: {$report->place}");
        $section->addText("In Charge: {$report->in_charge}");
        $section->addText("Total Beneficiaries: {$report->total_beneficiaries}");
        $section->addText("Goal: {$report->goal}");
        $section->addText("Report Month & Year: " . (\Carbon\Carbon::parse($report->report_month_year)->format('F Y') ?? 'N/A'));
        $section->addText("Commencement Month & Year: " . (\Carbon\Carbon::parse($report->commencement_month_year)->format('F Y') ?? 'N/A'));
        $section->addTextBreak(1);
    }

    // Specific Project Type Section
    private function addSpecificProjectSection(PhpWord $phpWord, $report, $annexures, $ageProfiles, $traineeProfiles, $inmateProfiles)
    {
        $section = $phpWord->addSection();

        switch ($report->project_type) {
            case 'Livelihood Development Projects':
                $this->addLivelihoodAnnexureSection($phpWord, $annexures);
                break;
            case 'Institutional Ongoing Group Educational proposal':
                $this->addInstitutionalGroupSection($phpWord, $ageProfiles);
                break;
            case 'Residential Skill Training Proposal 2':
                $this->addResidentialSkillTrainingSection($phpWord, $traineeProfiles);
                break;
            case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                $this->addCrisisInterventionCenterSection($phpWord, $inmateProfiles);
                break;
        }
    }

    // Livelihood Development Projects Section
    private function addLivelihoodAnnexureSection(PhpWord $phpWord, $annexures)
    {
        $section = $phpWord->addSection();
        $section->addText("Livelihood Development Projects - Annexure", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if (!empty($annexures) && count($annexures) > 0) {
            $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
            $phpWord->addTableStyle('LivelihoodTable', $tableStyle);
            $table = $section->addTable('LivelihoodTable');

            $table->addRow();
            $table->addCell(1000)->addText("S.No.", ['bold' => true]);
            $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
            $table->addCell(3000)->addText("Family Situation", ['bold' => true]);
            $table->addCell(3000)->addText("Nature of Livelihood", ['bold' => true]);
            $table->addCell(2000)->addText("Amount Requested", ['bold' => true]);

            foreach ($annexures as $index => $annexure) {
                $table->addRow();
                $table->addCell(1000)->addText($index + 1);
                $table->addCell(3000)->addText($annexure->beneficiary_name ?? 'N/A');
                $table->addCell(3000)->addText($annexure->family_situation ?? 'N/A');
                $table->addCell(3000)->addText($annexure->nature_of_livelihood ?? 'N/A');
                $table->addCell(2000)->addText($annexure->amount_requested ? NumberFormatHelper::formatIndianCurrency($annexure->amount_requested, 2) : 'N/A');
            }
        } else {
            $section->addText("No annexure data available.", ['italic' => true]);
        }
        $section->addTextBreak(1);
    }

    // Institutional Ongoing Group Educational Section
    private function addInstitutionalGroupSection(PhpWord $phpWord, $ageProfiles)
    {
        $section = $phpWord->addSection();
        $section->addText("Institutional Ongoing Group Educational - Age Profiles", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if (!empty($ageProfiles) && count($ageProfiles) > 0) {
            $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
            $phpWord->addTableStyle('AgeProfileTable', $tableStyle);
            $table = $section->addTable('AgeProfileTable');

            $table->addRow();
            $table->addCell(1000)->addText("S.No.", ['bold' => true]);
            $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
            $table->addCell(2000)->addText("Age", ['bold' => true]);
            $table->addCell(3000)->addText("Class/Standard", ['bold' => true]);
            $table->addCell(3000)->addText("Performance", ['bold' => true]);

            foreach ($ageProfiles as $index => $profile) {
                $table->addRow();
                $table->addCell(1000)->addText($index + 1);
                $table->addCell(3000)->addText($profile->beneficiary_name ?? 'N/A');
                $table->addCell(2000)->addText($profile->age ?? 'N/A');
                $table->addCell(3000)->addText($profile->class_standard ?? 'N/A');
                $table->addCell(3000)->addText($profile->performance ?? 'N/A');
            }
        } else {
            $section->addText("No age profile data available.", ['italic' => true]);
        }
        $section->addTextBreak(1);
    }

    // Residential Skill Training Section
    private function addResidentialSkillTrainingSection(PhpWord $phpWord, $traineeProfiles)
    {
        $section = $phpWord->addSection();
        $section->addText("Residential Skill Training - Trainee Profiles", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if (!empty($traineeProfiles) && count($traineeProfiles) > 0) {
            $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
            $phpWord->addTableStyle('TraineeTable', $tableStyle);
            $table = $section->addTable('TraineeTable');

            $table->addRow();
            $table->addCell(1000)->addText("S.No.", ['bold' => true]);
            $table->addCell(3000)->addText("Trainee Name", ['bold' => true]);
            $table->addCell(2000)->addText("Age", ['bold' => true]);
            $table->addCell(3000)->addText("Skill Training", ['bold' => true]);
            $table->addCell(3000)->addText("Progress", ['bold' => true]);

            foreach ($traineeProfiles as $index => $profile) {
                $table->addRow();
                $table->addCell(1000)->addText($index + 1);
                $table->addCell(3000)->addText($profile->trainee_name ?? 'N/A');
                $table->addCell(2000)->addText($profile->age ?? 'N/A');
                $table->addCell(3000)->addText($profile->skill_training ?? 'N/A');
                $table->addCell(3000)->addText($profile->progress ?? 'N/A');
            }
        } else {
            $section->addText("No trainee profile data available.", ['italic' => true]);
        }
        $section->addTextBreak(1);
    }

    // Crisis Intervention Center Section
    private function addCrisisInterventionCenterSection(PhpWord $phpWord, $inmateProfiles)
    {
        $section = $phpWord->addSection();
        $section->addText("Crisis Intervention Center - Inmate Profiles", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if (!empty($inmateProfiles) && count($inmateProfiles) > 0) {
            $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
            $phpWord->addTableStyle('InmateTable', $tableStyle);
            $table = $section->addTable('InmateTable');

            $table->addRow();
            $table->addCell(1000)->addText("S.No.", ['bold' => true]);
            $table->addCell(3000)->addText("Inmate Name", ['bold' => true]);
            $table->addCell(2000)->addText("Age", ['bold' => true]);
            $table->addCell(3000)->addText("Reason for Admission", ['bold' => true]);
            $table->addCell(3000)->addText("Status", ['bold' => true]);

            foreach ($inmateProfiles as $index => $profile) {
                $table->addRow();
                $table->addCell(1000)->addText($index + 1);
                $table->addCell(3000)->addText($profile->inmate_name ?? 'N/A');
                $table->addCell(2000)->addText($profile->age ?? 'N/A');
                $table->addCell(3000)->addText($profile->reason_for_admission ?? 'N/A');
                $table->addCell(3000)->addText($profile->status ?? 'N/A');
            }
        } else {
            $section->addText("No inmate profile data available.", ['italic' => true]);
        }
        $section->addTextBreak(1);
    }

    // Objectives Section
    private function addObjectivesSection(PhpWord $phpWord, $report)
    {
        $section = $phpWord->addSection();
        $section->addText("Objectives", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if ($report->objectives && $report->objectives->count() > 0) {
            foreach ($report->objectives as $index => $objective) {
                $section->addText("Objective " . ($index + 1), ['bold' => true, 'size' => 12]);
                $section->addText("Objective: " . ($objective->objective ?? 'N/A'));
                $section->addText("Expected Outcome: " . ($objective->expected_outcome ?? 'N/A'));
                $section->addText("What Did Not Happen: " . ($objective->not_happened ?? 'N/A'));
                $section->addText("Why Some Activities Could Not Be Undertaken: " . ($objective->why_not_happened ?? 'N/A'));
                $section->addText("Changes: " . ($objective->changes ? 'Yes' : 'No'));
                if ($objective->changes) {
                    $section->addText("Why Changes Were Needed: " . ($objective->why_changes ?? 'N/A'));
                }
                $section->addText("Lessons Learnt: " . ($objective->lessons_learnt ?? 'N/A'));
                $section->addText("What Will Be Done Differently: " . ($objective->todo_lessons_learnt ?? 'N/A'));
                $section->addTextBreak(1);

                // Activities for this objective
                if ($objective->activities && $objective->activities->count() > 0) {
                    $section->addText("Activities for Objective " . ($index + 1), ['bold' => true, 'size' => 12]);
                    foreach ($objective->activities as $activityIndex => $activity) {
                        $section->addText("Activity " . ($activityIndex + 1), ['bold' => true]);
                        $section->addText("Month: " . ($activity->month ?? 'N/A'));
                        $section->addText("Summary of Activities: " . ($activity->summary_activities ?? 'N/A'));
                        $section->addText("Qualitative & Quantitative Data: " . ($activity->qualitative_quantitative_data ?? 'N/A'));
                        $section->addText("Intermediate Outcomes: " . ($activity->intermediate_outcomes ?? 'N/A'));
                        $section->addTextBreak(0.5);
                    }
                }
                $section->addTextBreak(1);
            }
        } else {
            $section->addText("No objectives data available.", ['italic' => true]);
        }
    }

    // Outlook Section
    private function addOutlookSection(PhpWord $phpWord, $report)
    {
        $section = $phpWord->addSection();
        $section->addText("Outlooks", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if ($report->outlooks && $report->outlooks->count() > 0) {
            foreach ($report->outlooks as $outlook) {
                $section->addText("Date: " . (\Carbon\Carbon::parse($outlook->date)->format('d-m-Y') ?? 'N/A'));
                $section->addText("Action Plan for Next Month: " . ($outlook->plan_next_month ?? 'N/A'));
                $section->addTextBreak(1);
            }
        } else {
            $section->addText("No outlook data available.", ['italic' => true]);
        }
    }

    // Statements of Account Section
    private function addStatementsOfAccountSection(PhpWord $phpWord, $report, $budgets)
    {
        $section = $phpWord->addSection();
        $section->addText("Statements of Account", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        // Account Overview
        $section->addText("Account Overview", ['bold' => true, 'size' => 12]);
        $section->addText("Account Period: " . (\Carbon\Carbon::parse($report->account_period_start)->format('d-m-Y') ?? 'N/A') . " to " . (\Carbon\Carbon::parse($report->account_period_end)->format('d-m-Y') ?? 'N/A'));
        $section->addText("Amount Sanctioned: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($report->amount_sanctioned_overview ?? 0, 2));
        $section->addText("Total Amount: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($report->amount_in_hand ?? 0, 2));
        $section->addText("Balance Forwarded: " . \App\Helpers\NumberFormatHelper::formatIndianCurrency($report->total_balance_forwarded ?? 0, 2));
        $section->addTextBreak(1);

        // Account Details Table
        if ($report->accountDetails && $report->accountDetails->count() > 0) {
            $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
            $phpWord->addTableStyle('AccountTable', $tableStyle);
            $table = $section->addTable('AccountTable');

            $table->addRow();
            $table->addCell(2000)->addText("Particulars", ['bold' => true]);
            $table->addCell(1500)->addText("Amount Sanctioned", ['bold' => true]);
            $table->addCell(1500)->addText("Total Amount", ['bold' => true]);
            $table->addCell(1500)->addText("Expenses Last Month", ['bold' => true]);
            $table->addCell(1500)->addText("Expenses This Month", ['bold' => true]);
            $table->addCell(1500)->addText("Total Expenses", ['bold' => true]);
            $table->addCell(1500)->addText("Balance Amount", ['bold' => true]);

            foreach ($report->accountDetails as $accountDetail) {
                $table->addRow();
                $particulars = $accountDetail->particulars ?? 'N/A';
                if ($accountDetail->is_budget_row) {
                    $particulars .= ' (Budget Row)';
                }
                $table->addCell(2000)->addText($particulars);
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->amount_sanctioned ?? 0, 2));
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->total_amount ?? 0, 2));
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->expenses_last_month ?? 0, 2));
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->expenses_this_month ?? 0, 2));
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->total_expenses ?? 0, 2));
                $table->addCell(1500)->addText(\App\Helpers\NumberFormatHelper::formatIndianCurrency($accountDetail->balance_amount ?? 0, 2));
            }
        } else {
            $section->addText("No account details available.", ['italic' => true]);
        }
        $section->addTextBreak(1);
    }

    // Photos Section
    private function addPhotosSection(PhpWord $phpWord, $report, $groupedPhotos)
    {
        $section = $phpWord->addSection();
        $section->addText("Photos", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        if (!empty($groupedPhotos)) {
            foreach ($groupedPhotos as $category => $photos) {
                $section->addText("Category: " . $category, ['bold' => true, 'size' => 12]);
                $section->addTextBreak(0.5);

                foreach ($photos as $photo) {
                    $section->addText("Photo: " . ($photo['photo_name'] ?? 'N/A'));
                    $section->addText("Description: " . ($photo['description'] ?? 'N/A'));

                    if ($photo['file_exists']) {
                        $section->addText("Status: Available", ['italic' => true, 'color' => '008000']);
                    } else {
                        $section->addText("Status: File not found", ['italic' => true, 'color' => 'FF0000']);
                    }

                    $section->addTextBreak(0.5);
                }
                $section->addTextBreak(1);
            }
        } else {
            $section->addText("No photos available.", ['italic' => true]);
        }
    }

    // Attachments Section
    private function addAttachmentsSection(PhpWord $phpWord, $report)
    {
        $section = $phpWord->addSection();
        $section->addText("Attachments", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);
        $section->addText("Attachments are available for download in the system.", ['italic' => true]);
        $section->addTextBreak(1);
    }

    /**
     * Count total photos in grouped photos array
     */
    private function countTotalPhotos($groupedPhotos)
    {
        $totalPhotos = 0;
        foreach ($groupedPhotos as $category => $photos) {
            $totalPhotos += count($photos);
        }
        return $totalPhotos;
    }
}
