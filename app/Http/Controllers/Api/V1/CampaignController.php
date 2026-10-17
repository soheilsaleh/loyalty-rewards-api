<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Requests\CampaignPrize\StoreCampaignPrizeRequest;
use App\Http\Resources\CampaignPrizeResource;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly AuditService $audit,
    ) {}

    /**
     * Campaigns that are running right now, with their prize wheels.
     */
    public function active(): AnonymousResourceCollection
    {
        return CampaignResource::collection($this->campaigns->active());
    }

    /**
     * Create a campaign (admin only).
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaigns->create($request->validated());

        $this->audit->record($this->actor($request), AuditAction::CampaignCreate, $campaign, $request->validated());

        return CampaignResource::make($campaign)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a campaign (admin only).
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        $this->campaigns->update($campaign, $request->validated());

        $this->audit->record($this->actor($request), AuditAction::CampaignUpdate, $campaign, $request->validated());

        return CampaignResource::make($campaign);
    }

    /**
     * Delete a campaign (admin only).
     */
    public function destroy(Request $request, Campaign $campaign): Response
    {
        $this->audit->record($this->actor($request), AuditAction::CampaignDelete, $campaign, ['name' => $campaign->name]);

        $campaign->delete();

        return response()->noContent();
    }

    /**
     * Add a slice to a campaign's spin wheel (admin only).
     */
    public function storePrize(StoreCampaignPrizeRequest $request, Campaign $campaign): JsonResponse
    {
        $prize = $this->campaigns->addPrize($campaign, $request->validated());

        $this->audit->record($this->actor($request), AuditAction::CampaignPrizeCreate, $campaign, $request->validated());

        return CampaignPrizeResource::make($prize)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function actor(Request $request): ?User
    {
        /** @var User|null $user */
        $user = $request->user();

        return $user;
    }
}
