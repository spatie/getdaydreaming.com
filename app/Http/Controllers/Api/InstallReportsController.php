<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordInstallReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstallReportRequest;
use Illuminate\Http\Response;

class InstallReportsController extends Controller
{
    public function store(StoreInstallReportRequest $request, RecordInstallReport $recordInstallReport): Response
    {
        $recordInstallReport->execute($request->validated());

        return response()->noContent(202);
    }
}
