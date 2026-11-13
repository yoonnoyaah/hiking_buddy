<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupTrip;
use App\Models\TripMember;
use App\Models\LocationRequest;
use App\Models\User;
use App\Notifications\HikingBuddyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroupTripController extends Controller
{
    // --------------------------------------------------
    // GET ALL ACTIVE GROUP TRIPS
    // --------------------------------------------------

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


    // --------------------------------------------------
    // GET ONE GROUP TRIP
    // --------------------------------------------------

    public function show(Request $request, $id)
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
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }

        $user = $request->user();

        $isOwner = $user && $trip->organizer_id == $user->id;
        $isAdmin = $user && strtolower((string) $user->role) === 'admin';

        // Hide unpublished trips from other members.
        if ($trip->status !== 'active' && !$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'trip' => $trip
        ]);
    }


    // --------------------------------------------------
    // CREATE A GROUP TRIP
    // --------------------------------------------------

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

        $mountainName = trim($validated['mountain_name']);
        $regionName = trim($validated['region']);

        /*
         * Find location requests related to this trip.
         * A trip must wait if its mountain or region
         * request is still pending.
         */

        $locationRequests = LocationRequest::where(function ($query) use (
            $mountainName,
            $regionName
        ) {
            $query->where(function ($q) use ($mountainName, $regionName) {
                $q->where('type', 'mountain')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($mountainName)])
                    ->whereRaw('LOWER(region) = ?', [mb_strtolower($regionName)]);
            })
            ->orWhere(function ($q) use ($regionName) {
                $q->where('type', 'region')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($regionName)]);
            });
        })->get();

        // Rejected location requests prevent publication.
        if ($locationRequests->contains('status', 'rejected')) {
            $tripStatus = 'rejected';
        }
        // Pending location requests keep the trip unpublished.
        elseif ($locationRequests->contains('status', 'pending')) {
            $tripStatus = 'pending';
        }
        // No pending or rejected request: publish normally.
        else {
            $tripStatus = 'active';
        }

        $trip = GroupTrip::create([
            ...$validated,
            'organizer_id' => $request->user()->id,
            'status' => $tripStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => $tripStatus === 'pending'
                ? 'Your trip has been submitted and is waiting for location approval.'
                : ($tripStatus === 'rejected'
                    ? 'Your trip cannot be published because a required location was rejected.'
                    : 'Group trip created successfully.'),
            'trip' => $trip->load('organizer:id,name')
        ], 201);
    }


    // --------------------------------------------------
    // JOIN A GROUP TRIP
    // --------------------------------------------------

    public function join(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $trip = GroupTrip::lockForUpdate()->find($id);

            if (!$trip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trip not found.'
                ], 404);
            }

            if ($trip->trip_date->toDateString() < today()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This trip has expired and can no longer be joined.'
                ], 422);
            }

            if ($trip->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'This trip is not available for joining yet.'
                ], 422);
            }

            $alreadyJoined = TripMember::where('trip_id', $trip->id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'joined')
                ->exists();

            if ($alreadyJoined) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already joined this trip.'
                ], 422);
            }

            $joinedCount = TripMember::where('trip_id', $trip->id)
                ->where('status', 'joined')
                ->count();

            if ($joinedCount >= $trip->spots) {
                return response()->json([
                    'success' => false,
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


    // --------------------------------------------------
    // LEAVE A GROUP TRIP
    // --------------------------------------------------

    public function leave(Request $request, $id)
    {
        $trip = GroupTrip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }

        $membership = TripMember::where('trip_id', $trip->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'joined')
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'You have not joined this trip.'
            ], 404);
        }

        $membership->update([
            'status' => 'left'
        ]);

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


    // --------------------------------------------------
    // DELETE A GROUP TRIP
    // ORGANIZER OR ADMIN ONLY
    // --------------------------------------------------

    public function destroy(Request $request, $id)
    {
        $trip = GroupTrip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }

        $user = $request->user();

        if (
            $trip->organizer_id !== $user->id &&
            strtolower((string) $user->role) !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to delete this trip.'
            ], 403);
        }

        DB::transaction(function () use ($trip, $user) {

            $memberIds = TripMember::where('trip_id', $trip->id)
                ->where('status', 'joined')
                ->pluck('user_id')
                ->unique();

            $members = User::whereIn('id', $memberIds)
                ->where('id', '!=', $user->id)
                ->get();

            foreach ($members as $member) {
                $member->notify(new HikingBuddyNotification([
                    'category' => 'trails',
                    'title' => 'Trip cancelled',
                    'message' => 'The trip "' . $trip->title . '" has been cancelled.',
                    'url' => '/trails',
                    'actor_id' => $user->id,
                    'actor_name' => $user->name,
                    'related_id' => $trip->id,
                    'related_type' => 'group_trip',
                ]));
            }

            TripMember::where('trip_id', $trip->id)->delete();

            $trip->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully.'
        ]);
    }


    // --------------------------------------------------
    // ADMIN: GET ALL GROUP TRIPS
    // --------------------------------------------------

    public function adminIndex(Request $request)
    {
        if (!$request->user() || strtolower((string) $request->user()->role) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $trips = GroupTrip::with('organizer:id,name,email')
            ->withCount([
                'members as joined_count' => function ($query) {
                    $query->where('status', 'joined');
                }
            ])
            ->latest()
            ->get();

        $trips->transform(function ($trip) {

            if ($trip->status === 'cancelled') {
                $effectiveStatus = 'Cancelled';
            } elseif ($trip->status === 'pending') {
                $effectiveStatus = 'Pending approval';
            } elseif ($trip->status === 'rejected') {
                $effectiveStatus = 'Rejected';
            } elseif ($trip->trip_date->toDateString() < today()->toDateString()) {
                $effectiveStatus = 'Expired';
            } elseif ($trip->status === 'active') {
                $effectiveStatus = 'Active';
            } else {
                $effectiveStatus = ucfirst($trip->status);
            }

            $trip->effective_status = $effectiveStatus;

            return $trip;
        });

        return response()->json([
            'success' => true,
            'trips' => $trips
        ]);
    }


    // --------------------------------------------------
    // ADMIN: DELETE A GROUP TRIP
    // --------------------------------------------------

    public function adminDestroy(Request $request, $id)
    {
        if (!$request->user() || strtolower((string) $request->user()->role) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $trip = GroupTrip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.'
            ], 404);
        }

        DB::transaction(function () use ($trip, $request) {

            $memberIds = TripMember::where('trip_id', $trip->id)
                ->where('status', 'joined')
                ->pluck('user_id')
                ->unique();

            $members = User::whereIn('id', $memberIds)
                ->where('id', '!=', $request->user()->id)
                ->get();

            foreach ($members as $member) {
                $member->notify(new HikingBuddyNotification([
                    'category' => 'trails',
                    'title' => 'Trip cancelled',
                    'message' => 'The trip "' . $trip->title . '" has been cancelled by an administrator.',
                    'url' => '/trails',
                    'actor_id' => $request->user()->id,
                    'actor_name' => $request->user()->name,
                    'related_id' => $trip->id,
                    'related_type' => 'group_trip',
                ]));
            }

            TripMember::where('trip_id', $trip->id)->delete();

            $trip->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Trip removed successfully.'
        ]);
    }
}
