<?php


namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

    public function downloadDoc($report_id)
    {
        set_time_limit(300); // Increase execution time

        try {
            // Use eager loading to reduce the number of queries
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks', 'user'])
                              ->findOrFail($report_id);

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                    // Executors can download their own reports
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
                    // Coordinators can download all reports except individual project types
                    $excludedTypes = [
                        'Individual - Ongoing Educational support',
                        'Individual - Livelihood Application',
                        'Individual - Access to Health',
                        'Individual - Initial - Educational support'
                    ];
                    if (!in_array($report->project_type, $excludedTypes)) {
                        $hasAccess = true;
                    }
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this report.');
            }

            // Get associated project and budgets
            $project = Project::where('project_id', $report->project_id)->first();
            $budgets = ProjectBudget::where('project_id', $report->project_id)->get();

            // Group photos by category
            $groupedPhotos = [];
            if ($report->photos) {
                foreach ($report->photos as $photo) {
                    $category = $photo->category ?? 'Other';
                    if (!isset($groupedPhotos[$category])) {
                        $groupedPhotos[$category] = [];
                    }
                    $groupedPhotos[$category][] = $photo;
                }
            }

            // Get annexures
            $annexures = [];
            if ($report->outlooks) {
                $annexures = $report->outlooks;
            }

            // Get age profiles, trainee profiles, and inmate profiles based on project type
            $ageProfiles = [];
            $traineeProfiles = [];
            $inmateProfiles = [];

            switch ($report->project_type) {
                case 'CHILD CARE INSTITUTION':
                    $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                    break;
                case 'Residential Skill Training Proposal 2':
                    $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                    break;
                case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                    $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                    break;
            }

            // Render the Blade template for Word
            $html = view('reports.monthly.doc', compact(
                'report',
                'groupedPhotos',
                'project',
                'budgets',
                'annexures',
                'ageProfiles',
                'traineeProfiles',
                'inmateProfiles'
            ))->render();

            // Generate Word document
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);

            $filePath = storage_path("app/public/Report_{$report->report_id}.docx");
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);

            Log::info('DOC file generated successfully', ['report_id' => $report_id]);

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error generating DOC file', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }

    public function downloadPdf($report_id)
    {
        set_time_limit(300); // Increase execution time

        try {
            // Use eager loading to reduce the number of queries
            $report = DPReport::with(['objectives.activities', 'accountDetails', 'photos', 'outlooks', 'user'])
                              ->findOrFail($report_id);

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                    // Executors can download their own reports
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
                    // Coordinators can download all reports except individual project types
                    $excludedTypes = [
                        'Individual - Ongoing Educational support',
                        'Individual - Livelihood Application',
                        'Individual - Access to Health',
                        'Individual - Initial - Educational support'
                    ];
                    if (!in_array($report->project_type, $excludedTypes)) {
                        $hasAccess = true;
                    }
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this report.');
            }

            // Get associated project and budgets
            $project = Project::where('project_id', $report->project_id)->first();
            $budgets = ProjectBudget::where('project_id', $report->project_id)->get();

            // Group photos by category
            $groupedPhotos = [];
            if ($report->photos) {
                foreach ($report->photos as $photo) {
                    $category = $photo->category ?? 'Other';
                    if (!isset($groupedPhotos[$category])) {
                        $groupedPhotos[$category] = [];
                    }
                    $groupedPhotos[$category][] = $photo;
                }
            }

            // Get annexures
            $annexures = [];
            if ($report->outlooks) {
                $annexures = $report->outlooks;
            }

            // Get age profiles, trainee profiles, and inmate profiles based on project type
            $ageProfiles = [];
            $traineeProfiles = [];
            $inmateProfiles = [];

            switch ($report->project_type) {
                case 'CHILD CARE INSTITUTION':
                    $ageProfiles = $this->institutionalGroupController->getAgeProfiles($report_id);
                    break;
                case 'Residential Skill Training Proposal 2':
                    $traineeProfiles = $this->residentialSkillTrainingController->getTraineeProfiles($report_id);
                    break;
                case 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER':
                    $inmateProfiles = $this->crisisInterventionCenterController->getInmateProfiles($report_id);
                    break;
            }

            $pdf = PDF::loadView('reports.monthly.pdf', compact(
                'report',
                'groupedPhotos',
                'project',
                'budgets',
                'annexures',
                'ageProfiles',
                'traineeProfiles',
                'inmateProfiles'
            ));

            Log::info('PDF file generated successfully', ['report_id' => $report_id]);

            return $pdf->download("report_{$report_id}.pdf");
        } catch (\Exception $e) {
            Log::error('Error generating PDF file', ['error' => $e->getMessage(), 'report_id' => $report_id]);
            throw $e;
        }
    }
}
