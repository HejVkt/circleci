<?php namespace Src;

use App\Models\ProjectSheet;
use Carbon\Carbon;

/**
 * Class PeriodicallyCreateTimesheets
 *
 * We need to find all Project Sheets that don't contain Time Sheet for today and create a Time Sheet for this month
 *
 */
class PeriodicallyCreateTimesheets
{
    public static function run(Carbon $date)
    {
        $projectSheets = ProjectSheet::where('status', ProjectSheet::PROJECT_HAS_STARTED);
        $projectSheets->each(function (ProjectSheet $ps) use ($date) {
            if (!$ps->hasTimeSheetForDate($date)) {
                $ps->createTimeSheetForCurrentPeriod($date);
            }
        });

    }
}