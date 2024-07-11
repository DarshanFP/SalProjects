<?php

namespace App\Http\Controllers;

use App\Models\Reports\Quarterly\RQDPReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExecutorController extends Controller
{
    //
    public function ExecutorDashboard()
    {
        //commented out the return view ('executor.index'); and replaced it with the code below
    //     return view ('executor.index');
    // }

    // public function index()
    // {

        $reports = RQDPReport::where('user_id', Auth::id())->get();
        return view('executor.index', compact('reports'));
    }
}
