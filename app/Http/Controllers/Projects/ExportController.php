<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Models\User;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

class ExportController extends Controller
{

    public function downloadPdf($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with(['attachments', 'objectives.risks', 'objectives.activities.timeframes', 'sustainabilities', 'budgets', 'user'])
                ->firstOrFail();

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                    // Executors can download their own projects
                    if ($project->user_id === $user->id || $project->in_charge === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'provincial':
                    // Provincials can download projects from executors under them with specific statuses
                    if ($project->user->parent_id === $user->id) {
                        if (in_array($project->status, ['submitted_to_provincial', 'reverted_by_coordinator', 'approved_by_coordinator'])) {
                            $hasAccess = true;
                        }
                    }
                    break;

                case 'coordinator':
                    // Coordinators can download projects with various statuses
                    if (in_array($project->status, ['forwarded_to_coordinator', 'approved_by_coordinator', 'reverted_by_coordinator'])) {
                        $hasAccess = true;
                    }
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this project.');
            }

            $generalUser = User::where('role', 'general')->first();

            $projectRoles = [
                'executor' => $project->executor_name,
                'incharge' => $project->in_charge_name,
                'president' => $project->president_name,
                'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
                'coordinator' => $project->coordinator_india_name
            ];

            $html = view('projects.Oldprojects.pdf', compact('project', 'projectRoles'))->render();
            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);
            return response($mpdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="project_' . $project_id . '.pdf"');
        } catch (\Exception $e) {
            Log::error('ExportController@downloadPdf - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }



    // Additional methods as required...



    //     public function downloadDoc($project_id)
    // {
    //     try {
    //         $project = Project::where('project_id', $project_id)
    //             ->with([
    //                 'attachments',
    //                 'objectives.risks',
    //                 'objectives.activities.timeframes',
    //                 'sustainabilities',
    //                 'budgets'
    //             ])->firstOrFail();

    //         $generalUser = User::where('role', 'general')->first();

    //         $projectRoles = [
    //             'executor' => $project->executor_name,
    //             'incharge' => $project->in_charge_name, // Replacing 'applicant' with 'incharge'
    //             'president' => $project->president_name,
    //             'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
    //             'coordinator' => $project->coordinator_india_name // Fetching the Project Coordinator's name
    //         ];

    //         $phpWord = new PhpWord();
    //         $section = $phpWord->addSection();

    //         // General Information
    //         $section->addText("Project Details", ['bold' => true, 'size' => 16]);
    //         $section->addText("Project ID: {$project->project_id}");
    //         $section->addText("Project Title: {$project->project_title}");
    //         $section->addText("Project Type: {$project->project_type}");
    //         $section->addText("Society Name: {$project->society_name}");
    //         $section->addText("President Name: {$project->president_name}");
    //         $section->addText("In Charge Name: {$project->in_charge_name}");
    //         $section->addText("Executor Name: {$project->executor_name}");
    //         $section->addText("Executor Phone: {$project->executor_mobile}");
    //         $section->addText("Executor Email: {$project->executor_email}");
    //         $section->addText("Full Address: {$project->full_address}");
    //         $section->addText("Overall Project Period: {$project->overall_project_period} years");
    //         $section->addText("Overall Project Budget: Rs. " . number_format($project->overall_project_budget, 2));
    //         $section->addText("Amount Forwarded: Rs. " . number_format($project->amount_forwarded, 2));
    //         $section->addText("Amount Sanctioned: Rs. " . number_format($project->amount_sanctioned, 2));
    //         $section->addText("Opening Balance: Rs. " . number_format($project->opening_balance, 2));
    //         $section->addText("Coordinator India Name: {$project->coordinator_india_name}");
    //         $section->addText("Coordinator India Phone: {$project->coordinator_india_phone}");
    //         $section->addText("Coordinator India Email: {$project->coordinator_india_email}");
    //         $section->addText("Coordinator Luzern Name: {$project->coordinator_luzern_name}");
    //         $section->addText("Coordinator Luzern Phone: {$project->coordinator_luzern_phone}");
    //         $section->addText("Coordinator Luzern Email: {$project->coordinator_luzern_email}");
    //         $section->addText("Status: " . ucfirst($project->status));

    //         // Add spacing between sections
    //         $section->addTextBreak(1);

    //         // Goal of the Project
    //         $section->addText("Goal of the Project:", ['bold' => true]);
    //         $section->addText($project->goal);

    //         // Add spacing between sections
    //         $section->addTextBreak(1);

    //         // Logical Framework Section
    //         foreach ($project->objectives as $objective) {
    //             $section->addText("Objective: {$objective->objective}", ['bold' => true]);
    //             $section->addTextBreak(0.5);

    //             // Results / Outcomes
    //             $section->addText("Results / Outcomes:", ['bold' => true]);
    //             foreach ($objective->results as $result) {
    //                 $section->addText($result->result);
    //             }
    //             $section->addTextBreak(0.5);

    //            // Risks
    //            $section->addText("Risks:", ['bold' => true]);
    //            foreach ($objective->risks as $risk) {
    //                $section->addText($risk->risk);
    //            }
    //            $section->addTextBreak(0.5);

    //             // Activities and Means of Verification
    //             $section->addText("Activities and Means of Verification:", ['bold' => true]);

    //             // Define table style
    //             $tableStyle = [
    //                 'borderSize' => 6, // 1pt = 8 twips, 0.75pt ≈ 6 twips
    //                 'borderColor' => '000000', // Black border
    //                 'cellMargin' => 80 // Adds padding inside cells
    //             ];
    //             $phpWord->addTableStyle('TableStyle', $tableStyle);
    //             $table = $section->addTable('TableStyle');
    //             $table->addRow();
    //             $table->addCell(5000)->addText("Activities");
    //             $table->addCell(5000)->addText("Means of Verification");
    //             foreach ($objective->activities as $activity) {
    //                 $table->addRow();
    //                 $table->addCell(5000)->addText($activity->activity);
    //                 $table->addCell(5000)->addText($activity->verification);
    //             }
    //             $section->addTextBreak(0.5);

    //             // Time Frame for Activities
    //             $section->addText("Time Frame for Activities:", ['bold' => true]);
    //             $table = $section->addTable('TableStyle');
    //             $table->addRow();
    //             $table->addCell(5000)->addText("Activities");
    //             foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month) {
    //                 $table->addCell(500)->addText($month);
    //             }
    //             foreach ($objective->activities as $activity) {
    //                 $table->addRow();
    //                 $table->addCell(5000)->addText($activity->activity);
    //                 foreach (range(1, 12) as $month) {
    //                     $isChecked = $activity->timeframes->contains(function($timeframe) use ($month) {
    //                         return $timeframe->month == $month && $timeframe->is_active == 1;
    //                     });
    //                     $table->addCell(500)->addText($isChecked ? '✔' : '');
    //                 }
    //             }
    //         }

    //         // Add spacing between sections
    //         $section->addTextBreak(1);

    //         // Sustainability Section
    //         $section->addText("Project Sustainability, Monitoring and Methodologies", ['bold' => true, 'size' => 14]);
    //         foreach ($project->sustainabilities as $sustainability) {
    //             $section->addText("Explain the Sustainability of the Project:", ['bold' => true]);
    //             $section->addText($sustainability->sustainability ?? 'N/A');
    //             $section->addTextBreak(0.5);

    //             $section->addText("Explain the Monitoring Process of the Project:", ['bold' => true]);
    //             $section->addText($sustainability->monitoring_process ?? 'N/A');
    //             $section->addTextBreak(0.5);

    //             $section->addText("Explain the Methodology of Reporting:", ['bold' => true]);
    //             $section->addText($sustainability->reporting_methodology ?? 'N/A');
    //             $section->addTextBreak(0.5);

    //             $section->addText("Explain the Methodology of Evaluation:", ['bold' => true]);
    //             $section->addText($sustainability->evaluation_methodology ?? 'N/A');
    //         }

    //         // Add spacing between sections
    //         $section->addTextBreak(1);

    //         // Budget Details
    //         $groupedBudgets = $project->budgets->groupBy('phase');
    //         foreach ($groupedBudgets as $phase => $budgets) {
    //             $section->addText("Phase $phase", ['bold' => true, 'size' => 14]);
    //             $section->addText("Amount Sanctioned in Phase $phase: Rs. " . number_format($budgets->sum('this_phase'), 2));
    //             $section->addTextBreak(0.5);

    //             $table = $section->addTable('TableStyle');
    //             $table->addRow();
    //             $table->addCell(4000)->addText("Particular");
    //             $table->addCell(1000)->addText("Costs");
    //             $table->addCell(1000)->addText("Rate Multiplier");
    //             $table->addCell(1000)->addText("Rate Duration");
    //             $table->addCell(1000)->addText("Rate Increase (next phase)");
    //             $table->addCell(1000)->addText("This Phase (Auto)");
    //             $table->addCell(1000)->addText("Next Phase (Auto)");

    //             foreach ($budgets as $budget) {
    //                 $table->addRow();
    //                 $table->addCell(4000)->addText($budget->particular);
    //                 $table->addCell(1000)->addText(number_format($budget->rate_quantity, 2));
    //                 $table->addCell(1000)->addText(number_format($budget->rate_multiplier, 2));
    //                 $table->addCell(1000)->addText(number_format($budget->rate_duration, 2));
    //                 $table->addCell(1000)->addText(number_format($budget->rate_increase, 2));
    //                 $table->addCell(1000)->addText(number_format($budget->this_phase, 2));
    //                 $table->addCell(1000)->addText(number_format($budget->next_phase, 2));
    //             }

    //             $table->addRow();
    //             $table->addCell(4000)->addText("Total");
    //             $table->addCell(1000)->addText(number_format($budgets->sum('rate_quantity'), 2));
    //             $table->addCell(1000)->addText(number_format($budgets->sum('rate_multiplier'), 2));
    //             $table->addCell(1000)->addText(number_format($budgets->sum('rate_duration'), 2));
    //             $table->addCell(1000)->addText(number_format($budgets->sum('rate_increase'), 2));
    //             $table->addCell(1000)->addText(number_format($budgets->sum('this_phase'), 2));
    //             $table->addCell(1000)->addText(number_format($budgets->sum('next_phase'), 2));
    //         }

    //         // Add spacing between sections
    //         $section->addTextBreak(1);

    //         // Attachment Details
    //         $section->addText("Attachments", ['bold' => true, 'size' => 14]);
    //         foreach ($project->attachments as $attachment) {
    //             $section->addText("Attachment: " . $attachment->file_name);
    //             $section->addText("Description: " . $attachment->description);
    //             $section->addTextBreak(0.5);
    //         }

    //         // Signature and Approval Sections with page break control
    //         $section = $phpWord->addSection(['breakType' => 'continuous']);
    //         $section->addTextBreak(1);
    //         $section->addText("Signatures", ['bold' => true, 'size' => 16]);

    //         $table = $section->addTable('TableStyle');
    //         $table->addRow();
    //         $table->addCell(5000)->addText("Person");
    //         $table->addCell(3000)->addText("Signature");
    //         $table->addCell(2000)->addText("Date");

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Project Executor\n" . ($projectRoles['executor'] ?? 'N/A'));
    //         $table->addCell(3000)->addText('');
    //         $table->addCell(2000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Project Incharge\n" . ($projectRoles['incharge'] ?? 'N/A'));
    //         $table->addCell(3000)->addText('');
    //         $table->addCell(2000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("President of the Society / Chair Person of the Trust\n" . ($projectRoles['president'] ?? 'N/A'));
    //         $table->addCell(3000)->addText('');
    //         $table->addCell(2000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Project Sanctioned / Authorised by\n" . ($projectRoles['authorizedBy'] ?? 'N/A'));
    //         $table->addCell(3000)->addText('');
    //         $table->addCell(2000)->addText('');

    //         $section->addTextBreak(1);
    //         $section->addText("APPROVAL - To be filled by the Project Coordinator:", ['bold' => true, 'size' => 14]);

    //         $table = $section->addTable('TableStyle');
    //         $table->addRow();
    //         $table->addCell(5000)->addText("Amount approved");
    //         $table->addCell(5000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Remarks if any");
    //         $table->addCell(5000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Project Coordinator\n" . ($projectRoles['coordinator'] ?? 'N/A'));
    //         $table->addCell(5000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Signature");
    //         $table->addCell(5000)->addText('');

    //         $table->addRow();
    //         $table->addCell(5000)->addText("Date");
    //         $table->addCell(5000)->addText('');

    //         // Save and download the DOCX file
    //         $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    //         $filePath = storage_path("app/public/Project_{$project->project_id}.docx");
    //         $objWriter->save($filePath);

    //         Log::info('ExportController@downloadDoc - DOC generated', ['project_id' => $project_id]);

    //         return response()->download($filePath)->deleteFileAfterSend(true);
    //     } catch (\Exception $e) {
    //         Log::error('ExportController@downloadDoc - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
    //         throw $e;
    //     }
    // }
    public function downloadDoc($project_id)
    {
        try {
            // Check if XML extension is available
            if (!extension_loaded('xml')) {
                Log::warning('ExportController@downloadDoc - XML extension not available, falling back to PDF', ['project_id' => $project_id]);
                return $this->downloadPdf($project_id);
            }

            $project = Project::where('project_id', $project_id)
                ->with([
                    'attachments',
                    'objectives.risks',
                    'objectives.activities.timeframes',
                    'sustainabilities',
                    'budgets'
                ])->firstOrFail();

            $user = Auth::user();

            // Role-based access control
            $hasAccess = false;

            switch ($user->role) {
                case 'executor':
                    // Executors can download their own projects
                    if ($project->user_id === $user->id || $project->in_charge === $user->id) {
                        $hasAccess = true;
                    }
                    break;

                case 'provincial':
                    // Provincials can download projects from executors under them with specific statuses
                    if ($project->user->parent_id === $user->id) {
                        if (in_array($project->status, ['submitted_to_provincial', 'reverted_by_coordinator', 'approved_by_coordinator'])) {
                            $hasAccess = true;
                        }
                    }
                    break;

                case 'coordinator':
                    // Coordinators can download projects with various statuses
                    if (in_array($project->status, ['forwarded_to_coordinator', 'approved_by_coordinator', 'reverted_by_coordinator'])) {
                        $hasAccess = true;
                    }
                    break;
            }

            if (!$hasAccess) {
                abort(403, 'You do not have permission to download this project.');
            }

            $generalUser = User::where('role', 'general')->first();

            $projectRoles = [
                'executor' => $project->executor_name,
                'incharge' => $project->in_charge_name,
                'president' => $project->president_name,
                'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
                'coordinator' => $project->coordinator_india_name
            ];

            Log::info('ExportController@downloadDoc - Starting Word document generation', ['project_id' => $project_id]);

            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // 1. General Information Section
            $this->addGeneralInfoSection($phpWord, $project, $projectRoles);

            // 2. Key Information Section
            $this->addKeyInformationSection($phpWord, $project);

            // 3. CCI Specific Partials
            if ($project->project_type === 'CHILD CARE INSTITUTION') {
                $this->addCCISections($phpWord, $project);
            }

            // 4. RST Specific Partials (Residential Skill Training Proposal 2, Development Projects, NEXT PHASE - DEVELOPMENT PROPOSAL)
            if (in_array($project->project_type, [
                'Residential Skill Training Proposal 2',
                'Development Projects',
                'NEXT PHASE - DEVELOPMENT PROPOSAL'
            ])) {
                $this->addRSTSections($phpWord, $project);
            }

            // 5. Edu-RUT Specific Partials
            if ($project->project_type === 'Rural-Urban-Tribal') {
                $this->addEduRUTSections($phpWord, $project);
            }

            // 6. Individual Project Types
            if (in_array($project->project_type, [
                'Individual - Ongoing Educational support',
                'Individual - Livelihood Application',
                'Individual - Access to Health',
                'Individual - Initial - Educational support'
            ])) {
                $this->addIndividualProjectSections($phpWord, $project);
            }

            // 7. IGE Specific Partials
            if ($project->project_type === 'Institutional Ongoing Group Educational proposal') {
                $this->addIGESpecificSections($phpWord, $project);
            }

            // 8. LDP Specific Partials
            if ($project->project_type === 'Livelihood Development Projects') {
                $this->addLDPSections($phpWord, $project);
            }

            // 9. CIC Specific Partials
            if ($project->project_type === 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER') {
                $this->addCICSections($phpWord, $project);
            }

            // 10. Common Sections (for non-individual types)
            if (!in_array($project->project_type, [
                'Individual - Ongoing Educational support',
                'Individual - Livelihood Application',
                'Individual - Access to Health',
                'Individual - Initial - Educational support'
            ])) {
                $this->addLogicalFrameworkSection($phpWord, $project);
                $this->addSustainabilitySection($phpWord, $project);
                $this->addBudgetSection($phpWord, $project);
                $this->addAttachmentsSection($phpWord, $project);
            }

            // Comments Section - currently not included in Word doc
            // You can add a method if needed.

            // 14. Signature and Approval Sections (common at the end)
            $this->addSignatureAndApprovalSections($phpWord, $project, $projectRoles);

            // Save the file and return response
            $filePath = storage_path("app/public/Project_{$project->project_id}.docx");
            IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

            Log::info('ExportController@downloadDoc - DOC generated', ['project_id' => $project_id]);
            return response()->download($filePath)->deleteFileAfterSend(true);

         }
         catch (\Exception $e)
        {

            Log::error('ExportController@downloadDoc - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }


// General info
private function addGeneralInfoSection(PhpWord $phpWord, $project, $projectRoles)
{
    // Add a new section to the document
    $section = $phpWord->addSection();
    $section->addText("Project Details", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Add General Information
    $section->addText("Basic Information", ['bold' => true, 'size' => 14]);
    $section->addText("Project ID: {$project->project_id}");
    $section->addText("Project Title: {$project->project_title}");
    $section->addText("Project Type: {$project->project_type}");
    $section->addText("Society Name: {$project->society_name}");
    $section->addText("President Name: {$project->president_name}");
    $section->addText("In Charge Name: {$project->in_charge_name}");
    $section->addText("In Charge Phone: {$project->in_charge_mobile}");
    $section->addText("In Charge Email: {$project->in_charge_email}");
    $section->addText("Executor Name: {$project->executor_name}");
    $section->addText("Executor Phone: {$project->executor_mobile}");
    $section->addText("Executor Email: {$project->executor_email}");
    $section->addText("Full Address: {$project->full_address}");
    $section->addText("Overall Project Period: {$project->overall_project_period} years");
    $section->addText(
        "Commencement Month & Year: " .
        (\Carbon\Carbon::parse($project->commencement_month_year)->format('F Y') ?? 'N/A')
    );
    $section->addText(
        "Overall Project Budget: Rs. " . number_format($project->overall_project_budget, 2)
    );
    $section->addText(
        "Amount Forwarded: Rs. " . number_format($project->amount_forwarded, 2)
    );
    $section->addText(
        "Amount Sanctioned: Rs. " . number_format($project->amount_sanctioned, 2)
    );
    $section->addText(
        "Opening Balance: Rs. " . number_format($project->opening_balance, 2)
    );
    $section->addText("Coordinator India Name: {$project->coordinator_india_name}");
    $section->addText("Coordinator India Phone: {$project->coordinator_india_phone}");
    $section->addText("Coordinator India Email: {$project->coordinator_india_email}");
    $section->addText("Coordinator Luzern Name: {$project->coordinator_luzern_name}");
    $section->addText("Coordinator Luzern Phone: {$project->coordinator_luzern_phone}");
    $section->addText("Coordinator Luzern Email: {$project->coordinator_luzern_email}");
    $section->addText("Status: " . ucfirst($project->status));
    $section->addTextBreak(1);

    // Add any additional fields if required in the future
}
//Key Information section
private function addKeyInformationSection(PhpWord $phpWord, $project)
{
    // Add a new section to the document
    $section = $phpWord->addSection();

    // Add a header for the section
    $section->addText("Key Information", ['bold' => true, 'size' => 14]);

    // Add a horizontal rule for visual separation
    $section->addText(str_repeat('-', 50), ['color' => 'gray']);

    // Add content in a grid-like structure
    $section->addText("Goal of the Project:", ['bold' => true]);
    $section->addText($project->goal ?? 'N/A', ['size' => 12]);

    // Add spacing at the end of the section
    $section->addTextBreak(1);
}
// CHILD CARE INSTITUTION specific functions
private function addCCISections(PhpWord $phpWord, $project)
{
    // Add rationale
    $this->addRationaleSection($phpWord, $project);

    // Add statistics
    $this->addStatisticsSection($phpWord, $project);

    // Add annexed target group
    $this->addAnnexedTargetGroupSection($phpWord, $project);

    // Add age profile
    $this->addAgeProfileSection($phpWord, $project);

    // Add personal situation
    $this->addPersonalSituationSection($phpWord, $project);

    // Add economic background
    $this->addEconomicBackgroundSection($phpWord, $project);

    // Add achievements
    $this->addAchievementsSection($phpWord, $project);

    // Add present situation
    $this->addPresentSituationSection($phpWord, $project);
}
//Section - Rationa - CHILD CARE INSTITUTION
private function addRationaleSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add section title
    $section->addText("Rationale", ['bold' => true, 'size' => 14]);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('RationaleTableStyle', $tableStyle);

    // Add table
    $table = $section->addTable('RationaleTableStyle');

    // Add rows
    $table->addRow();
    $table->addCell(3000)->addText("Description:", ['bold' => true]);
    $table->addCell(7000)->addText($project->rationale->description ?? 'No rationale provided yet.');
}
//Section - Statistics - CHILD CARE INSTITUTION

private function addStatisticsSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText(
        "Statistics of Passed out / Rehabilitated / Re-integrated Children till Date",
        ['bold' => true, 'size' => 14]
    );

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('StatisticsTable', $tableStyle);
    $table = $section->addTable('StatisticsTable');

    // Add header row
    $table->addRow();
    $table->addCell(7000, ['valign' => 'center'])->addText("Description", ['bold' => true]);
    $table->addCell(3000, ['valign' => 'center'])->addText("Upto Previous Year", ['bold' => true]);
    $table->addCell(3000, ['valign' => 'center'])->addText("Current Year on Roll", ['bold' => true]);

    // Add data rows
    $statistics = $project->statistics; // Assuming $statistics is available as a property or relation

    $rows = [
        'Total number of children in the institution' => [
            'previous' => $statistics->total_children_previous_year ?? 'N/A',
            'current' => $statistics->total_children_current_year ?? 'N/A',
        ],
        'Children who are reintegrated with their guardians/parents' => [
            'previous' => $statistics->reintegrated_children_previous_year ?? 'N/A',
            'current' => $statistics->reintegrated_children_current_year ?? 'N/A',
        ],
        'Children who are shifted to other NGOs / Govt.' => [
            'previous' => $statistics->shifted_children_previous_year ?? 'N/A',
            'current' => $statistics->shifted_children_current_year ?? 'N/A',
        ],
        'Children who are pursuing higher studies outside' => [
            'previous' => $statistics->pursuing_higher_studies_previous_year ?? 'N/A',
            'current' => $statistics->pursuing_higher_studies_current_year ?? 'N/A',
        ],
        'Children who completed the studies and settled down in life (i.e., married etc.)' => [
            'previous' => $statistics->settled_children_previous_year ?? 'N/A',
            'current' => $statistics->settled_children_current_year ?? 'N/A',
        ],
        'Children who are now settled and working' => [
            'previous' => $statistics->working_children_previous_year ?? 'N/A',
            'current' => $statistics->working_children_current_year ?? 'N/A',
        ],
        'Any other category' => [
            'previous' => $statistics->other_category_previous_year ?? 'N/A',
            'current' => $statistics->other_category_current_year ?? 'N/A',
        ],
    ];

    foreach ($rows as $description => $data) {
        $table->addRow();
        $table->addCell(7000)->addText($description);
        $table->addCell(3000)->addText($data['previous']);
        $table->addCell(3000)->addText($data['current']);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}
//Section - Target Group - CHILD CARE INSTITUTION

private function addAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Section Title
    $section->addText("Annexed Target Group (CCI)", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    // Check if annexedTargetGroup is available and not empty
    if (isset($project->annexed_target_groups) && $project->annexed_target_groups->isNotEmpty()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6, // 1pt = 8 twips, 0.75pt ≈ 6 twips
            'borderColor' => '000000', // Black border
            'cellMargin' => 80 // Adds padding inside cells
        ];
        $firstRowStyle = ['bgColor' => '101117']; // Dark background for header row
        $phpWord->addTableStyle('AnnexedTargetGroupTable', $tableStyle, $firstRowStyle);

        // Add Table
        $table = $section->addTable('AnnexedTargetGroupTable');

        // Add Header Row
        $table->addRow();
        $table->addCell(1000)->addText("S.No.", ['bold' => true, 'color' => 'FFFFFF'], ['align' => 'center']);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Date of Birth", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Date of Joining", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(2000)->addText("Class of Study", ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(4000)->addText("Family Background", ['bold' => true, 'color' => 'FFFFFF']);

        // Add Data Rows
        foreach ($project->annexed_target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1, [], ['align' => 'center']);
            $table->addCell(3000)->addText($group->beneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText(
                isset($group->dob) ? \Carbon\Carbon::parse($group->dob)->format('d/m/Y') : 'N/A'
            );
            $table->addCell(2000)->addText(
                isset($group->date_of_joining) ? \Carbon\Carbon::parse($group->date_of_joining)->format('d/m/Y') : 'N/A'
            );
            $table->addCell(2000)->addText($group->class_of_study ?? 'N/A');
            $table->addCell(4000)->addText($group->family_background_description ?? 'N/A');
        }
    } else {
        // No Data Message
        $section->addText("No data available for Annexed Target Group.", ['italic' => true, 'size' => 12]);
    }

    $section->addTextBreak(2); // Add some spacing after the table
}
//Section - Age Profile - CHILD CARE INSTITUTION
private function addAgeProfileSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Age Profile of Children in the Institution", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Table for Age Profile Data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
    ];
    $phpWord->addTableStyle('AgeProfileTable', $tableStyle);
    $table = $section->addTable('AgeProfileTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(4000)->addText("Age Category", ['bold' => true]);
    $table->addCell(3000)->addText("Education", ['bold' => true]);
    $table->addCell(2000)->addText("Up to Previous Year", ['bold' => true]);
    $table->addCell(2000)->addText("Present Academic Year", ['bold' => true]);

    // Age Profile Data
    $ageProfile = $project->age_profile; // Assuming this relation is fetched with the project

    $dataRows = [
        // Children below 5 years
        ['Children below 5 years', 'Bridge course', $ageProfile['education_below_5_bridge_course_prev_year'] ?? 'N/A', $ageProfile['education_below_5_bridge_course_current_year'] ?? 'N/A'],
        ['', 'Kindergarten', $ageProfile['education_below_5_kindergarten_prev_year'] ?? 'N/A', $ageProfile['education_below_5_kindergarten_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_below_5_other_specify'] ?? 'Other', $ageProfile['education_below_5_other_prev_year'] ?? 'N/A', $ageProfile['education_below_5_other_current_year'] ?? 'N/A'],

        // Children between 6 to 10 years
        ['Children between 6 to 10 years', 'Primary school', $ageProfile['education_6_10_primary_school_prev_year'] ?? 'N/A', $ageProfile['education_6_10_primary_school_current_year'] ?? 'N/A'],
        ['', 'Bridge course', $ageProfile['education_6_10_bridge_course_prev_year'] ?? 'N/A', $ageProfile['education_6_10_bridge_course_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_6_10_other_specify'] ?? 'Other', $ageProfile['education_6_10_other_prev_year'] ?? 'N/A', $ageProfile['education_6_10_other_current_year'] ?? 'N/A'],

        // Children between 11 to 15 years
        ['Children between 11 to 15 years', 'Secondary school', $ageProfile['education_11_15_secondary_school_prev_year'] ?? 'N/A', $ageProfile['education_11_15_secondary_school_current_year'] ?? 'N/A'],
        ['', 'High school', $ageProfile['education_11_15_high_school_prev_year'] ?? 'N/A', $ageProfile['education_11_15_high_school_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_11_15_other_specify'] ?? 'Other', $ageProfile['education_11_15_other_prev_year'] ?? 'N/A', $ageProfile['education_11_15_other_current_year'] ?? 'N/A'],

        // 16 and above
        ['16 and above', 'Undergraduate', $ageProfile['education_16_above_undergraduate_prev_year'] ?? 'N/A', $ageProfile['education_16_above_undergraduate_current_year'] ?? 'N/A'],
        ['', 'Technical/Vocational education', $ageProfile['education_16_above_technical_vocational_prev_year'] ?? 'N/A', $ageProfile['education_16_above_technical_vocational_current_year'] ?? 'N/A'],
        ['', $ageProfile['education_16_above_other_specify'] ?? 'Other', $ageProfile['education_16_above_other_prev_year'] ?? 'N/A', $ageProfile['education_16_above_other_current_year'] ?? 'N/A'],
    ];

    // Populate the table
    $currentCategory = '';
    foreach ($dataRows as $row) {
        $table->addRow();
        if ($row[0] !== $currentCategory) {
            $currentCategory = $row[0];
            $table->addCell(4000, ['vMerge' => 'restart'])->addText($row[0]);
        } else {
            $table->addCell(4000, ['vMerge' => 'continue']);
        }
        $table->addCell(3000)->addText($row[1]); // Education
        $table->addCell(2000)->addText($row[2]); // Previous Year
        $table->addCell(2000)->addText($row[3]); // Current Year
    }
}
//Section - Personal Situation - CHILD CARE INSTITUTION

private function addPersonalSituationSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Personal Situation of Children in the Institution", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Table for Personal Situation Data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
        'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER
    ];
    $phpWord->addTableStyle('PersonalSituationTable', $tableStyle);
    $table = $section->addTable('PersonalSituationTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(5000)->addText("Description", ['bold' => true]);
    $table->addCell(3000)->addText("Up to Last Year", ['bold' => true]);
    $table->addCell(3000)->addText("Current Year", ['bold' => true]);

    // Personal Situation Data
    $personalSituation = $project->personal_situation; // Assuming you fetched this relation with the project

    $dataRows = [
        ['Children with parents', $personalSituation->children_with_parents_last_year ?? 'N/A', $personalSituation->children_with_parents_current_year ?? 'N/A'],
        ['Semi-orphans (living with relatives)', $personalSituation->semi_orphans_last_year ?? 'N/A', $personalSituation->semi_orphans_current_year ?? 'N/A'],
        ['Orphans', $personalSituation->orphans_last_year ?? 'N/A', $personalSituation->orphans_current_year ?? 'N/A'],
        ['HIV-infected/affected', $personalSituation->hiv_infected_last_year ?? 'N/A', $personalSituation->hiv_infected_current_year ?? 'N/A'],
        ['Differently-abled children', $personalSituation->differently_abled_last_year ?? 'N/A', $personalSituation->differently_abled_current_year ?? 'N/A'],
        ['Parents in conflict', $personalSituation->parents_in_conflict_last_year ?? 'N/A', $personalSituation->parents_in_conflict_current_year ?? 'N/A'],
        ['Other ailments', $personalSituation->other_ailments_last_year ?? 'N/A', $personalSituation->other_ailments_current_year ?? 'N/A'],
    ];

    foreach ($dataRows as $row) {
        $table->addRow();
        $table->addCell(5000)->addText($row[0]); // Description
        $table->addCell(3000)->addText($row[1]); // Up to Last Year
        $table->addCell(3000)->addText($row[2]); // Current Year
    }

    // Add General Remarks Section
    $section->addTextBreak(1);
    $section->addText("General Remarks", ['bold' => true]);
    $section->addText($personalSituation->general_remarks ?? 'No remarks provided.');
}
//Section - Economic BackgroundS - CHILD CARE INSTITUTION
private function addEconomicBackgroundSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Economic Background of Parents", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Economic Background Data
    $economicBackground = $project->economic_background; // Assuming you fetched this relation with the project

    $dataRows = [
        'Agricultural Labour' => $economicBackground->agricultural_labour_number ?? 'N/A',
        'Marginal Farmers (less than two and half acres)' => $economicBackground->marginal_farmers_number ?? 'N/A',
        'Parents in Self-Employment' => $economicBackground->self_employed_parents_number ?? 'N/A',
        'Parents Working in Informal Sector' => $economicBackground->informal_sector_parents_number ?? 'N/A',
        'Any Other' => $economicBackground->any_other_number ?? 'N/A',
    ];

    // Add data in a table format
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('EconomicBackgroundTable', $tableStyle);
    $table = $section->addTable('EconomicBackgroundTable');

    // Add Table Header
    $table->addRow();
    $table->addCell(7000)->addText("Description", ['bold' => true]);
    $table->addCell(3000)->addText("Value", ['bold' => true]);

    // Add Data Rows
    foreach ($dataRows as $label => $value) {
        $table->addRow();
        $table->addCell(7000)->addText($label);
        $table->addCell(3000)->addText($value);
    }

    // Add General Remarks
    $section->addTextBreak(1);
    $section->addText("General Remarks", ['bold' => true]);
    $section->addText($economicBackground->general_remarks ?? 'No remarks provided.');
}
//Section - Achievements - CHILD CARE INSTITUTION
private function addAchievementsSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header
    $section->addText("Achievements", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    $achievements = $project->achievements; // Assuming this relation is fetched

    // Academic Achievements
    $section->addText("Academic Achievements:", ['bold' => true]);
    if (!empty($achievements->academic_achievements)) {
        foreach ($achievements->academic_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No academic achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
    $section->addTextBreak(1);

    // Sports Achievements
    $section->addText("Sports Achievements:", ['bold' => true]);
    if (!empty($achievements->sport_achievements)) {
        foreach ($achievements->sport_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No sports achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
    $section->addTextBreak(1);

    // Other Achievements
    $section->addText("Other Achievements:", ['bold' => true]);
    if (!empty($achievements->other_achievements)) {
        foreach ($achievements->other_achievements as $achievement) {
            $section->addText("- $achievement");
        }
    } else {
        $section->addText("No other achievements recorded.", ['italic' => true, 'color' => '6c757d']);
    }
}
// Section - Present Situation - CHILD CARE INSTITUTION
private function addPresentSituationSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Add Header for Present Situation
    $section->addText("Present Situation of the Inmates", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Internal Challenges
    $section->addText("Internal Challenges Faced from Inmates:", ['bold' => true]);
    $section->addText($project->present_situation->internal_challenges ?? 'No internal challenges recorded.');
    $section->addTextBreak(1);

    // Add External Challenges
    $section->addText("External Challenges / Present Difficulties:", ['bold' => true]);
    $section->addText($project->present_situation->external_challenges ?? 'No external challenges recorded.');
    $section->addTextBreak(2);

    // Add Header for Area of Focus
    $section->addText("Area of Focus for the Current Year", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add Main Focus Areas
    $section->addText("Main Focus Areas:", ['bold' => true]);
    $section->addText($project->present_situation->area_of_focus ?? 'No focus areas specified.');
    $section->addTextBreak(1);
}


// Residential Skill Training Specific Functions
private function addRSTSections(PhpWord $phpWord, $project)
{
    $this->addInstitutionInfoSection($phpWord, $project);
    $this->addBeneficiariesAreaSection($phpWord, $project);
    $this->addTargetGroupSection($phpWord, $project);
    $this->addTargetGroupAnnexureSection($phpWord, $project);
    $this->addGeographicalAreaSection($phpWord, $project);
}
// Section - Institution Info - Residential Skill Training
private function addInstitutionInfoSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the header
    $section->addText("Institution Information", ['bold' => true, 'size' => 14]);

    // Fetch RST Institution Info (assuming the relation or accessor is $project->RSTInstitutionInfo)
    $institutionInfo = $project->RSTInstitutionInfo;

    // Define the info labels and corresponding values
    $infoItems = [
        'Year the Training Center was set up:' => $institutionInfo?->year_setup ?? 'No data available.',
        'Total Students Trained Till Date:' => $institutionInfo?->total_students_trained ?? 'No data available.',
        'Beneficiaries Trained in the Last Year:' => $institutionInfo?->beneficiaries_last_year ?? 'No data available.',
        'Outcome/Impact of the Training:' => $institutionInfo?->training_outcome ?? 'No data available.',
    ];

    // Add the info grid (label-value pairs)
    foreach ($infoItems as $label => $value) {
        $section->addText($label, ['bold' => true]);
        $section->addText($value);
        $section->addTextBreak(0.5); // Add spacing between entries
    }

    $section->addTextBreak(1); // Add extra space after the section
}
// Section - Beneficiaries Area - Residential Skill Training
private function addBeneficiariesAreaSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Project Area", ['bold' => true, 'size' => 14]);

    // Add a table to represent the data
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 50
    ];
    $firstRowStyle = [
        'bgColor' => 'cccccc', // Light gray background
        'bold' => true
    ];

    // Register table style
    $phpWord->addTableStyle('BeneficiariesAreaTable', $tableStyle);

    // Create table
    $table = $section->addTable('BeneficiariesAreaTable');

    // Add header row
    $table->addRow();
    $table->addCell(3000)->addText("Project Area", $firstRowStyle);
    $table->addCell(3000)->addText("Category of Beneficiary", $firstRowStyle);
    $table->addCell(2000)->addText("Direct Beneficiaries", $firstRowStyle);
    $table->addCell(2000)->addText("Indirect Beneficiaries", $firstRowStyle);

    // Check if data exists
    if ($project->beneficiaries_area && $project->beneficiaries_area->isNotEmpty()) {
        // Loop through the beneficiaries area
        foreach ($project->beneficiaries_area as $area) {
            $table->addRow();
            $table->addCell(3000)->addText($area->project_area ?? 'N/A');
            $table->addCell(3000)->addText($area->category_beneficiary ?? 'N/A');
            $table->addCell(2000)->addText($area->direct_beneficiaries ?? 'N/A');
            $table->addCell(2000)->addText($area->indirect_beneficiaries ?? 'N/A');
        }
    } else {
        // Add a row indicating no data
        $table->addRow();
        $table->addCell(0, ['gridSpan' => 4])->addText("No project area data recorded.", ['italic' => true], ['alignment' => 'center']);
    }

    $section->addTextBreak(1); // Add spacing after the table
}
// Section - Target Group - Residential Skill Training
private function addTargetGroupSection(PhpWord $phpWord, $project)
{
    // Add a new section to the Word document
    $section = $phpWord->addSection();
    $section->addText("Target Group", ['bold' => true, 'size' => 14]);

    // Check if the collection has data
    if ($project->RSTTargetGroup && $project->RSTTargetGroup->isNotEmpty()) {
        // Add table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('TargetGroupTableStyle', $tableStyle);

        // Create a table
        $table = $section->addTable('TargetGroupTableStyle');

        // Add table header row
        $table->addRow();
        $table->addCell(1000)->addText("S.No.", ['bold' => true]);
        $table->addCell(3000)->addText("Number of Beneficiaries", ['bold' => true]);
        $table->addCell(6000)->addText("Description of Problems", ['bold' => true]);

        // Loop through all rows in RSTTargetGroup
        foreach ($project->RSTTargetGroup as $index => $targetGroup) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($targetGroup->tg_no_of_beneficiaries ?? 'N/A');
            $table->addCell(6000)->addText($targetGroup->beneficiaries_description_problems ?? 'N/A');
        }
    } else {
        // If no data is available
        $section->addText("No target group data available for this project.", ['italic' => true]);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}

// Section - Target Group Annexure - Residential Skill Training
private function addTargetGroupAnnexureSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();
    $section->addText("Target Group Annexure", ['bold' => true, 'size' => 14]);

    // Check if annexures are available
    $RSTTargetGroupAnnexure = $project->target_group_annexure ?? collect();
    if ($RSTTargetGroupAnnexure->isEmpty()) {
        $section->addText("No data available for Target Group Annexure.", ['italic' => true]);
        return;
    }

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 80
    ];
    $firstRowStyle = [
        'bgColor' => 'f2f2f2', // Light gray background for header
        'bold' => true
    ];
    $phpWord->addTableStyle('TargetGroupAnnexureTable', $tableStyle);

    // Create table
    $table = $section->addTable('TargetGroupAnnexureTable');

    // Add table headers
    $table->addRow();
    $table->addCell(2000)->addText("Name", $firstRowStyle);
    $table->addCell(2000)->addText("Religion", $firstRowStyle);
    $table->addCell(2000)->addText("Caste", $firstRowStyle);
    $table->addCell(3000)->addText("Education Background", $firstRowStyle);
    $table->addCell(3000)->addText("Family Situation", $firstRowStyle);
    $table->addCell(3000)->addText("Paragraph", $firstRowStyle);

    // Populate table rows
    foreach ($RSTTargetGroupAnnexure as $annexure) {
        $table->addRow();
        $table->addCell(2000)->addText($annexure->rst_name ?? 'N/A');
        $table->addCell(2000)->addText($annexure->rst_religion ?? 'N/A');
        $table->addCell(2000)->addText($annexure->rst_caste ?? 'N/A');
        $table->addCell(3000)->addText($annexure->rst_education_background ?? 'N/A');
        $table->addCell(3000)->addText($annexure->rst_family_situation ?? 'N/A');
        $table->addCell(3000)->addText($annexure->rst_paragraph ?? 'N/A');
    }

    // Add spacing after table
    $section->addTextBreak(1);
}
// Section - Geographical Area - Residential Skill Training
private function addGeographicalAreaSection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText("Geographical Area of Beneficiaries", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add some space after the title

    // Check if there is data to display
    if ($project->geographical_area && $project->geographical_area->count() > 0) {
        // Define a table style
        $tableStyle = [
            'borderSize' => 6, // 0.75pt
            'borderColor' => '999999', // Light gray
            'cellMargin' => 80 // Padding inside cells
        ];
        $phpWord->addTableStyle('GeographicalTableStyle', $tableStyle);

        // Add the table
        $table = $section->addTable('GeographicalTableStyle');

        // Add the header row
        $table->addRow();
        $table->addCell(2000)->addText("Mandal", ['bold' => true]);
        $table->addCell(3000)->addText("Villages", ['bold' => true]);
        $table->addCell(3000)->addText("Town", ['bold' => true]);
        $table->addCell(3000)->addText("No of Beneficiaries", ['bold' => true]);

        // Loop through the geographical areas
        foreach ($project->geographical_area as $area) {
            $table->addRow();
            $table->addCell(2000)->addText($area->mandal ?? 'N/A');
            $table->addCell(3000)->addText($area->villages ?? 'N/A');
            $table->addCell(3000)->addText($area->town ?? 'N/A');
            $table->addCell(3000)->addText($area->no_of_beneficiaries ?? 'N/A');
        }
    } else {
        // Add a message for no data
        $section->addText("No geographical area data recorded.", ['italic' => true, 'size' => 12]);
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}



// Rural Urban Tribal Specific Functions
private function addEduRUTSections(PhpWord $phpWord, $project)
{
    $this->addEduRUTBasicInfoSection($phpWord, $project);
    $this->addEduRUTTargetGroupSection($phpWord, $project);
}

// Section - RUT Basic Info - Rural Urban Tribal
private function addEduRUTBasicInfoSection(PhpWord $phpWord, $basicInfo)
{
    $section = $phpWord->addSection();
    $section->addText("Basic Information of Project's Operational Area", ['bold' => true, 'size' => 14]);

    if ($basicInfo) {
        // Add a table for structured layout
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);

        // Institution Type
        $table->addRow();
        $table->addCell(5000)->addText("Institution Type:");
        $table->addCell(5000)->addText($basicInfo->institution_type ?? 'N/A');

        // Group Type
        $table->addRow();
        $table->addCell(5000)->addText("Group Type:");
        $table->addCell(5000)->addText($basicInfo->group_type ?? 'N/A');

        // Category
        $table->addRow();
        $table->addCell(5000)->addText("Category:");
        $table->addCell(5000)->addText($basicInfo->category ?? 'N/A');

        // Project Location
        $table->addRow();
        $table->addCell(5000)->addText("Project Location:");
        $table->addCell(5000)->addText($basicInfo->project_location ?? 'N/A');

        // Sisters' Work
        $table->addRow();
        $table->addCell(5000)->addText("Work of Sisters in the Project Area:");
        $table->addCell(5000)->addText($basicInfo->sisters_work ?? 'N/A');

        // Socio-Economic and Cultural Conditions
        $table->addRow();
        $table->addCell(5000)->addText("Socio-Economic and Cultural Conditions:");
        $table->addCell(5000)->addText($basicInfo->conditions ?? 'N/A');

        // Problems
        $table->addRow();
        $table->addCell(5000)->addText("Problems Identified and Their Consequences:");
        $table->addCell(5000)->addText($basicInfo->problems ?? 'N/A');

        // Need
        $table->addRow();
        $table->addCell(5000)->addText("Need of the Project:");
        $table->addCell(5000)->addText($basicInfo->need ?? 'N/A');

        // Criteria
        $table->addRow();
        $table->addCell(5000)->addText("Criteria for Selecting the Target Group:");
        $table->addCell(5000)->addText($basicInfo->criteria ?? 'N/A');
    } else {
        // If no basic info is available
        $section->addText("No basic information available for this project.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - RUT Target Group - Rural Urban Tribal
private function addEduRUTTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Edu-Rural-Urban-Tribal - Target Group", ['bold' => true, 'size' => 14]);

    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('TargetGroupTableStyle', $tableStyle);

    $table = $section->addTable('TargetGroupTableStyle');

    // Add header row
    $table->addRow();
    $table->addCell(500)->addText("S.No.", ['bold' => true]);
    $table->addCell(2000)->addText("Beneficiary Name", ['bold' => true]);
    $table->addCell(1500)->addText("Caste", ['bold' => true]);
    $table->addCell(2000)->addText("Name of Institution", ['bold' => true]);
    $table->addCell(1500)->addText("Class / Standard", ['bold' => true]);
    $table->addCell(1500)->addText("Total Tuition Fee", ['bold' => true]);
    $table->addCell(2000)->addText("Eligibility of Scholarship", ['bold' => true]);
    $table->addCell(1500)->addText("Expected Amount", ['bold' => true]);
    $table->addCell(2000)->addText("Contribution from Family", ['bold' => true]);

    // Add rows dynamically based on the data
    if ($project->target_groups && $project->target_groups->count() > 0) {
        foreach ($project->target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1); // S.No.
            $table->addCell(2000)->addText($group->beneficiary_name ?? 'N/A');
            $table->addCell(1500)->addText($group->caste ?? 'N/A');
            $table->addCell(2000)->addText($group->institution_name ?? 'N/A');
            $table->addCell(1500)->addText($group->class_standard ?? 'N/A');
            $table->addCell(1500)->addText($group->total_tuition_fee ? 'Rs. ' . number_format($group->total_tuition_fee, 2) : 'N/A');
            $table->addCell(2000)->addText($group->eligibility_scholarship ? 'Yes' : 'No');
            $table->addCell(1500)->addText($group->expected_amount ? 'Rs. ' . number_format($group->expected_amount, 2) : 'N/A');
            $table->addCell(2000)->addText($group->contribution_from_family ? 'Rs. ' . number_format($group->contribution_from_family, 2) : 'N/A');
        }
    } else {
        // No data available
        $table->addRow();
        $table->addCell(9000, ['gridSpan' => 9])->addText("No target group data available.", ['italic' => true], ['align' => 'center']);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - RUT Annexed Target Group - Rural Urban Tribal
private function addEduRUTAnnexedTargetGroupSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Annexed Target Group", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1); // Add spacing

    // Define the table style
    $tableStyle = [
        'borderSize' => 6, // 1pt = 8 twips, 6 twips ≈ 0.75pt
        'borderColor' => '000000',
        'cellMargin' => 50, // Padding inside cells
    ];
    $phpWord->addTableStyle('AnnexedTargetGroupTable', $tableStyle);
    $table = $section->addTable('AnnexedTargetGroupTable');

    // Add header row
    $table->addRow();
    $table->addCell(500)->addText("S.No.", ['bold' => true]);
    $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
    $table->addCell(5000)->addText("Family Background", ['bold' => true]);
    $table->addCell(3000)->addText("Need of Support", ['bold' => true]);

    // Check if data exists
    if ($project->annexed_target_groups && $project->annexed_target_groups->count() > 0) {
        foreach ($project->annexed_target_groups as $index => $group) {
            $table->addRow();
            $table->addCell(500)->addText($index + 1);
            $table->addCell(3000)->addText($group->beneficiary_name ?? 'N/A');
            $table->addCell(5000)->addText($group->family_background ?? 'N/A');
            $table->addCell(3000)->addText($group->need_of_support ?? 'N/A');
        }
    } else {
        // No data available
        $table->addRow();
        $table->addCell(11500, ['gridSpan' => 4])->addText("No Annexed Target Group data available.");
    }

    $section->addTextBreak(1); // Add spacing after the table
}


// Institutional Ongoing Group Educational proposal Specific Functions
private function addIGESpecificSections(PhpWord $phpWord, $project)
{
    $this->addIGEInstitutionInfoSection($phpWord, $project);
    $this->addIGEBeneficiariesSupportedSection($phpWord, $project);
    $this->addIGEOngoingBeneficiariesSection($phpWord, $project);
    $this->addIGENewBeneficiariesSection($phpWord, $project);
    $this->addIGEBudgetSection($phpWord, $project);
    $this->addIGEDevelopmentMonitoringSection($phpWord, $project);
}

// Section - IGE Institution Info - Institutional Ongoing Group Educational proposal
private function addIGEInstitutionInfoSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Institution Information", ['bold' => true, 'size' => 14]);

    // Create a table for institution info
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('InstitutionInfoTable', $tableStyle);
    $table = $section->addTable('InstitutionInfoTable');

    // Add the headers
    $table->addRow();
    $table->addCell(5000)->addText("Field", ['bold' => true]);
    $table->addCell(5000)->addText("Details", ['bold' => true]);

    // Add the rows with data
    $table->addRow();
    $table->addCell(5000)->addText("Institutional Type");
    $table->addCell(5000)->addText($project->institution_info?->institutional_type ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Age Group");
    $table->addCell(5000)->addText($project->institution_info?->age_group ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Number of Beneficiaries (Previous Years)");
    $table->addCell(5000)->addText($project->institution_info?->previous_year_beneficiaries ?? 'N/A');

    $table->addRow();
    $table->addCell(5000)->addText("Outcome/Impact");
    $table->addCell(5000)->addText($project->institution_info?->outcome_impact ?? 'No information provided.');

    // Add spacing after the section
    $section->addTextBreak(1);
}
// Section - IGE Beneficiaries Supported - Institutional Ongoing Group Educational proposal
private function addIGEBeneficiariesSupportedSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Number of Beneficiaries Supported this Year", ['bold' => true, 'size' => 14]);

    $beneficiariesSupported = $project->beneficiaries_supported;

    if ($beneficiariesSupported && $beneficiariesSupported->count()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('BeneficiariesTable', $tableStyle);

        // Add table
        $table = $section->addTable('BeneficiariesTable');

        // Add table headers
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Class", ['bold' => true]);
        $table->addCell(3000)->addText("Total Number", ['bold' => true]);

        // Add beneficiaries data
        foreach ($beneficiariesSupported as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->class);
            $table->addCell(3000)->addText($beneficiary->total_number);
        }
    } else {
        // Add fallback text if no data is available
        $section->addText("No beneficiaries supported data available.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - IGE Ongoing Beneficiaries - Institutional Ongoing Group Educational proposal
private function addIGEOngoingBeneficiariesSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Ongoing Beneficiaries", ['bold' => true, 'size' => 14]);

    if ($project->ongoing_beneficiaries && $project->ongoing_beneficiaries->isNotEmpty()) {
        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('OngoingBeneficiariesTable', $tableStyle);

        // Add table to the section
        $table = $section->addTable('OngoingBeneficiariesTable');

        // Add table header row
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
        $table->addCell(2000)->addText("Caste", ['bold' => true]);
        $table->addCell(3000)->addText("Address", ['bold' => true]);
        $table->addCell(3000)->addText("Present Group / Year of Study", ['bold' => true]);
        $table->addCell(3000)->addText("Performance Details", ['bold' => true]);

        // Add data rows
        foreach ($project->ongoing_beneficiaries as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->obeneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText($beneficiary->ocaste ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->oaddress ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->ocurrent_group_year_of_study ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->operformance_details ?? 'N/A');
        }
    } else {
        // Add a fallback text if no data is found
        $section->addText("No ongoing beneficiaries found for this project.");
    }

    $section->addTextBreak(1); // Add spacing after the section
}
// Section - IGE New Beneficiaries - Institutional Ongoing Group Educational proposal
private function addIGENewBeneficiariesSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("New Beneficiaries", ['bold' => true, 'size' => 14]);

    // Check if there are new beneficiaries
    if ($project->new_beneficiaries && $project->new_beneficiaries->isNotEmpty()) {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('NewBeneficiariesTable', $tableStyle);
        $table = $section->addTable('NewBeneficiariesTable');

        // Add table header
        $table->addRow();
        $table->addCell(1000)->addText("S.No", ['bold' => true]);
        $table->addCell(3000)->addText("Beneficiary Name", ['bold' => true]);
        $table->addCell(2000)->addText("Caste", ['bold' => true]);
        $table->addCell(3000)->addText("Address", ['bold' => true]);
        $table->addCell(3000)->addText("Group / Year of Study", ['bold' => true]);
        $table->addCell(4000)->addText("Family Background and Need of Support", ['bold' => true]);

        // Add beneficiary data
        foreach ($project->new_beneficiaries as $index => $beneficiary) {
            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(3000)->addText($beneficiary->beneficiary_name ?? 'N/A');
            $table->addCell(2000)->addText($beneficiary->caste ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->address ?? 'N/A');
            $table->addCell(3000)->addText($beneficiary->group_year_of_study ?? 'N/A');
            $table->addCell(4000)->addText($beneficiary->family_background_need ?? 'N/A');
        }
    } else {
        // Add message if no beneficiaries are recorded
        $section->addText("No new beneficiaries recorded.", ['italic' => true]);
    }

    $section->addTextBreak(1); // Add space after the section
}
// Section - IGE Budget - Institutional Ongoing Group Educational proposal
private function addIGEBudgetSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Budget for Current Year", ['bold' => true, 'size' => 14]);

    if ($project->budget && $project->budget->isNotEmpty()) {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ];

        $phpWord->addTableStyle('BudgetTable', $tableStyle);
        $table = $section->addTable('BudgetTable');

        // Add table header
        $table->addRow();
        $headers = [
            "S.No", "Name", "Study Proposed to Be", "College Fees",
            "Hostel Fees", "Total Amount", "Scholarship Eligibility",
            "Family Contribution", "Amount Requested"
        ];

        foreach ($headers as $header) {
            $table->addCell(2000)->addText($header, ['bold' => true]);
        }

        // Initialize totals
        $totalCollegeFees = 0;
        $totalHostelFees = 0;
        $totalAmount = 0;
        $totalScholarshipEligibility = 0;
        $totalFamilyContribution = 0;
        $totalAmountRequested = 0;

        // Add table rows
        foreach ($project->budget as $index => $budget) {
            $collegeFees = $budget->college_fees ?? 0;
            $hostelFees = $budget->hostel_fees ?? 0;
            $totalRowAmount = $budget->total_amount ?? 0;
            $scholarshipEligibility = $budget->scholarship_eligibility ?? 0;
            $familyContribution = $budget->family_contribution ?? 0;
            $amountRequested = $budget->amount_requested ?? 0;

            $totalCollegeFees += $collegeFees;
            $totalHostelFees += $hostelFees;
            $totalAmount += $totalRowAmount;
            $totalScholarshipEligibility += $scholarshipEligibility;
            $totalFamilyContribution += $familyContribution;
            $totalAmountRequested += $amountRequested;

            $table->addRow();
            $table->addCell(1000)->addText($index + 1);
            $table->addCell(2000)->addText($budget->name ?? 'N/A');
            $table->addCell(2000)->addText($budget->study_proposed ?? 'N/A');
            $table->addCell(2000)->addText(number_format($collegeFees, 2));
            $table->addCell(2000)->addText(number_format($hostelFees, 2));
            $table->addCell(2000)->addText(number_format($totalRowAmount, 2));
            $table->addCell(2000)->addText(number_format($scholarshipEligibility, 2));
            $table->addCell(2000)->addText(number_format($familyContribution, 2));
            $table->addCell(2000)->addText(number_format($amountRequested, 2));
        }

        // Add totals row
        $table->addRow();
        $table->addCell(3000, ['gridSpan' => 3])->addText("Totals", ['bold' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $table->addCell(2000)->addText(number_format($totalCollegeFees, 2), ['bold' => true]);
        $table->addCell(2000)->addText(number_format($totalHostelFees, 2), ['bold' => true]);
        $table->addCell(2000)->addText(number_format($totalAmount, 2), ['bold' => true]);
        $table->addCell(2000)->addText(number_format($totalScholarshipEligibility, 2), ['bold' => true]);
        $table->addCell(2000)->addText(number_format($totalFamilyContribution, 2), ['bold' => true]);
        $table->addCell(2000)->addText(number_format($totalAmountRequested, 2), ['bold' => true]);
    } else {
        $section->addText("No budget data available for this project.", ['italic' => true]);
    }
}
// Section - IGE Development Monitoring - Institutional Ongoing Group Educational proposal
private function addIGEDevelopmentMonitoringSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    $section->addText("Development Monitoring", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    $developmentMonitoring = $project->development_monitoring;

    // Add Proposed Activities
    $section->addText("Proposed Activities for Overall Development:", ['bold' => true]);
    $section->addText($developmentMonitoring?->proposed_activities ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Monitoring Methods
    $section->addText("Methods of Monitoring the Beneficiaries' Growth:", ['bold' => true]);
    $section->addText($developmentMonitoring?->monitoring_methods ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Evaluation Process and Responsibility
    $section->addText("Process of Evaluation and Responsibility:", ['bold' => true]);
    $section->addText($developmentMonitoring?->evaluation_process ?? 'No data provided.');
    $section->addTextBreak(1);

    // Add Conclusion
    $section->addText("Conclusion:", ['bold' => true]);
    $section->addText($developmentMonitoring?->conclusion ?? 'No data provided.');
    $section->addTextBreak(1);
}



// LDP - Livelihood Development Project Specific Functions
private function addLDPSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Livelihood Development Project Details", ['bold' => true, 'size' => 16]);

    // Call separate methods for each partial
    $this->addNeedAnalysisSection($phpWord, $project);
    $this->addLDPTargetGroupSection($phpWord, $project);
    $this->addInterventionLogicSection($phpWord, $project);
}
// Section - Need Analysis - LDP - Livelihood Development Project
private function addNeedAnalysisSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Need Analysis", ['bold' => true, 'size' => 16]);

    // Check if need analysis data exists
    if ($project->needAnalysis && $project->needAnalysis->document_path) {
        $documentName = basename($project->needAnalysis->document_path); // Extract document name
        $section->addText("Status: Document Uploaded", ['bold' => true]);
        $section->addText("Document Name: {$documentName}", ['size' => 12]);
    } else {
        $section->addText("Status: No document uploaded yet.", ['bold' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}
// Section - Target Group - LDP - Livelihood Development Project
private function addLDPTargetGroupSection(PhpWord $phpWord, $project)
{
    // Add a new section
    $section = $phpWord->addSection();

    // Add a title for the section
    $section->addText(
        "Annexed Target Group: Livelihood Development Projects",
        ['bold' => true, 'size' => 14],
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
    );

    // Add spacing before the table
    $section->addTextBreak(1);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 80,
    ];
    $firstRowStyle = ['bgColor' => '202BA3'];
    $phpWord->addTableStyle('TargetGroupTable', $tableStyle, $firstRowStyle);

    // Create table
    $table = $section->addTable('TargetGroupTable');

    // Add table header row
    $table->addRow();
    $table->addCell(1000, ['bgColor' => '202BA3'])->addText(
        "S.No.",
        ['bold' => true, 'color' => 'FFFFFF'],
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Beneficiary Name",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Family Situation",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(3000, ['bgColor' => '202BA3'])->addText(
        "Nature of Livelihood",
        ['bold' => true, 'color' => 'FFFFFF']
    );
    $table->addCell(2000, ['bgColor' => '202BA3'])->addText(
        "Amount Requested",
        ['bold' => true, 'color' => 'FFFFFF']
    );

    // Add data rows
    if (!empty($project->targetGroups) && $project->targetGroups->isNotEmpty()) {
        foreach ($project->targetGroups as $index => $targetGroup) {
            $table->addRow();
            $table->addCell(1000)->addText(($index + 1), null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $table->addCell(3000)->addText($targetGroup->L_beneficiary_name ?? 'N/A');
            $table->addCell(3000)->addText($targetGroup->L_family_situation ?? 'N/A');
            $table->addCell(3000)->addText($targetGroup->L_nature_of_livelihood ?? 'N/A');
            $table->addCell(2000)->addText($targetGroup->L_amount_requested ? 'Rs. ' . number_format($targetGroup->L_amount_requested, 2) : 'N/A');
        }
    } else {
        // Add a row indicating no data is available
        $table->addRow();
        $table->addCell(0, ['gridSpan' => 5])->addText(
            "No target groups available.",
            ['italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
    }

    // Add spacing after the table
    $section->addTextBreak(1);
}
// Section - Intervention Logic - LDP - Livelihood Development Project
private function addInterventionLogicSection(PhpWord $phpWord, $project)
{
    // Retrieve intervention logic data
    $interventionLogic = $project->interventionLogic; // Assuming this is a relationship or attribute on the project

    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the header for the section
    $section->addText("Intervention Logic", ['bold' => true, 'size' => 16]);
    $section->addText("Description of how the project's interventions alleviate the existing problems.", ['italic' => true, 'size' => 12]);
    $section->addTextBreak(1);

    // Add the description
    if ($interventionLogic && $interventionLogic->intervention_description) {
        $section->addText("Description:", ['bold' => true]);
        $section->addText($interventionLogic->intervention_description);
    } else {
        $section->addText("No intervention logic provided.", ['italic' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}


// common sections
// Logical Framework
private function addLogicalFrameworkSection(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();

    // Header for Logical Framework
    $section->addText("Logical Framework", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Loop through each objective
    foreach ($project->objectives as $objective) {
        // Objective Header
        $section->addText("Objective: {$objective->objective}", ['bold' => true, 'size' => 14]);
        $section->addTextBreak(0.5);

        // Results / Outcomes
        $section->addText("Results / Outcomes:", ['bold' => true, 'size' => 12]);
        foreach ($objective->results as $result) {
            $section->addText("- {$result->result}");
        }
        $section->addTextBreak(0.5);

        // Risks Section
        if ($objective->risks && $objective->risks->isNotEmpty()) {
            $section->addText("Risks:", ['bold' => true, 'size' => 12]);
            foreach ($objective->risks as $risk) {
                $section->addText("- {$risk->risk}");
            }
        }
        $section->addTextBreak(0.5);

        // Activities and Means of Verification Table
        $section->addText("Activities and Means of Verification:", ['bold' => true, 'size' => 12]);
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('ActivitiesTable', $tableStyle);
        $table = $section->addTable('ActivitiesTable');

        // Table Header
        $table->addRow();
        $table->addCell(5000)->addText("Activities", ['bold' => true]);
        $table->addCell(5000)->addText("Means of Verification", ['bold' => true]);

        foreach ($objective->activities as $activity) {
            $table->addRow();
            $table->addCell(5000)->addText($activity->activity);
            $table->addCell(5000)->addText($activity->verification);
        }
        $section->addTextBreak(1);

        // Time Frame Table
        $section->addText("Time Frame for Activities:", ['bold' => true, 'size' => 12]);
        $table = $section->addTable('ActivitiesTable');

        // Add table header for months
        $table->addRow();
        $table->addCell(5000)->addText("Activities", ['bold' => true]);
        foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month) {
            $table->addCell(1000)->addText($month, ['bold' => true]);
        }

        // Add activities with time frames
        foreach ($objective->activities as $activity) {
            $table->addRow();
            $table->addCell(5000)->addText($activity->activity);

            // Loop through months and add checkmark if active
            foreach (range(1, 12) as $month) {
                $isChecked = $activity->timeframes->contains(function ($timeframe) use ($month) {
                    return $timeframe->month == $month && $timeframe->is_active == 1;
                });
                $table->addCell(1000)->addText($isChecked ? '✔' : '');
            }
        }

        $section->addTextBreak(1);
    }
}
// Sustainability
private function addSustainabilitySection(PhpWord $phpWord, $project)
{
    // Create a new section in the Word document
    $section = $phpWord->addSection();

    // Add the section header
    $section->addText("Project Sustainability, Monitoring, and Methodologies", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    if ($project->sustainabilities->isEmpty()) {
        $section->addText("No sustainability information is available for this project.", ['italic' => true]);
        return;
    }

    // Define table style for grid layout
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('SustainabilityTable', $tableStyle);

    // Add the sustainability details in a table format
    foreach ($project->sustainabilities as $sustainability) {
        $table = $section->addTable('SustainabilityTable');

        // Sustainability of the Project
        $table->addRow();
        $table->addCell(5000)->addText("Sustainability of the Project:", ['bold' => true]);
        $table->addCell(7000)->addText($sustainability->sustainability ?? 'N/A');

        // Monitoring Process
        $table->addRow();
        $table->addCell(5000)->addText("Monitoring Process of the Project:", ['bold' => true]);
        $table->addCell(7000)->addText($sustainability->monitoring_process ?? 'N/A');

        // Reporting Methodology
        $table->addRow();
        $table->addCell(5000)->addText("Methodology of Reporting:", ['bold' => true]);
        $table->addCell(7000)->addText($sustainability->reporting_methodology ?? 'N/A');

        // Evaluation Methodology
        $table->addRow();
        $table->addCell(5000)->addText("Methodology of Evaluation:", ['bold' => true]);
        $table->addCell(7000)->addText($sustainability->evaluation_methodology ?? 'N/A');

        // Add spacing after each sustainability entry
        $section->addTextBreak(1);
    }
}
// Budget
private function addBudgetSection(PhpWord $phpWord, $project)
{
    // Create a new section for the budget details
    $section = $phpWord->addSection();
    $section->addText("Budget", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Group budgets by phase
    $groupedBudgets = $project->budgets->groupBy('phase');

    foreach ($groupedBudgets as $phase => $budgets) {
        // Add Phase Header
        $section->addText("Phase {$phase}", ['bold' => true, 'size' => 14]);
        $section->addText("Amount Sanctioned in Phase {$phase}: Rs. " . number_format($budgets->sum('this_phase'), 2));
        $section->addTextBreak(1);

        // Define table style
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('BudgetTable', $tableStyle);

        // Create the table
        $table = $section->addTable('BudgetTable');

        // Add table header
        $table->addRow();
        $table->addCell(4000)->addText("Particular", ['bold' => true]);
        $table->addCell(1500)->addText("Costs", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Multiplier", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Duration", ['bold' => true]);
        $table->addCell(1500)->addText("Rate Increase (next phase)", ['bold' => true]);
        $table->addCell(1500)->addText("This Phase (Auto)", ['bold' => true]);
        $table->addCell(1500)->addText("Next Phase (Auto)", ['bold' => true]);

        // Add table rows
        foreach ($budgets as $budget) {
            $table->addRow();
            $table->addCell(4000)->addText($budget->particular);
            $table->addCell(1500)->addText(number_format($budget->rate_quantity, 2));
            $table->addCell(1500)->addText(number_format($budget->rate_multiplier, 2));
            $table->addCell(1500)->addText(number_format($budget->rate_duration, 2));
            $table->addCell(1500)->addText(number_format($budget->rate_increase, 2));
            $table->addCell(1500)->addText(number_format($budget->this_phase, 2));
            $table->addCell(1500)->addText(number_format($budget->next_phase, 2));
        }

        // Add table footer for totals
        $table->addRow();
        $table->addCell(4000)->addText("Total", ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('rate_quantity'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('rate_multiplier'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('rate_duration'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('rate_increase'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('this_phase'), 2), ['bold' => true]);
        $table->addCell(1500)->addText(number_format($budgets->sum('next_phase'), 2), ['bold' => true]);

        // Add spacing between phases
        $section->addTextBreak(1);
    }
}
// common Attachements of group projects
private function addAttachmentsSection(PhpWord $phpWord, $project)
{
    // Add a new section in the Word document
    $section = $phpWord->addSection();

    // Add the section header
    $section->addText("Attachments", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Check if attachments exist
    if ($project->attachments->isEmpty()) {
        $section->addText("No attachments available.", ['italic' => true]);
        return;
    }

    // Define table style for better presentation
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80
    ];
    $phpWord->addTableStyle('AttachmentsTable', $tableStyle);
    $table = $section->addTable('AttachmentsTable');

    // Add table headers
    $table->addRow();
    $table->addCell(5000)->addText("Attachment Name", ['bold' => true]);
    $table->addCell(5000)->addText("Description", ['bold' => true]);

    // Loop through attachments and add rows to the table
    foreach ($project->attachments as $attachment) {
        $table->addRow();
        $table->addCell(5000)->addText($attachment->file_name);
        $table->addCell(5000)->addText($attachment->description ?? 'No description provided.');
    }

    // Add footer note
    $section->addTextBreak(1);
    $section->addText("(Click on the attachment name in the system to download it)", ['italic' => true, 'size' => 10]);
}

//  CIC - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER Specific Functions
// Section - Basic Inforrmation - CIC - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER
private function addCICSections(PhpWord $phpWord, $project)
{
    // Retrieve CIC-specific data
    $cicBasicInfo = $project->cicBasicInfo; // Assuming this relationship or attribute exists

    // Create a new section for CIC
    $section = $phpWord->addSection();

    // Add header
    $section->addText("PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER", ['bold' => true, 'size' => 16]);
    $section->addText("Basic Information", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Check if CIC basic info exists
    if ($cicBasicInfo) {
        // Add table for structured layout
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80
        ];
        $phpWord->addTableStyle('CICBasicInfoTable', $tableStyle);
        $table = $section->addTable('CICBasicInfoTable');

        // Add rows for CIC details
        $table->addRow();
        $table->addCell(4000)->addText("Center Name:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->center_name ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Address:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->address ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Contact Person:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->contact_person ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Phone Number:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->phone_number ?? 'N/A');

        $table->addRow();
        $table->addCell(4000)->addText("Email Address:", ['bold' => true]);
        $table->addCell(8000)->addText($cicBasicInfo->email_address ?? 'N/A');
    } else {
        // No CIC-specific data available
        $section->addText("No CIC basic information available.", ['italic' => true]);
    }

    // Add spacing after the section
    $section->addTextBreak(1);
}

private function addSignatureAndApprovalSections(PhpWord $phpWord, $project, $projectRoles)
{
    // Add a new section for signatures and approval
    $section = $phpWord->addSection();

    // Header for Signatures
    $section->addText("Signatures", ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    // Define table style
    $tableStyle = [
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 80,
    ];
    $phpWord->addTableStyle('SignaturesTable', $tableStyle);

    // Create the Signatures table
    $table = $section->addTable('SignaturesTable');

    // Table Header
    $table->addRow();
    $table->addCell(5000)->addText("Person", ['bold' => true]);
    $table->addCell(3000)->addText("Signature", ['bold' => true]);
    $table->addCell(2000)->addText("Date", ['bold' => true]);

    // Add rows for different roles
    $table->addRow();
    $table->addCell(5000)->addText("Project Executor\n" . ($projectRoles['executor'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Incharge\n" . ($projectRoles['incharge'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("President of the Society / Chair Person of the Trust\n" . ($projectRoles['president'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Sanctioned / Authorized by\n" . ($projectRoles['authorizedBy'] ?? 'N/A'));
    $table->addCell(3000)->addText('');
    $table->addCell(2000)->addText('');

    $section->addTextBreak(2);

    // Approval Section Header
    $section->addText("Approval - To be filled by the Project Coordinator:", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Create the Approval table
    $table = $section->addTable('SignaturesTable');

    $table->addRow();
    $table->addCell(5000)->addText("Amount Approved");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Remarks if any");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Project Coordinator\n" . ($projectRoles['coordinator'] ?? 'N/A'));
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Signature");
    $table->addCell(5000)->addText('');

    $table->addRow();
    $table->addCell(5000)->addText("Date");
    $table->addCell(5000)->addText('');
}

// Individual Project Types Sections
private function addIndividualProjectSections(PhpWord $phpWord, $project)
{
    // Add a new section for individual project types
    $section = $phpWord->addSection();

    // Add header based on project type
    $section->addText("Individual Project Details", ['bold' => true, 'size' => 16]);
    $section->addText("Project Type: {$project->project_type}", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Handle different individual project types
    switch ($project->project_type) {
        case 'Individual - Ongoing Educational support':
            $this->addIESections($phpWord, $project);
            break;

        case 'Individual - Livelihood Application':
            $this->addILPSections($phpWord, $project);
            break;

        case 'Individual - Access to Health':
            $this->addIAHSections($phpWord, $project);
            break;

        case 'Individual - Initial - Educational support':
            $this->addIIESSections($phpWord, $project);
            break;

        default:
            $section->addText("Individual project type not yet implemented for Word export.", ['italic' => true]);
            break;
    }
}

// IES - Individual - Ongoing Educational support
private function addIESections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Individual - Ongoing Educational Support", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add basic information
    $section->addText("This is an individual educational support project.", ['italic' => true]);
    $section->addText("Note: Detailed individual project sections are not yet implemented in Word export.", ['italic' => true]);
}

// ILP - Individual - Livelihood Application
private function addILPSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Individual - Livelihood Application", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add basic information
    $section->addText("This is an individual livelihood application project.", ['italic' => true]);
    $section->addText("Note: Detailed individual project sections are not yet implemented in Word export.", ['italic' => true]);
}

// IAH - Individual - Access to Health
private function addIAHSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Individual - Access to Health", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add basic information
    $section->addText("This is an individual access to health project.", ['italic' => true]);
    $section->addText("Note: Detailed individual project sections are not yet implemented in Word export.", ['italic' => true]);
}

// IIES - Individual - Initial - Educational support
private function addIIESSections(PhpWord $phpWord, $project)
{
    $section = $phpWord->addSection();
    $section->addText("Individual - Initial - Educational Support", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);

    // Add basic information
    $section->addText("This is an individual initial educational support project.", ['italic' => true]);
    $section->addText("Note: Detailed individual project sections are not yet implemented in Word export.", ['italic' => true]);
}

}
