<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use App\Models\User;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{

    public function downloadPdf($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)
                ->with(['attachments', 'objectives.risks', 'objectives.activities.timeframes', 'sustainabilities', 'budgets'])
                ->firstOrFail();

            $generalUser = User::where('role', 'general')->first();

            $projectRoles = [
                'executor' => $project->executor_name,
                'incharge' => $project->in_charge_name,
                'president' => $project->president_name,
                'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
                'coordinator' => $project->coordinator_india_name
            ];

            $pdf = PDF::loadView('projects.Oldprojects.pdf', compact('project', 'projectRoles'));

            Log::info('ExportController@downloadPdf - PDF generated', ['project_id' => $project_id]);

            return $pdf->download("project_{$project_id}.pdf");
        } catch (\Exception $e) {
            Log::error('ExportController@downloadPdf - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }



    // Additional methods as required...



    public function downloadDoc($project_id)
{
    try {
        $project = Project::where('project_id', $project_id)
            ->with([
                'attachments',
                'objectives.risks',
                'objectives.activities.timeframes',
                'sustainabilities',
                'budgets'
            ])->firstOrFail();

        $generalUser = User::where('role', 'general')->first();

        $projectRoles = [
            'executor' => $project->executor_name,
            'incharge' => $project->in_charge_name, // Replacing 'applicant' with 'incharge'
            'president' => $project->president_name,
            'authorizedBy' => $generalUser ? $generalUser->name : 'N/A',
            'coordinator' => $project->coordinator_india_name // Fetching the Project Coordinator's name
        ];

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // General Information
        $section->addText("Project Details", ['bold' => true, 'size' => 16]);
        $section->addText("Project ID: {$project->project_id}");
        $section->addText("Project Title: {$project->project_title}");
        $section->addText("Project Type: {$project->project_type}");
        $section->addText("Society Name: {$project->society_name}");
        $section->addText("President Name: {$project->president_name}");
        $section->addText("In Charge Name: {$project->in_charge_name}");
        $section->addText("Executor Name: {$project->executor_name}");
        $section->addText("Executor Phone: {$project->executor_mobile}");
        $section->addText("Executor Email: {$project->executor_email}");
        $section->addText("Full Address: {$project->full_address}");
        $section->addText("Overall Project Period: {$project->overall_project_period} years");
        $section->addText("Overall Project Budget: Rs. " . number_format($project->overall_project_budget, 2));
        $section->addText("Amount Forwarded: Rs. " . number_format($project->amount_forwarded, 2));
        $section->addText("Amount Sanctioned: Rs. " . number_format($project->amount_sanctioned, 2));
        $section->addText("Opening Balance: Rs. " . number_format($project->opening_balance, 2));
        $section->addText("Coordinator India Name: {$project->coordinator_india_name}");
        $section->addText("Coordinator India Phone: {$project->coordinator_india_phone}");
        $section->addText("Coordinator India Email: {$project->coordinator_india_email}");
        $section->addText("Coordinator Luzern Name: {$project->coordinator_luzern_name}");
        $section->addText("Coordinator Luzern Phone: {$project->coordinator_luzern_phone}");
        $section->addText("Coordinator Luzern Email: {$project->coordinator_luzern_email}");
        $section->addText("Status: " . ucfirst($project->status));

        // Add spacing between sections
        $section->addTextBreak(1);

        // Goal of the Project
        $section->addText("Goal of the Project:", ['bold' => true]);
        $section->addText($project->goal);

        // Add spacing between sections
        $section->addTextBreak(1);

        // Logical Framework Section
        foreach ($project->objectives as $objective) {
            $section->addText("Objective: {$objective->objective}", ['bold' => true]);
            $section->addTextBreak(0.5);

            // Results / Outcomes
            $section->addText("Results / Outcomes:", ['bold' => true]);
            foreach ($objective->results as $result) {
                $section->addText($result->result);
            }
            $section->addTextBreak(0.5);

           // Risks
           $section->addText("Risks:", ['bold' => true]);
           foreach ($objective->risks as $risk) {
               $section->addText($risk->risk);
           }
           $section->addTextBreak(0.5);

            // Activities and Means of Verification
            $section->addText("Activities and Means of Verification:", ['bold' => true]);

            // Define table style
            $tableStyle = [
                'borderSize' => 6, // 1pt = 8 twips, 0.75pt ≈ 6 twips
                'borderColor' => '000000', // Black border
                'cellMargin' => 80 // Adds padding inside cells
            ];
            $phpWord->addTableStyle('TableStyle', $tableStyle);
            $table = $section->addTable('TableStyle');
            $table->addRow();
            $table->addCell(5000)->addText("Activities");
            $table->addCell(5000)->addText("Means of Verification");
            foreach ($objective->activities as $activity) {
                $table->addRow();
                $table->addCell(5000)->addText($activity->activity);
                $table->addCell(5000)->addText($activity->verification);
            }
            $section->addTextBreak(0.5);

            // Time Frame for Activities
            $section->addText("Time Frame for Activities:", ['bold' => true]);
            $table = $section->addTable('TableStyle');
            $table->addRow();
            $table->addCell(5000)->addText("Activities");
            foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $month) {
                $table->addCell(500)->addText($month);
            }
            foreach ($objective->activities as $activity) {
                $table->addRow();
                $table->addCell(5000)->addText($activity->activity);
                foreach (range(1, 12) as $month) {
                    $isChecked = $activity->timeframes->contains(function($timeframe) use ($month) {
                        return $timeframe->month == $month && $timeframe->is_active == 1;
                    });
                    $table->addCell(500)->addText($isChecked ? '✔' : '');
                }
            }
        }

        // Add spacing between sections
        $section->addTextBreak(1);

        // Sustainability Section
        $section->addText("Project Sustainability, Monitoring and Methodologies", ['bold' => true, 'size' => 14]);
        foreach ($project->sustainabilities as $sustainability) {
            $section->addText("Explain the Sustainability of the Project:", ['bold' => true]);
            $section->addText($sustainability->sustainability ?? 'N/A');
            $section->addTextBreak(0.5);

            $section->addText("Explain the Monitoring Process of the Project:", ['bold' => true]);
            $section->addText($sustainability->monitoring_process ?? 'N/A');
            $section->addTextBreak(0.5);

            $section->addText("Explain the Methodology of Reporting:", ['bold' => true]);
            $section->addText($sustainability->reporting_methodology ?? 'N/A');
            $section->addTextBreak(0.5);

            $section->addText("Explain the Methodology of Evaluation:", ['bold' => true]);
            $section->addText($sustainability->evaluation_methodology ?? 'N/A');
        }

        // Add spacing between sections
        $section->addTextBreak(1);

        // Budget Details
        $groupedBudgets = $project->budgets->groupBy('phase');
        foreach ($groupedBudgets as $phase => $budgets) {
            $section->addText("Phase $phase", ['bold' => true, 'size' => 14]);
            $section->addText("Amount Sanctioned in Phase $phase: Rs. " . number_format($budgets->sum('this_phase'), 2));
            $section->addTextBreak(0.5);

            $table = $section->addTable('TableStyle');
            $table->addRow();
            $table->addCell(4000)->addText("Particular");
            $table->addCell(1000)->addText("Costs");
            $table->addCell(1000)->addText("Rate Multiplier");
            $table->addCell(1000)->addText("Rate Duration");
            $table->addCell(1000)->addText("Rate Increase (next phase)");
            $table->addCell(1000)->addText("This Phase (Auto)");
            $table->addCell(1000)->addText("Next Phase (Auto)");

            foreach ($budgets as $budget) {
                $table->addRow();
                $table->addCell(4000)->addText($budget->particular);
                $table->addCell(1000)->addText(number_format($budget->rate_quantity, 2));
                $table->addCell(1000)->addText(number_format($budget->rate_multiplier, 2));
                $table->addCell(1000)->addText(number_format($budget->rate_duration, 2));
                $table->addCell(1000)->addText(number_format($budget->rate_increase, 2));
                $table->addCell(1000)->addText(number_format($budget->this_phase, 2));
                $table->addCell(1000)->addText(number_format($budget->next_phase, 2));
            }

            $table->addRow();
            $table->addCell(4000)->addText("Total");
            $table->addCell(1000)->addText(number_format($budgets->sum('rate_quantity'), 2));
            $table->addCell(1000)->addText(number_format($budgets->sum('rate_multiplier'), 2));
            $table->addCell(1000)->addText(number_format($budgets->sum('rate_duration'), 2));
            $table->addCell(1000)->addText(number_format($budgets->sum('rate_increase'), 2));
            $table->addCell(1000)->addText(number_format($budgets->sum('this_phase'), 2));
            $table->addCell(1000)->addText(number_format($budgets->sum('next_phase'), 2));
        }

        // Add spacing between sections
        $section->addTextBreak(1);

        // Attachment Details
        $section->addText("Attachments", ['bold' => true, 'size' => 14]);
        foreach ($project->attachments as $attachment) {
            $section->addText("Attachment: " . $attachment->file_name);
            $section->addText("Description: " . $attachment->description);
            $section->addTextBreak(0.5);
        }

        // Signature and Approval Sections with page break control
        $section = $phpWord->addSection(['breakType' => 'continuous']);
        $section->addTextBreak(1);
        $section->addText("Signatures", ['bold' => true, 'size' => 16]);

        $table = $section->addTable('TableStyle');
        $table->addRow();
        $table->addCell(5000)->addText("Person");
        $table->addCell(3000)->addText("Signature");
        $table->addCell(2000)->addText("Date");

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
        $table->addCell(5000)->addText("Project Sanctioned / Authorised by\n" . ($projectRoles['authorizedBy'] ?? 'N/A'));
        $table->addCell(3000)->addText('');
        $table->addCell(2000)->addText('');

        $section->addTextBreak(1);
        $section->addText("APPROVAL - To be filled by the Project Coordinator:", ['bold' => true, 'size' => 14]);

        $table = $section->addTable('TableStyle');
        $table->addRow();
        $table->addCell(5000)->addText("Amount approved");
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

        // Save and download the DOCX file
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $filePath = storage_path("app/public/Project_{$project->project_id}.docx");
        $objWriter->save($filePath);

        Log::info('ExportController@downloadDoc - DOC generated', ['project_id' => $project_id]);

        return response()->download($filePath)->deleteFileAfterSend(true);
    } catch (\Exception $e) {
        Log::error('ExportController@downloadDoc - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
        throw $e;
    }
}



}
