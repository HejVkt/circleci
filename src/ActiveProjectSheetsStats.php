<?php

namespace Src;

use App\Models\ProjectSheet;
use Carbon\Carbon;

class ActiveProjectSheetsStats
{
    public static function run(Carbon $date)
    {
        $projectSheets = ProjectSheet::where('status', ProjectSheet::PROJECT_HAS_STARTED);
        \App\Models\ActiveProjectSheetsStats::updateOrCreate(
            ['date' => $date->toDateString()],
            [
                'active_project_sheets' => $projectSheets->count(),
                'total_itswap_fee' => $projectSheets->sum('itswap_service_fee_euro')
            ]
        );

    }
}
