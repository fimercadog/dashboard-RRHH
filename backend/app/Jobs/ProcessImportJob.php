<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Services\Import\ImportEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessImportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    // ponytail: tries=1 because Client/Product have no unique DB constraint — retrying a partially-inserted
    // batch would silently duplicate rows. Fix: add unique constraints then raise tries back to 3.
    public int $tries   = 1;
    public int $backoff = 30;

    public function __construct(public readonly int $importJobId) {}

    public function handle(ImportEngine $engine): void
    {
        $job = ImportJob::findOrFail($this->importJobId);

        // RESET counters so retries start clean
        $job->status         = 'processing';
        $job->processed_rows = 0;
        $job->created_rows   = 0;
        $job->skipped_rows   = 0;
        $job->failed_rows    = 0;
        $job->errors         = null;
        $job->started_at     = now();
        $job->completed_at   = null;
        $job->save();

        $engine->run($job);
    }

    public function failed(\Throwable $exception): void
    {
        ImportJob::where('id', $this->importJobId)->update([
            'status'       => 'failed',
            'completed_at' => now(),
        ]);

        Log::error("ProcessImportJob [{$this->importJobId}] falló definitivamente: " . $exception->getMessage(), [
            'exception' => $exception,
        ]);
    }
}
