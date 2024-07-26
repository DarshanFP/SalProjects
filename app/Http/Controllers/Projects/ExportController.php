<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\Project;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function downloadPdf($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)->with('attachments')->firstOrFail();

            $pdf = PDF::loadView('projects.Oldprojects.pdf', compact('project'));

            Log::info('ExportController@downloadPdf - PDF generated', ['project_id' => $project_id]);

            return $pdf->download("project_{$project_id}.pdf");
        } catch (\Exception $e) {
            Log::error('ExportController@downloadPdf - Error', ['error' => $e->getMessage(), 'project_id' => $project_id]);
            throw $e;
        }
    }

    public function downloadDoc($project_id)
    {
        try {
            $project = Project::where('project_id', $project_id)->with('attachments')->firstOrFail();

            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

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

            $section->addTextBreak(1);
            $section->addText("Goal of the Project:", ['bold' => true]);
            $section->addText($project->goal);

            // Add budget details
            $groupedBudgets = $project->budgets->groupBy('phase');
            foreach ($groupedBudgets as $phase => $budgets) {
                $section->addTextBreak(1);
                $section->addText("Phase $phase", ['bold' => true, 'size' => 14]);

                $section->addText("Amount Sanctioned in Phase $phase: Rs. " . number_format($budgets->sum('this_phase'), 2));

                $table = $section->addTable();
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

            // Add attachment details
            $section->addTextBreak(1);
            $section->addText("Attachments", ['bold' => true, 'size' => 14]);
            foreach ($project->attachments as $attachment) {
                $section->addText("Attachment: " . $attachment->file_name);
                $section->addText("Description: " . $attachment->description);
            }

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
