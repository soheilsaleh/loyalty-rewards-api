<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reward\StoreRewardRequest;
use App\Http\Requests\Reward\UpdateRewardRequest;
use App\Http\Resources\RewardResource;
use App\Models\Reward;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RewardController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * The active reward catalog, cheapest first.
     */
    public function index(): AnonymousResourceCollection
    {
        $rewards = Reward::query()
            ->where('is_active', true)
            ->orderBy('cost_points')
            ->cursorPaginate(15);

        return RewardResource::collection($rewards);
    }

    /**
     * Add a reward to the catalog (admin only).
     */
    public function store(StoreRewardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $reward = Reward::create($data);

        /** @var User $actor */
        $actor = $request->user();
        $this->audit->record($actor, AuditAction::RewardCreate, $reward, $data);

        return RewardResource::make($reward)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a reward (admin only).
     */
    public function update(UpdateRewardRequest $request, Reward $reward): RewardResource
    {
        $reward->update($request->validated());

        /** @var User $actor */
        $actor = $request->user();
        $this->audit->record($actor, AuditAction::RewardUpdate, $reward, $request->validated());

        return RewardResource::make($reward);
    }

    /**
     * Remove a reward from the catalog (admin only).
     */
    public function destroy(Request $request, Reward $reward): Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->audit->record($actor, AuditAction::RewardDelete, $reward, ['name' => $reward->name]);

        $reward->delete();

        return response()->noContent();
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);

        while (Reward::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($name).'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
