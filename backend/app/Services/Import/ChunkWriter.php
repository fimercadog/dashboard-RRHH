<?php

namespace App\Services\Import;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChunkWriter
{
    /**
     * Insert $rows into $modelClass in SQLite-safe sub-batches.
     * Returns ['result' => 'ok'] or ['result' => 'error', 'message' => '...']
     */
    public function write(string $modelClass, array $rows): array
    {
        if (empty($rows)) {
            return ['result' => 'ok'];
        }

        $nCols     = count(array_keys($rows[0]));
        $batchSize = max(1, (int) floor(999 / $nCols));

        foreach (array_chunk($rows, $batchSize) as $subLote) {
            try {
                DB::transaction(fn () => $modelClass::insert($subLote));
            } catch (QueryException $e) {
                Log::error('ImportChunkWriter: error al insertar lote.', [
                    'table'   => $modelClass,
                    'message' => $e->getMessage(),
                ]);
                return ['result' => 'error', 'message' => 'Error al insertar el lote. Verifica el formato de los datos.'];
            }
        }

        return ['result' => 'ok'];
    }
}
