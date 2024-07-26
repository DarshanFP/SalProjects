<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('ProjectController@store - Data received from form', $request->all());

            $project = (new GeneralInfoController())->store($request);
            $keyInformation = (new KeyInformationController())->store($request, $project);
            $budget = (new BudgetController())->store($request, $project);
            $attachments = (new AttachmentController())->store($request, $project);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'Project created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@store - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error creating the project. Please try again.'])->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            Log::info('ProjectController@update - Data received from form', $request->all());

            $project = (new GeneralInfoController())->update($request, $id);
            $keyInformation = (new KeyInformationController())->update($request, $project);
            $budget = (new BudgetController())->update($request, $project);
            $attachments = (new AttachmentController())->update($request, $project);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProjectController@update - Error', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'There was an error updating the project. Please try again.'])->withInput();
        }
    }

    public function create()
    {
        return view('projects.create');
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        return view('projects.edit', compact('project'));
    }
}
