<?php namespace Src;

use App\Mail\ExpenseReportScansReminderMail;
use App\Mail\TimeSheetScansReminderMail;
use App\Models\ActivityLog;
use App\Models\ExpenseReport;
use App\Models\TimeSheet;
use Carbon\Carbon;

/**
 * Class SendReminderForActiveTimeSheets
 *
 * If today is last day of month or last day of project - send a reminder for all active Projects (once per User)
 *
 */
class SendReminderAboutSignedScans
{
    public static function run($daysAgo = 2)
    {
        $emailsSent = [];

        // time sheets
        $tss = TimeSheet::where('status', 'expecting_signed_scan')->get();
        foreach ($tss as $ts) {
            if ($ts->project_sheet && !$ts->project_sheet->is_scans_required) {
                continue;
            }

            // activity log record
            $activityCount = self::findExpectingSignedScansActivity('time_sheets', $ts->id, $daysAgo);
            try {
                $email = object_get($ts, 'creator.email');
                if ($activityCount && $email !== null && !isset($emailsSent[$email])) {
                    $emailsSent[$email] = true;
                    self::sendReminderAboutTimeSheets($email, $ts);
                }

            } catch (\Exception $e) {
                \Log::error('Error occured sending reminder email ' . $e->getMessage(), [
                    'time_sheet_id' => $ts->id,
                ]);
            }
        }

        // expense reports
        $ers = ExpenseReport::where('status', 'expecting_signed_scan')->get();
        foreach ($ers as $er) {
            if ($er->project_sheet && !$er->project_sheet->is_scans_required) {
                continue;
            }

            // activity log record
            $activityCount = self::findExpectingSignedScansActivity('expense_reports', $er->id, $daysAgo);
            try {
                $email = object_get($er, 'creator.email');
                if ($activityCount && $email !== null && !isset($emailsSent[$email])) {
                    $emailsSent[$email] = true;
                    self::sendReminderAboutExpenseReports($email, $er);
                }

            } catch (\Exception $e) {
                \Log::error('Error occured sending reminder email ' . $e->getMessage(), [
                    'expense_report_id' => $er->id,
                ]);
            }
        }

    }

    private static function sendReminderAboutTimeSheets($email, TimeSheet $ts)
    {
        \Mail::to($email)->send(new TimeSheetScansReminderMail($ts));
    }

    private static function sendReminderAboutExpenseReports($email, ExpenseReport $expenseReport)
    {
        \Mail::to($email)->send(new ExpenseReportScansReminderMail($expenseReport));
    }

    private static function findExpectingSignedScansActivity($model, $id, $daysAgo)
    {
        $dayBefore = Carbon::parse(($daysAgo + 1) . ' days ago');

        $activityCount = ActivityLog::where(['model' => $model, 'entity_id' => $id])
            ->where('new_json', 'LIKE', '%"expecting_signed_scan"%')
            ->whereBetween('created_at', [$dayBefore, Carbon::parse("$daysAgo days ago")])
            ->count();

        return $activityCount;
    }
}
