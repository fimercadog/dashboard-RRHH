<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AudienceSource;
use App\Http\Controllers\Api\Concerns\ResolvesCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use ResolvesCompany;

    /** @param  AudienceSource[]  $sources */
    public function __construct(private readonly array $sources) {}

    public function index(Request $request): JsonResponse
    {
        $cid  = $this->companyId($request);
        $data = Campaign::where('company_id', $cid)
            ->with('creator:id,name')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => CampaignResource::collection($data->items()),
            'meta' => ['total' => $data->total(), 'last_page' => $data->lastPage(), 'current_page' => $data->currentPage()],
        ]);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $cid      = $this->companyId($request);
        $campaign = Campaign::create(array_merge(
            $request->validated(),
            ['company_id' => $cid, 'created_by' => $request->user()->id, 'status' => 'draft'],
        ));

        return response()->json(new CampaignResource($campaign->load('creator')), 201);
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeCompany($request, $campaign->company_id);

        return response()->json(new CampaignResource($campaign->load('creator')));
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeCompany($request, $campaign->company_id);
        $campaign->update($request->validated());

        return response()->json(new CampaignResource($campaign->fresh('creator')));
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeCompany($request, $campaign->company_id);
        $campaign->delete();

        return response()->json(null, 204);
    }

    /** Preview recipient count from the chosen source. */
    public function previewAudience(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizeCompany($request, $campaign->company_id);

        $source = $this->findSource($campaign->audience_source);

        if (! $source) {
            return response()->json(['message' => 'Fuente de destinatarios no reconocida.'], 422);
        }

        if (! $source->isReady()) {
            return response()->json([
                'ready'   => false,
                'message' => $source->notReadyMessage(),
            ], 200);
        }

        $preview = $source->preview($campaign->company_id, $campaign->audience_filters ?? []);

        return response()->json(array_merge(['ready' => true], $preview));
    }

    /** GET /campaigns/sources — list all registered sources with their status. */
    public function sources(): JsonResponse
    {
        $list = array_map(fn (AudienceSource $s) => [
            'key'     => $s->key(),
            'name'    => $s->name(),
            'ready'   => $s->isReady(),
            'message' => $s->isReady() ? null : $s->notReadyMessage(),
        ], $this->sources);

        return response()->json($list);
    }

    private function findSource(string $key): ?AudienceSource
    {
        foreach ($this->sources as $source) {
            if ($source->key() === $key) {
                return $source;
            }
        }
        return null;
    }

    private function authorizeCompany(Request $request, int $campaignCompanyId): void
    {
        abort_if($campaignCompanyId !== $this->companyId($request), 403);
    }
}
