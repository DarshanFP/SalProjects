<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    //
    public function CoordinatorDashboard()
    {
        return view('coordinator.index');
    }
}
