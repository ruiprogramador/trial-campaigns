<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaignService) {}

    /**
     * GET /api/campaigns — list with send stats
     */
    public function index(): JsonResponse
    {
        $campaigns = Campaign::with(['contactList'])
            ->withCount(['sends', 'sends as pending_sends_count' => fn ($q) => $q->where('status', 'pending'),
                                  'sends as sent_sends_count'    => fn ($q) => $q->where('status', 'sent'),
                                  'sends as failed_sends_count'  => fn ($q) => $q->where('status', 'failed'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($campaigns);
    }

    /**
     * POST /api/campaigns — create
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create($request->validated());

        return response()->json($campaign->load('contactList'), 201);
    }

    /**
     * GET /api/campaigns/{id} — show with send stats
     */
    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::with('contactList')->findOrFail($id);

        return response()->json([
            ...$campaign->toArray(),
            'stats' => $campaign->stats,
        ]);
    }

    /**
     * POST /api/campaigns/{id}/dispatch — dispatch immediately
     * Protected by EnsureCampaignIsDraft middleware
     */
    public function dispatch(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $this->campaignService->dispatch($campaign);

        return response()->json(['message' => 'Campaign dispatched successfully.']);
    }
}
