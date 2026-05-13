<?php

namespace App\Http\Controllers;

use App\Models\PlanningDoc;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PublicPlanningShareController extends Controller
{
    public function show(string $token)
    {
        $doc = PlanningDoc::where('share_token', $token)->with('project')->firstOrFail();
        return view('planning.public_share', compact('doc', 'token'));
    }

    public function printPdf(string $token)
    {
        $doc = PlanningDoc::where('share_token', $token)->with('project')->firstOrFail();

        $htmlContent = $doc->content
            ? Str::markdown($doc->content, ['html_input' => 'allow', 'allow_unsafe_links' => false])
            : '';

        $html = view('emails.planning_pdf', [
            'doc'         => $doc,
            'project'     => $doc->project,
            'htmlContent' => $htmlContent,
            'sentBy'      => null,
        ])->render();

        $filename = preg_replace('/[^\w\s가-힣-]/u', '', $doc->title) ?: 'planning';
        $filename = $filename . '_v' . $doc->version . '.pdf';

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
