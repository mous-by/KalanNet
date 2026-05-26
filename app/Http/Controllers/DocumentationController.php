<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentationController extends Controller
{
    public function download($format)
    {
        $title = 'Documentation_Utilisateur_KalanNet';

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.documentation');
            return $pdf->download($title . '.pdf');
        }

        if ($format === 'word') {
            $headers = array(
                "Content-type" => "application/vnd.ms-word",
                "Content-Disposition" => "attachment;Filename=" . $title . ".doc"
            );

            // For Word, we can reuse the same HTML view since Word interprets basic HTML and CSS.
            $wordContent = view('pdf.documentation')->render();

            return response()->make($wordContent, 200, $headers);
        }

        abort(404);
    }
}
