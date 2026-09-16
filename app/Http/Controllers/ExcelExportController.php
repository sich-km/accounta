<?php

namespace App\Http\Controllers;

use App\Exports\AccountaWorkbookExport;
use App\Services\FiscalYearService;
use App\Services\ProfitAndLossSummaryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportController extends Controller
{
    public function __invoke(
        Request $request,
        FiscalYearService $fiscalYearService,
        ProfitAndLossSummaryService $profitAndLossSummaryService,
    ): BinaryFileResponse {
        $fileName = 'accounta_'.now(config('app.timezone'))->format('Ymd_His').'.xlsx';

        return Excel::download(
            new AccountaWorkbookExport(
                (int) $request->user()->organization_id,
                $request->user()->isAdmin(),
                $fiscalYearService,
                $profitAndLossSummaryService,
            ),
            $fileName,
            ExcelWriter::XLSX,
        );
    }
}
