<?php

namespace App\Console\Commands;

use App\Enums\EmailStatus;
use App\Jobs\AnalyzeEmailJob;
use App\Models\Email;
use Illuminate\Console\Command;

class ReprocessEmailsCommand extends Command
{
    protected $signature = 'emails:reprocess {--failed-only : Only re-process emails that previously failed}';

    protected $description = 'Re-queue analysis jobs for already-fetched emails';

    public function handle(): int
    {
        $query = Email::query()
            ->whereNotIn('status', [EmailStatus::Ignored->value, EmailStatus::Analyzed->value]);

        if ($this->option('failed-only')) {
            $query->where('status', EmailStatus::Failed->value);
        }

        $emails = $query->get();
        $count = 0;

        foreach ($emails as $email) {
            $email->forceFill([
                'status' => EmailStatus::Pending->value,
                'attempts' => 0,
                'analysis_error' => null,
            ])->save();

            AnalyzeEmailJob::dispatch($email);
            $count++;
        }

        $this->info("emails:reprocess — queued {$count} email(s).");

        return self::SUCCESS;
    }
}