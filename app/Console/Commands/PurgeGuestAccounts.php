<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeGuestAccounts extends Command
{
    protected $signature = 'guests:purge {--days= : Override retention days} {--dry-run : Show what would be deleted}';
    protected $description = 'Delete guest users/students and their requests after the retention window';

    public function handle(): int
    {
        $retentionDays = $this->option('days');
        $retentionDays = is_numeric($retentionDays)
            ? max(0, (int) $retentionDays)
            : max(0, (int) config('guests.retention_days', 7));

        $cutoff = now()->subDays($retentionDays);
        $dryRun = (bool) $this->option('dry-run');

        $totalStudents = 0;
        $totalRequests = 0;
        $totalAttachments = 0;

        $query = Student::query()
            ->where('student_number', 'like', 'GUEST-%')
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('serviceRequests', function ($requestQuery) {
                $requestQuery->whereNull('archived_at')
                    ->whereIn('status', [
                        'Submitted',
                        'In Review',
                        'Awaiting Student Response',
                        'Appointment Scheduled',
                    ])
                    ->where(function ($stageQuery) {
                        $stageQuery->whereNull('queue_stage')
                            ->orWhereNotIn('queue_stage', ['completed', 'no_show']);
                    });
            })
            ->whereDoesntHave('serviceRequests', function ($requestQuery) use ($cutoff) {
                $requestQuery->where('updated_at', '>', $cutoff);
            })
            ->with(['user', 'serviceRequests.attachments'])
            ->orderBy('student_number');

        $query->chunk(100, function ($students) use (
            $dryRun,
            &$totalStudents,
            &$totalRequests,
            &$totalAttachments
        ) {
            foreach ($students as $student) {
                $totalStudents++;
                $totalRequests += $student->serviceRequests->count();

                foreach ($student->serviceRequests as $request) {
                    foreach ($request->attachments as $attachment) {
                        $path = $attachment->file_path;
                        if ($path !== '' && $path !== null) {
                            $totalAttachments++;
                            if (!$dryRun) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                    }
                }

                if ($dryRun) {
                    continue;
                }

                if ($student->user) {
                    $student->user->delete();
                } else {
                    $student->delete();
                }
            }
        });

        $mode = $dryRun ? 'Dry run:' : 'Purged:';
        $this->info("{$mode} {$totalStudents} guest account(s), {$totalRequests} request(s), {$totalAttachments} attachment(s).");

        return self::SUCCESS;
    }
}
