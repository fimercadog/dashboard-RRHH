<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /** Publico: lo llaman los formularios del sitio de marketing. Throttle en la ruta. */
    public function store(StoreLeadRequest $request)
    {
        Lead::create($request->safe()->except('consent') + [
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Recibimos tu solicitud. Te contactaremos pronto.',
        ], 201);
    }

    /** Panel: gated por can:leads.view en la ruta. */
    public function index(Request $request)
    {
        return Lead::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('company_name', 'like', $term));
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));
    }

    /** Panel: solo cambia el estado del lead. */
    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'discarded'])],
        ]);

        $lead->update($data);

        return response()->json($lead);
    }
}
