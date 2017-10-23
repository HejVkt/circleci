<?php namespace Src;

use App\Models\ActivityLog;
use App\Models\ProjectSheet;
use Carbon\Carbon;

class AdvanceProjectSheetStatuses
{
    public static function run(Carbon $now)
    {
        ProjectSheet::each(function (ProjectSheet $projectSheet) use ($now) {
            self::forPs($projectSheet, $now);
        });
    }

    public static function forPs(ProjectSheet $projectSheet, $now): void
    {
        $pastStartDate = $now->greaterThanOrEqualTo(Carbon::parse($projectSheet->start_date));
        $pastEndDate = $now->greaterThanOrEqualTo(Carbon::parse($projectSheet->end_date)->addDay(1));
        $beforeEndDate = !$pastEndDate;
        $old = $projectSheet->status;
        $user = current_user();

        if ($pastStartDate && $beforeEndDate && $projectSheet->status === ProjectSheet::EXPECTING_PROJECT_START) {
            $projectSheet->status = ProjectSheet::PROJECT_HAS_STARTED;
            $projectSheet->save();
            $newData = json_encode(['status' => ProjectSheet::PROJECT_HAS_STARTED]);
        }

        $startedOrApproved = $projectSheet->status === ProjectSheet::EXPECTING_PROJECT_START ||
            $projectSheet->status === ProjectSheet::PROJECT_HAS_STARTED;

        if ($pastEndDate && $startedOrApproved) {
            $projectSheet->status = ProjectSheet::PROJECT_IS_COMPLETE;
            $projectSheet->save();
            $newData = json_encode(['status' => ProjectSheet::PROJECT_IS_COMPLETE]);
        }

        if ($projectSheet->status != $old) {
            ActivityLog::forceCreate([
                'model' => $projectSheet->getTable(),
                'submodel' => null,
                'entity_id' => $projectSheet->id,
                'user_id' => $user ? $user->id : 0,
                'operation' => 'updated',
                'custom_text' => '',
                'old_json' => json_encode(['status' => $old]),
                'new_json' => $newData,
            ]);
        }
    }
}
