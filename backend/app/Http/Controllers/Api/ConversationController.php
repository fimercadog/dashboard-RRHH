<?php

namespace App\Http\Controllers\Api;

use App\Contracts\WhatsappServiceContract;
use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignConversationRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ConversationMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\AuditService;
use App\Services\TableQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request, TableQueryService $tables): AnonymousResourceCollection
    {
        $cid = $this->companyId($request);

        $query = Conversation::where('company_id', $cid)
            ->with(['assignedUser:id,name', 'latestMessage']);

        $tables->apply($request, $query, ['contact_name', 'contact_phone'], [
            'status'      => 'status',
            'assigned_to' => 'assigned_to',
        ]);

        $perPage = min((int) $request->input('per_page', TableQueryService::DEFAULT_PER_PAGE), TableQueryService::MAX_PER_PAGE);

        return ConversationResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, int $id): ConversationResource
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)
            ->with(['assignedUser:id,name', 'latestMessage'])
            ->findOrFail($id);

        return new ConversationResource($conversation);
    }

    public function messages(Request $request, int $id): AnonymousResourceCollection
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)->findOrFail($id);

        $perPage = min((int) $request->input('per_page', 50), 200);

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return ConversationMessageResource::collection($messages);
    }

    public function assign(AssignConversationRequest $request, int $id, AuditService $audit): ConversationResource
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)->findOrFail($id);
        $old = $conversation->getOriginal();

        $conversation->update(['assigned_to' => $request->validated('user_id')]);
        $audit->record('assigned', $conversation, $request, $old);

        return new ConversationResource($conversation->load('assignedUser:id,name', 'latestMessage'));
    }

    public function transfer(Request $request, int $id, AuditService $audit): ConversationResource
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)->findOrFail($id);
        $old = $conversation->getOriginal();

        $conversation->update(['status' => 'transferred']);
        $audit->record('transferred', $conversation, $request, $old);

        return new ConversationResource($conversation->load('assignedUser:id,name', 'latestMessage'));
    }

    public function close(Request $request, int $id, AuditService $audit): ConversationResource
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)->findOrFail($id);
        $old = $conversation->getOriginal();

        $conversation->update(['status' => 'closed']);
        $audit->record('closed', $conversation, $request, $old);

        return new ConversationResource($conversation->load('assignedUser:id,name', 'latestMessage'));
    }

    public function reopen(Request $request, int $id, AuditService $audit): ConversationResource
    {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)->findOrFail($id);
        $old = $conversation->getOriginal();

        $conversation->update(['status' => 'open']);
        $audit->record('reopened', $conversation, $request, $old);

        return new ConversationResource($conversation->load('assignedUser:id,name', 'latestMessage'));
    }

    public function sendMessage(
        SendMessageRequest $request,
        int $id,
        WhatsappServiceContract $whatsappService,
        AuditService $audit,
    ): JsonResponse {
        $cid = $this->companyId($request);

        $conversation = Conversation::where('company_id', $cid)
            ->with('whatsappSetting')
            ->findOrFail($id);

        // Ventana de 24 horas de WhatsApp.
        if (! $conversation->isWithin24hWindow()) {
            return response()->json([
                'message' => 'No se puede enviar mensajes de texto libres fuera de la ventana de 24 horas de WhatsApp.',
            ], 422);
        }

        $setting = $conversation->whatsappSetting;

        if (! $setting || ! $setting->is_active || ! filled($setting->access_token)) {
            return response()->json([
                'message' => 'La configuración de WhatsApp no está activa o completa.',
            ], 422);
        }

        try {
            $wamid = $whatsappService->sendText(
                $setting,
                $conversation->contact_phone,
                $request->validated('content'),
                $cid,
            );
        } catch (\Throwable) {
            return response()->json(['message' => 'Error al enviar el mensaje a WhatsApp. Inténtalo de nuevo.'], 503);
        }

        if (! $wamid) {
            return response()->json(['message' => 'WhatsApp no devolvió un identificador de mensaje. El mensaje no fue enviado.'], 503);
        }

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'content'         => $request->validated('content'),
            'content_type'    => 'text',
            'sender_type'     => 'agent',
            'meta_message_id' => $wamid,
            'status'          => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        $audit->record('message_sent', $conversation, $request);

        return (new ConversationMessageResource($message))->response()->setStatusCode(201);
    }
}
