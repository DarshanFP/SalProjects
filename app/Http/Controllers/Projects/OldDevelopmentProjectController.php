<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\OldProjects\OldDevelopmentProject;
use App\Models\OldProjects\OldDevelopmentProjectBudget;
use App\Models\OldProjects\OldDevelopmentProjectAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OldDevelopmentProjectController extends Controller
{
    public function createOldProject()
    {
        $user = Auth::user();
        $users = User::all(); // Adjust the query to get the relevant users
        return view('projects.developmentProjects.oldProjects', compact('user', 'users'));

    }

    public function storeOldProject(Request $request)
    {
        // Validate the request
        $request->validate([
            'project_title' => 'required|string|max:255',
            'place' => 'required|string|max:255',
            'society_name' => 'required|string|max:255',
            'commencement_month' => 'required|integer|min:1|max:12',
            'commencement_year' => 'required|integer|min:1900|max:' . date('Y'),
            'in_charge' => 'required|string|max:255',
            'total_beneficiaries' => 'required|integer',
            'reporting_period' => 'required|string|max:255',
            'goal' => 'required|string',
            'phases.*.budget.*.description' => 'required|string',
            'phases.*.budget.*.rate_quantity' => 'required|numeric',
            'phases.*.budget.*.rate_multiplier' => 'required|numeric',
            'phases.*.budget.*.rate_duration' => 'required|numeric',
            'phases.*.budget.*.rate_increase' => 'nullable|numeric',
            'phases.*.budget.*.this_phase' => 'required|numeric',
            'phases.*.budget.*.next_phase' => 'required|numeric',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:2048',
            'attachment_descriptions.*' => 'nullable|string',
        ]);

        // Concatenate month and year
        $commencement_month_year = $request->commencement_month . '/' . $request->commencement_year;

        // Store the project data
        $project = OldDevelopmentProject::create([
            'user_id' => Auth::id(),
            'project_title' => $request->project_title,
            'place' => $request->place,
            'society_name' => $request->society_name,
            'commencement_month_year' => $commencement_month_year,
            'in_charge' => $request->in_charge,
            'total_beneficiaries' => $request->total_beneficiaries,
            'reporting_period' => $request->reporting_period,
            'goal' => $request->goal,
            'total_amount_sanctioned' => $request->total_amount_sanctioned,
        ]);

        // Store the budget data
        foreach ($request->input('phases', []) as $phaseIndex => $phase) {
            foreach ($phase['budget'] as $budget) {
                OldDevelopmentProjectBudget::create([
                    'project_id' => $project->id,
                    'phase' => $phaseIndex + 1,
                    'description' => $budget['description'],
                    'rate_quantity' => $budget['rate_quantity'],
                    'rate_multiplier' => $budget['rate_multiplier'],
                    'rate_duration' => $budget['rate_duration'],
                    'rate_increase' => $budget['rate_increase'],
                    'this_phase' => $budget['this_phase'],
                    'next_phase' => $budget['next_phase'],
                ]);
            }
        }

        // Store the attachment data
        if ($request->has('attachments')) {
            foreach ($request->file('attachments') as $index => $file) {
                $path = $file->store('attachments', 'public');
                OldDevelopmentProjectAttachment::create([
                    'project_id' => $project->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'description' => $request->input('attachment_descriptions')[$index] ?? null,
                ]);
            }
        }

        // Redirect back with a success message
        return redirect()->route('projects.developmentProjects.createOldProject')->with('success', 'Project application submitted successfully.');
    }

     // List all projects for the executor
     public function index()
     {
         $projects = OldDevelopmentProject::where('user_id', Auth::id())->paginate(10);
         return view('projects.developmentProjects.index', compact('projects'));
     }

     // Show a single project projects.developmentProjects.index
     public function show($id)
     {
         $project = OldDevelopmentProject::findOrFail($id);
         return view('projects.developmentProjects.show', compact('project'));
     }

     // Edit a project
     public function edit($id)
     {
         $project = OldDevelopmentProject::findOrFail($id);
         return view('projects.developmentProjects.edit', compact('project'));
     }

     // Submit a project to provincial
     public function submit(Request $request, $id)
     {
         $project = OldDevelopmentProject::findOrFail($id);
         // Logic to submit the project to provincial
         // ...

         return redirect()->route('projects.developmentProjects.index')->with('success', 'Project submitted to provincial successfully.');
     }

     // Create a monthly report for a project
     public function createMonthlyReport($id)
     {
         $project = OldDevelopmentProject::findOrFail($id);
         // Logic to create a monthly report
         // ...

         return view('projects.developmentProjects.createMonthlyReport', compact('project'));
     }


}
