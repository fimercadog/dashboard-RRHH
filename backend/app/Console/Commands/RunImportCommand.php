<?php

namespace App\Console\Commands;

use App\Models\ImportJob;
use App\Services\Import\ImportEngine;
use Illuminate\Console\Command;

class RunImportCommand extends Command
{
    protected $signature   = 'import:run {importJobId : ID del ImportJob a procesar}';
    protected $description = 'Ejecuta una importación masiva de forma síncrona (sin queue)';

    public function handle(ImportEngine $engine): int
    {
        $id  = (int) $this->argument('importJobId');
        $job = ImportJob::find($id);

        if (! $job) {
            $this->error("ImportJob #{$id} no encontrado.");
            return self::FAILURE;
        }

        if (in_array($job->status, ['processing', 'completed'], true)) {
            $this->warn("ImportJob #{$id} ya está en estado '{$job->status}'. No se ejecuta de nuevo.");
            return self::SUCCESS;
        }

        $job->status         = 'processing';
        $job->processed_rows = 0;
        $job->created_rows   = 0;
        $job->skipped_rows   = 0;
        $job->failed_rows    = 0;
        $job->errors         = null;
        $job->started_at     = now();
        $job->completed_at   = null;
        $job->save();

        try {
            $this->info("Ejecutando importación #{$id} ({$job->entity})...");
            $engine->run($job);
            $job->refresh();
            $this->info("Completado. Creados: {$job->created_rows} | Omitidos: {$job->skipped_rows} | Fallidos: {$job->failed_rows}");
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'completed_at' => now()]);
            $this->error("Error en importación #{$id}: {$e->getMessage()}");
            throw $e;
        }

        $this->info("Importación #{$id} finalizada. Estado: {$job->status}");
        $this->table(
            ['Total', 'Procesadas', 'Creadas', 'Omitidas', 'Fallidas'],
            [[
                $job->total_rows,
                $job->processed_rows,
                $job->created_rows,
                $job->skipped_rows,
                $job->failed_rows,
            ]]
        );

        if (! empty($job->errors)) {
            $this->warn('Se encontraron errores:');
            foreach ($job->errors as $err) {
                $this->line("  Fila {$err['row']}: [{$err['type']}] {$err['message']}");
            }
        }

        return self::SUCCESS;
    }
}
