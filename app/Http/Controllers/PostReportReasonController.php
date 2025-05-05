<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PostReportReason;
use Illuminate\Auth\Access\AuthorizationException;

class PostReportReasonController extends Controller
{
    function index(Request $request) {
        $reasons = PostReportReason::all();
        return response()->json($reasons);
    }
}