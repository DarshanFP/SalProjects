<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF; // Import the PDF facade

class TestController extends Controller
{
    public function generatePdf()
    {
        $data = ['message' => 'This is a test PDF document.'];
        $pdf = PDF::loadView('pdf.test', $data);
        return $pdf->download('test.pdf');
    }
}
