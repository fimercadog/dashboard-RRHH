<?php

namespace App\Services;

use App\Contracts\PendingProvider;
use App\Models\User;

class PendingService
{
    /** @var PendingProvider[] */
    private array $providers = [];

    public function register(PendingProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Aggregate all pending items across registered providers.
     * Each provider is responsible for company_id scoping and permission checks.
     *
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function all(int $companyId, User $user): array
    {
        $items = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->items($companyId, $user) as $item) {
                if ($item['count'] > 0) {
                    $items[] = $item;
                }
            }
        }

        // Sort: high priority first, then by date ascending (oldest first).
        usort($items, function (array $a, array $b): int {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            $pa = $order[$a['priority']] ?? 1;
            $pb = $order[$b['priority']] ?? 1;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        $total = array_sum(array_column($items, 'count'));

        return ['items' => array_values($items), 'total' => $total];
    }
}
