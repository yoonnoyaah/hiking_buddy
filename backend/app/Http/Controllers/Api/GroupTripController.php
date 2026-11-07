<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupTrip;
use App\Models\TripMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Notifications\HikingBuddyNotification;
use App\Models\User;

class GroupTripController extends Controller
{
    // Get all active group trips
    public function index()
    {
        $currentUser = Auth::guard('sanctum')->user();

        $trips = GroupTrip::with('organizer:id,name')
            ->withCount([
                'members as joined_count' => function ($query) {
                    $query->where('status', 'joined');
                }
            ])
            ->withExists([
                'members as is_joined' => function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser?->id ?? 0)
                        ->where('status', 'joined');
                }
            ])
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'trips' => $trips
        ]);
    }

    // Get one trip
    public function show($id)
    {
        $trip = GroupTrip::with([
            'organizer:id,name',
            'members.user:id,name'
        ])
        ->withCount([
            'members as joined_count' => function ($query) {
                $query->where('status', 'joined');
            }
        ])
        ->find($id);

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'trip' => $trip
        ]);
    }

    // Create a group trip
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mountain_id' => 'nullable|exists:mountains,id',
            'mountain_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'difficulty' => 'required|string|max:50',
            'trip_date' => 'required|date|after_or_equal:today',
            'trip_time' => 'required|date_format:H:i',
            'pace' => 'nullable|string|max:50',
            'fitness' => 'nullable|string|max:50',
            'spots' => 'required|integer|min:1|max:100',
            'vehicle' => 'nullable|string|max:255',
            'meeting' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'emergency' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:3000',
        ]);

        $trip = GroupTrip::create([
            ...$validated,
            'organizer_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group trip created successfully.',
            'trip' => $trip->load('organizer:id,name')
        ], 201);
    }

    // Join a trip
    public function join(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $trip = GroupTrip::where('status', 'active')
                ->lockForUpdate()
                ->find($id);

            if (!$trip) {
                return response()->json([
                    'message' => 'Trip not found or unavailable.'
                ], 404);
            }

            $alreadyJoined = TripMember::where('trip_id', $trip->id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'joined')
                ->exists();

            if ($alreadyJoined) {
                return response()->json([
                    'message' => 'You have already joined this trip.'
                ], 422);
            }

            $joinedCount = TripMember::where('trip_id', $trip->id)
                ->where('status', 'joined')
                ->count();

            if ($joinedCount >= $trip->spots) {
                return response()->json([
                    'message' => 'Sorry, this trip is already full.'
                ], 422);
            }

            TripMember::updateOrCreate(
                [
                    'trip_id' => $trip->id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'status' => 'joined',
                    'joined_at' => now(),
                ]
            );

            // Notify the trip organizer
            $actor = $request->user();
            $organizer = User::find($trip->organizer_id);

            if ($organizer && $organizer->id !== $actor->id) {
                $organizer->notify(new HikingBuddyNotification([
                    'category' => 'trails',
                    'title' => 'New trip member',
                    'message' => $actor->name . ' joined your trip: ' . $trip->title,
                    'url' => '/trails',
                    'actor_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'related_id' => $trip->id,
                    'related_type' => 'group_trip',
                ]));
            }

            return response()->json([
                'success' => true,
                'message' => 'You joined the trip successfully.'
            ]);
        });
    }

    // Leave a trip
    public function leave(Request $request, $id)
    {
        $membership = TripMember::where('trip_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'joined')
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'You have not joined this trip.'
            ], 404);
        }

        $membership->update([
            'status' => 'left'
        ]);

        // Notify the trip organizer
        $actor = $request->user();
        $organizer = User::find($trip->organizer_id);

        if ($organizer && $organizer->id !== $actor->id) {
            $organizer->notify(new HikingBuddyNotification([
                'category' => 'trails',
                'title' => 'Member left your trip',
                'message' => $actor->name . ' left your trip: ' . $trip->title,
                'url' => '/trails',
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'related_id' => $trip->id,
                'related_type' => 'group_trip',
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => 'You left the trip successfully.'
        ]);
    }

    // Delete a trip (organizer or admin only)
    public function destroy(Request $request, $id)
    {
        $trip = GroupTrip::find($id);

        if (!$trip) {
            return response()->json([
                'message' => 'Trip not found.'
            ], 404);
        }

        $user = $request->user();

        if (
            $trip->organizer_id !== $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'You are not allowed to delete this trip.'
            ], 403);
        }

        // Notify all joined members before deleting the trip
        $memberIds = TripMember::where('group_trip_id', $trip->id)
            ->where('status', 'joined')
            ->pluck('user_id')
            ->unique();

        $organizer = $request->user();

        $members = User::whereIn('id', $memberIds)
            ->where('id', '!=', $organizer->id)
            ->get();

        foreach ($members as $member) {
            $member->notify(new HikingBuddyNotification([
                'category' => 'trails',
                'title' => 'Trip cancelled',
                'message' => 'The trip "' . $trip->title . '" has been cancelled.',
                'url' => '/trails',
                'actor_id' => $organizer->id,
                'actor_name' => $organizer->name,
                'related_id' => $trip->id,
                'related_type' => 'group_trip',
            ]));
}

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully.'
        ]);
    }
}
