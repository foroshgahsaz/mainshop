<?php

namespace App\Console\Commands;

use App\Services\Payment\PaymentLogArchiveService;
use Illuminate\Console\Command;

class ArchivePaymentLogsCommand extends Command
{
    protected $signature = 'payments:archive-logs';

    protected $description = 'Zip payment log files older than the 20-day retention window';

    public function handle(PaymentLogArchiveService $archive): int
    {
        $count = $archive->archiveLogsOlderThanRetention();

        $this->info("Archived {$count} payment log file(s).");

        return self::SUCCESS;
    }
}
