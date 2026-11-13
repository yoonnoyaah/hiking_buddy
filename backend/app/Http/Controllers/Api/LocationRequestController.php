<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationRequest;
use App\Models\Mountain;
use App\Models\GroupTrip;
use App\Models\User;
use App\Notifications\HikingBuddyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationRequestController extends Controller
{
    // Members submit a mountain or region request.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mountain,region',
            'name' => 'required|string|max:150',
            'region' => 'nullable|string|max:150',
            'difficulty' => 'nullable|in:Easy,Moderate,Hard',
            'description' => 'nullable|string|max:2000',
        ]);

        if ($validated['type'] === 'mountain' && empty($validated['region'])) {
            return response()->json([
                'success' => false,
                'message' => 'A region is required for a mountain request.',
            ], 422);
        }

        if ($validated['type'] === 'mountain') {
            $alreadyExists = Mountain::whereRaw(
                'LOWER(name) = ?',
                [mb_strtolower(trim($validated['name']))]
            )->whereRaw(
                'LOWER(region) = ?',
                [mb_strtolower(trim($validated['region']))]
            )->exists();

            if ($alreadyExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This mountain already exists in that region.',
                ], 422);
            }
        }

        $locationRequest = LocationRequest::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'name' => trim($validated['name']),
            'region' => $validated['type'] === 'mountain'
                ? trim($validated['region'])
                : null,
            'difficulty' => $validated['difficulty'] ?? 'Moderate',
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        $requestType = $validated['type'];

        $adminUrl = $requestType === 'mountain'
            ? '?page=location-approvals&tab=mountains&id=' . $locationRequest->id
            : '?page=location-approvals&tab=regions&id=' . $locationRequest->id;

        $this->notifyAdmins([
            'category' => 'admin_location',
            'title' => 'New ' . $requestType . ' request',
            'message' => $request->user()->name .
                ' submitted a ' . $requestType . ' request: ' .
                $locationRequest->name,
            'url' => $adminUrl,
            'actor_id' => $request->user()->id,
            'actor_name' => $request->user()->name,
            'related_id' => $locationRequest->id,
            'related_type' => 'location_request',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your location request has been submitted for admin review.',
            'location_request' => $locationRequest,
        ], 201);
    }

    // Admin: list mountain or region requests.
    public function adminIndex(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $query = LocationRequest::with([
            'user:id,name,email',
            'reviewer:id,name',
        ])->latest();

        if ($request->filled('type')) {
            $request->validate([
                'type' => 'in:mountain,region',
            ]);

            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $request->validate([
                'status' => 'in:pending,approved,rejected',
            ]);

            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'requests' => $query->get(),
        ]);
    }

    // Admin: approve or reject a location request.
    public function review(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $locationRequest = LocationRequest::with('user')->find($id);

        if (!$locationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Location request not found.',
            ], 404);
        }

        if ($locationRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been reviewed.',
            ], 422);
        }

        DB::transaction(function () use ($request, $validated, $locationRequest) {

            // Create or activate the mountain when approved.
            if (
                $validated['status'] === 'approved' &&
                $locationRequest->type === 'mountain'
            ) {
                $existing = Mountain::whereRaw(
                    'LOWER(name) = ?',
                    [mb_strtolower($locationRequest->name)]
                )->whereRaw(
                    'LOWER(region) = ?',
                    [mb_strtolower($locationRequest->region)]
                )->first();

                if ($existing) {
                    $existing->update(['is_active' => true]);
                } else {
                    Mountain::create([
                        'name' => $locationRequest->name,
                        'region' => $locationRequest->region,
                        'difficulty' => $locationRequest->difficulty ?: 'Moderate',
                        'description' => $locationRequest->description,
                        'is_active' => true,
                    ]);
                }
            }

            $locationRequest->update([
                'status' => $validated['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['status'] === 'rejected'
                    ? ($validated['rejection_reason'] ?? null)
                    : null,
            ]);

            // Find trips that depend on this location request.
            $trips = GroupTrip::where('status', 'pending')
                ->where(function ($query) use ($locationRequest) {
                    if ($locationRequest->type === 'mountain') {
                        $query->whereRaw(
                            'LOWER(mountain_name) = ?',
                            [mb_strtolower($locationRequest->name)]
                        )->whereRaw(
                            'LOWER(region) = ?',
                            [mb_strtolower($locationRequest->region ?? '')]
                        );
                    } else {
                        $query->whereRaw(
                            'LOWER(region) = ?',
                            [mb_strtolower($locationRequest->name)]
                        );
                    }
                })
                ->with('organizer')
                ->get();

            foreach ($trips as $trip) {
                $requiredRequests = LocationRequest::where(function ($query) use ($trip) {
                    $query->where(function ($q) use ($trip) {
                        $q->where('type', 'mountain')
                            ->whereRaw(
                                'LOWER(name) = ?',
                                [mb_strtolower($trip->mountain_name)]
                            )
                            ->whereRaw(
                                'LOWER(region) = ?',
                                [mb_strtolower($trip->region)]
                            );
                    })->orWhere(function ($q) use ($trip) {
                        $q->where('type', 'region')
                            ->whereRaw(
                                'LOWER(name) = ?',
                                [mb_strtolower($trip->region)]
                            );
                    });
                })->get();

                if ($requiredRequests->contains('status', 'rejected')) {
                    $trip->update(['status' => 'rejected']);

                    $this->notifyOrganizer(
                        $trip,
                        $request,
                        'Trip location rejected',
                        'Your trip "' . $trip->title .
                            '" could not be published because a required location was rejected.'
                    );

                    continue;
                }

                if (
                    $requiredRequests->isNotEmpty() &&
                    $requiredRequests->every(fn ($item) => $item->status === 'approved')
                ) {
                    $trip->update(['status' => 'active']);

                    $this->notifyOrganizer(
                        $trip,
                        $request,
                        'Your trip is now published',
                        'Your trip "' . $trip->title .
                            '" has been approved and is now visible to other members.'
                    );
                }
            }

            // Notify the member who submitted the location request.
            if ($locationRequest->user) {
                $locationRequest->user->notify(
                    new HikingBuddyNotification([
                        'category' => 'trails',
                        'title' => $locationRequest->type === 'mountain'
                            ? 'Mountain request reviewed'
                            : 'Region request reviewed',
                        'message' => $locationRequest->name .
                            ' was ' . $validated['status'] . ' by the admin.',
                        'url' => '/trails',
                        'actor_id' => $request->user()->id,
                        'actor_name' => $request->user()->name,
                        'related_id' => $locationRequest->id,
                        'related_type' => 'location_request',
                    ])
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Location request ' . $validated['status'] . ' successfully.',
            'location_request' => $locationRequest->fresh([
                'user:id,name,email',
                'reviewer:id,name',
            ]),
        ]);
    }

    // Public: return available regions for trip creation.
    public function regions()
    {
        $mountainRegions = Mountain::where('is_active', true)
            ->whereNotNull('region')
            ->pluck('region');

        $approvedRegions = LocationRequest::where('type', 'region')
            ->where('status', 'approved')
            ->pluck('name');

        $regions = $mountainRegions
            ->merge($approvedRegions)
            ->map(fn ($region) => trim($region))
            ->filter()
            ->unique(fn ($region) => mb_strtolower($region))
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'regions' => $regions,
        ]);
    }

    private function notifyOrganizer($trip, Request $request, string $title, string $message): void
    {
        if (!$trip->organizer) {
            return;
        }

        $trip->organizer->notify(
            new HikingBuddyNotification([
                'category' => 'trails',
                'title' => $title,
                'message' => $message,
                'url' => '/trails',
                'actor_id' => $request->user()->id,
                'actor_name' => $request->user()->name,
                'related_id' => $trip->id,
                'related_type' => 'group_trip',
            ])
        );
    }

    private function notifyAdmins(array $data): void
    {
        User::where('role', 'admin')->get()->each(function ($admin) use ($data) {
            $admin->notify(new HikingBuddyNotification($data));
        });
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user() &&
            strtolower((string) $request->user()->role) === 'admin';
    }

    private function forbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'Administrator access is required.',
        ], 403);
    }
}
