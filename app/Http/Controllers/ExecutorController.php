<?php

namespace App\Http\Controllers;

use App\Models\OldProjects\Project;
use App\Models\Reports\Quarterly\RQDPReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExecutorController extends Controller
{
    //
    public function ExecutorDashboard()
    {
    // Get the authenticated user's projects
    $projects = Project::where('user_id', Auth::id())->get();
    $user = Auth::user();

    // Pass the projects to the executor index view
    return view('executor.index', compact('projects', 'user'));
    }
}
