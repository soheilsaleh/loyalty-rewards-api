<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Requests\CampaignPrize\StoreCampaignPrizeRequest;
use App\Http\Resources\CampaignPrizeResource;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns) {}

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

        return CampaignResource::make($campaign)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a campaign (admin only).
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): CampaignResource
    {
        return CampaignResource::make(
            $this->campaigns->update($campaign, $request->validated()),
        );
    }

    /**
     * Delete a campaign (admin only).
     */
    public function destroy(Campaign $campaign): Response
    {
        $campaign->delete();

        return response()->noContent();
    }

    /**
     * Add a slice to a campaign's spin wheel (admin only).
     */
    public function storePrize(StoreCampaignPrizeRequest $request, Campaign $campaign): JsonResponse
    {
        $prize = $this->campaigns->addPrize($campaign, $request->validated());

        return CampaignPrizeResource::make($prize)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
