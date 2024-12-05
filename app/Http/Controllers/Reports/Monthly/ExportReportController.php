<?php


namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\DPReport;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectBudget;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\Log;

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
            // Fetch the report with relationships
            $report = DPReport::with([
                'objectives.activities.timeframes',
                'accountDetails',
                'photos',
                'outlooks',
                'attachments'
            ])->findOrFail($report_id);

            // Decode expected outcomes for objectives
            foreach ($report->objectives as $objective) {
                $objective->expected_outcome = json_decode($objective->expected_outcome, true) ?? [];
            }

            // Group photos by description
            $groupedPhotos = $report->photos->groupBy('description');

            // Fetch project and budgets
            $project = Project::where('project_id', $report->project_id)->firstOrFail();
            $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
            $budgets = ProjectBudget::where('project_id', $project->project_id)
                                    ->where('phase', $highestPhase)
                                    ->get();

            // Fetch additional data based on project type
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

                    // Prepare education data for Residential Skill Training
                    $education = [];
                    foreach ($traineeProfiles as $profile) {
                        $category = $profile->education_category;
                        $number = $profile->number;

                        switch ($category) {
                            case 'Below 9th standard':
                                $education['below_9'] = $number;
                                break;
                            case '10th class failed':
                                $education['class_10_fail'] = $number;
                                break;
                            case '10th class passed':
                                $education['class_10_pass'] = $number;
                                break;
                            case 'Intermediate':
                                $education['intermediate'] = $number;
                                break;
                            case 'Intermediate and above':
                                $education['above_intermediate'] = $number;
                                break;
                            case 'Total':
                                $education['total'] = $number;
                                break;
                            default:
                                $education['other'] = $category;
                                $education['other_count'] = $number;
                                break;
                        }
                    }
                    $report->education = $education;
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
            $report = DPReport::with([
                'objectives.activities.timeframes',
                'accountDetails',
                'photos',
                'outlooks',
                'attachments'
            ])->findOrFail($report_id);

            foreach ($report->objectives as $objective) {
                $objective->expected_outcome = json_decode($objective->expected_outcome, true) ?? [];
            }

            $groupedPhotos = $report->photos->groupBy('description');

            $project = Project::where('project_id', $report->project_id)->firstOrFail();
            $highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
            $budgets = ProjectBudget::where('project_id', $project->project_id)
                                    ->where('phase', $highestPhase)
                                    ->get();

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
                    $report->education = $education ?? [];
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
