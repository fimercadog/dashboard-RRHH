<?php

namespace App\Services\Import;

use App\Contracts\ImportableEntity;
use App\Models\ImportJob;
use App\Services\Import\Handlers\ClientImportHandler;
use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\Handlers\ProductImportHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportEngine
{
    private const CHUNK_SIZE = 200;

    private ErrorAccumulator $accumulator;
    private DuplicateChecker $duplicateChecker;
    private ChunkWriter $chunkWriter;

    public function __construct(
        ErrorAccumulator $accumulator,
        DuplicateChecker $duplicateChecker,
        ChunkWriter $chunkWriter,
    ) {
        $this->accumulator      = $accumulator;
        $this->duplicateChecker = $duplicateChecker;
        $this->chunkWriter      = $chunkWriter;
    }

    public function run(ImportJob $job): void
    {
        // 1. Resolve handler
        try {
            $handler = $this->resolveHandler($job->entity);
        } catch (\InvalidArgumentException $e) {
            $this->markFailed($job, $e->getMessage());
            return;
        }

        // 2. Verify file exists
        if (! Storage::disk('local')->exists($job->file_path)) {
            $this->markFailed($job, 'El archivo de importación no existe en storage.');
            return;
        }

        $fullPath = Storage::disk('local')->path($job->file_path);
        $file     = new \SplFileObject($fullPath, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

        // 3. Read header
        $file->rewind();
        $rawHeader = $file->current();
        if (! $rawHeader) {
            $this->markFailed($job, 'El archivo CSV está vacío o no tiene encabezado.');
            return;
        }

        $header = array_map(fn ($col) => strtolower(trim((string) $col)), $rawHeader);
        $file->next();

        // 4. Verify required columns
        $missing = array_diff($handler->requiredColumns(), $header);
        if (! empty($missing)) {
            $this->markFailed($job, 'Columnas requeridas faltantes: ' . implode(', ', $missing));
            return;
        }

        // 5. Count total rows
        $totalRows = 0;
        while (! $file->eof()) {
            $row = $file->current();
            if ($row !== false && ! ($row === [null])) {
                $totalRows++;
            }
            $file->next();
        }

        $job->total_rows = $totalRows;
        $job->save();

        // 6. Read in chunks
        $file->rewind();
        $file->current(); // skip header
        $file->next();

        $chunk    = [];
        $rowNum   = 1;
        $startRow = 1;

        while (! $file->eof()) {
            $raw = $file->current();
            $file->next();

            if ($raw === false || $raw === [null]) {
                continue;
            }

            // Map CSV row to associative array using header
            $combined = array_combine($header, array_pad(array_map('trim', $raw), count($header), ''));
            // Normalize empty strings to null for optional columns
            foreach ($combined as $k => $v) {
                if ($v === '') {
                    $combined[$k] = null;
                }
            }

            $chunk[] = ['data' => $combined, 'row' => $rowNum];
            $rowNum++;

            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->processChunk($job, $handler, $chunk, $startRow);
                $startRow = $rowNum;
                $chunk    = [];
            }
        }

        // Process remaining rows
        if (! empty($chunk)) {
            $this->processChunk($job, $handler, $chunk, $startRow);
        }

        // 8. Mark complete
        $job->errors       = $this->accumulator->all() ?: null;
        $job->status       = 'completed';
        $job->completed_at = now();
        $job->save();
    }

    private function processChunk(ImportJob $job, ImportableEntity $handler, array $chunk, int $startRow): void
    {
        $pendingRows = [];

        foreach ($chunk as $entry) {
            $row    = $entry['data'];
            $rowNum = $entry['row'];

            // PASO 2: validate
            try {
                $validated = $handler->validateRow($row, $rowNum, $job->company_id);
            } catch (\RuntimeException $e) {
                $this->accumulator->add($rowNum, 'validation', null, $e->getMessage());
                $job->failed_rows++;
                continue;
            }

            // PASO 3: duplicate check
            $dupResult = $this->duplicateChecker->check($handler, $validated, $job->company_id);
            if ($dupResult === 'active') {
                $job->skipped_rows++;
                continue;
            }
            if ($dupResult === 'soft_deleted') {
                $this->accumulator->add($rowNum, 'validation', null, 'Registro eliminado previamente con datos duplicados.');
                $job->failed_rows++;
                continue;
            }

            // PASO 4: map row
            try {
                $mapped = $handler->mapRow($validated, $job->company_id);
            } catch (\RuntimeException $e) {
                $this->accumulator->add($rowNum, 'validation', null, $e->getMessage());
                $job->failed_rows++;
                continue;
            }

            $pendingRows[] = $mapped;
        }

        // PASO 6: write
        if (! empty($pendingRows)) {
            $result = $this->chunkWriter->write($handler->modelClass(), $pendingRows);

            if ($result['result'] === 'ok') {
                $job->created_rows += count($pendingRows);
            } else {
                $job->failed_rows += count($pendingRows);
                foreach ($pendingRows as $i => $r) {
                    $this->accumulator->add($startRow + $i, 'transaction', null, $result['message']);
                }
            }
        }

        // PASO 7: update processed count
        $job->processed_rows += count($chunk);

        // PASO 8: persist progress
        $job->save();
    }

    private function resolveHandler(string $entity): ImportableEntity
    {
        return match ($entity) {
            'employees' => app(EmployeeImportHandler::class),
            'clients'   => app(ClientImportHandler::class),
            'products'  => app(ProductImportHandler::class),
            default     => throw new \InvalidArgumentException("Entidad de importación desconocida: {$entity}"),
        };
    }

    private function markFailed(ImportJob $job, string $reason): void
    {
        $job->status       = 'failed';
        $job->errors       = [['row' => 0, 'type' => 'system', 'field' => null, 'message' => $reason]];
        $job->completed_at = now();
        $job->save();

        Log::error("ImportEngine: importación {$job->id} marcada como fallida. Motivo: {$reason}");
    }
}
