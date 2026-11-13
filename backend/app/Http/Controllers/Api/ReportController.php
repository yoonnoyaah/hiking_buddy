<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\GroupTrip;
use App\Models\Report;
use App\Models\User;
use App\Notifications\HikingBuddyNotification;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Members report a community post or group trip.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:community_post,group_trip',
            'target_id' => 'required|integer|min:1',
            'reason' => 'required|string|min:3|max:2000',
        ]);

        if ($validated['type'] === 'community_post') {
            $target = CommunityPost::find($validated['target_id']);

            if (!$target) {
                return response()->json([
                    'success' => false,
                    'message' => 'The community post could not be found.',
                ], 404);
            }

            if ((int) $target->user_id === (int) $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot report your own post.',
                ], 422);
            }

            $targetTitle = $target->topic;
        } else {
            $target = GroupTrip::find($validated['target_id']);

            if (!$target) {
                return response()->json([
                    'success' => false,
                    'message' => 'The group trip could not be found.',
                ], 404);
            }

            if ((int) $target->organizer_id === (int) $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot report your own trip.',
                ], 422);
            }

            $targetTitle = $target->title;
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $validated['type'],
            'reportable_id' => $target->id,
            'reason' => trim($validated['reason']),
            'status' => 'pending',
        ]);

        $isCommunity = $validated['type'] === 'community_post';

        $this->notifyAdmins([
            'category' => 'admin_reports',
            'title' => $isCommunity
                ? 'New community report'
                : 'New trip report',
            'message' => $request->user()->name .
                ' reported: ' . $targetTitle,
            'url' => '?page=reports&tab=' .
                ($isCommunity ? 'community' : 'trips') .
                '&id=' . $report->id,
            'actor_id' => $request->user()->id,
            'actor_name' => $request->user()->name,
            'related_id' => $report->id,
            'related_type' => 'report',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your report has been submitted for admin review.',
            'report' => $report,
        ], 201);
    }

    // Admin: list reports, optionally filtered by type or status.
    public function adminIndex(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $request->validate([
            'type' => 'nullable|in:community_post,group_trip',
            'status' => 'nullable|in:pending,resolved,dismissed',
        ]);

        $query = Report::with([
            'reporter:id,name,email',
            'reviewer:id,name',
        ])->latest();

        if ($request->filled('type')) {
            $query->where('reportable_type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $reports = $query->get()->map(function ($report) {
            $target = null;

            if ($report->reportable_type === 'community_post') {
                $target = CommunityPost::with('user:id,name,email')
                    ->find($report->reportable_id);

                $report->target_title = $target?->topic;
                $report->target_owner = $target?->user?->name;
            }

            if ($report->reportable_type === 'group_trip') {
                $target = GroupTrip::with('organizer:id,name,email')
                    ->find($report->reportable_id);

                $report->target_title = $target?->title;
                $report->target_owner = $target?->organizer?->name;
            }

            $report->target_exists = (bool) $target;

            return $report;
        });

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    // Admin: resolve or dismiss a report.
    public function review(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'status' => 'required|in:resolved,dismissed',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $report = Report::with('reporter')->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found.',
            ], 404);
        }

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This report has already been reviewed.',
            ], 422);
        }

        $report->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($report->reporter) {
            $report->reporter->notify(
                new HikingBuddyNotification([
                    'category' => 'community',
                    'title' => 'Report reviewed',
                    'message' => 'Your report has been ' .
                        $validated['status'] . ' by the admin.',
                    'url' => $report->reportable_type === 'community_post'
                        ? '/community'
                        : '/trails',
                    'actor_id' => $request->user()->id,
                    'actor_name' => $request->user()->name,
                    'related_id' => $report->id,
                    'related_type' => 'report',
                ])
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Report ' . $validated['status'] . ' successfully.',
            'report' => $report->fresh([
                'reporter:id,name,email',
                'reviewer:id,name',
            ]),
        ]);
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