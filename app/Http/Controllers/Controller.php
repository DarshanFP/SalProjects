<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Http\Request;
use PDF; // Import the PDF facade

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

}

class TestController extends Controller
{
    public function generatePdf()
    {
        $data = ['message' => 'This is a test PDF document.'];
        $pdf = PDF::loadView('pdf.test', $data);
        return $pdf->download('test.pdf');
    }
}
