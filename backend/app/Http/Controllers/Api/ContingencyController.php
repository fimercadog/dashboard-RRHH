<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Models\ContingencyEvent;
use App\Models\ContingencySession;
use App\Support\ContingencyModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ContingencyController extends Controller
{
    use ResolvesCompany;

    /**
     * Estado actual. Abierto a cualquier usuario autenticado: todos necesitan
     * saber si la contingencia esta activa y que modulos quedan en modo local.
     */
    public function status(Request $request)
    {
        $session = $this->activeSession($request);

        return response()->json([
            'active' => (bool) $session,
            'modules' => ContingencyModuleRegistry::all(),
            'session' => $session ? [
                'id' => $session->id,
                'enabled_modules' => $session->enabled_modules,
                'activated_at' => $session->activated_at,
                'activated_by' => $session->activatedBy?->only(['id', 'name']),
            ] : null,
        ]);
    }

    /** Activar. Gated por can:settings.manage en la ruta. */
    public function activate(Request $request)
    {
        $data = $request->validate([
            'enabled_modules' => ['required', 'array', 'min:1'],
            'enabled_modules.*' => ['string', Rule::in(ContingencyModuleRegistry::keys())],
        ]);

        $companyId = $this->companyId($request);

        if ($this->activeSession($request)) {
            return response()->json(['message' => 'Ya hay una contingencia activa.'], 409);
        }

        $session = ContingencySession::create([
            'company_id' => $companyId,
            'enabled_modules' => array_values(array_unique($data['enabled_modules'])),
            'activated_by' => $request->user()?->id,
            'activated_at' => Carbon::now(),
            'status' => 'active',
        ]);

        $this->logEvent($request, $session, 'activated', ['enabled_modules' => $session->enabled_modules]);

        return response()->json($this->status($request)->getData(true), 201);
    }

    /** Desactivar. Gated por can:settings.manage en la ruta. */
    public function deactivate(Request $request)
    {
        $session = $this->activeSession($request);

        if (! $session) {
            return response()->json(['message' => 'No hay una contingencia activa.'], 409);
        }

        $session->update([
            'status' => 'closed',
            'deactivated_by' => $request->user()?->id,
            'deactivated_at' => Carbon::now(),
        ]);

        $this->logEvent($request, $session, 'deactivated');

        return response()->json($this->status($request)->getData(true));
    }

    private function activeSession(Request $request): ?ContingencySession
    {
        return ContingencySession::query()
            ->with('activatedBy:id,name')
            ->where('company_id', $this->companyId($request))
            ->where('status', 'active')
            ->latest('activated_at')
            ->first();
    }

    private function logEvent(Request $request, ContingencySession $session, string $action, ?array $context = null): void
    {
        ContingencyEvent::create([
            'company_id' => $session->company_id,
            'contingency_session_id' => $session->id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
