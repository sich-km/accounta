<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->canManageMasters(), 403);

        return view('management.index');
    }
}
