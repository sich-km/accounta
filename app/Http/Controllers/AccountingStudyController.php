<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AccountingStudyController extends Controller
{
    public function __invoke(): View
    {
        return view('accounting-study.index');
    }
}
