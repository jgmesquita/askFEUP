<?php

namespace App\Http\Controllers;

use App\Models\PostReport;
use Illuminate\Http\Request;

class PostReportController extends Controller
{
    public function renderPage() 
    {
        $reportedPosts = PostReport::getReportedPosts(); 
        return view('pages.mod-center', compact('reportedPosts'));
    }

    public function resolveReport(Request $request) {
        $validated = $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'integer|exists:post_report,id',
        ]);
    
        PostReport::whereIn('id', $validated['report_ids'])->update(['status' => 'resolved']);
    
        return response()->json(['success' => true, 'message' => 'Reports resolved successfully']);
    }
}