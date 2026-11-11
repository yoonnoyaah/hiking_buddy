<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMemberController extends Controller
{
    private function isAdmin(Request $request)
    {
        return $request->user() && $request->user()->role === 'admin';
    }

    public function index(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $members = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'joined' => $user->created_at?->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    public function warnings(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $member = User::findOrFail($id);

        $warnings = DB::table('member_warnings')
            ->leftJoin('users as admins', 'member_warnings.admin_id', '=', 'admins.id')
            ->where('member_warnings.user_id', $member->id)
            ->select(
                'member_warnings.id',
                'member_warnings.reason',
                'member_warnings.created_at',
                'admins.name as admin_name'
            )
            ->latest('member_warnings.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'member' => $member->only([
                'id',
                'name',
                'email',
                'role',
                'status',
            ]),
            'warnings' => $warnings,
        ]);
    }

    public function warn(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $member = User::findOrFail($id);

        if ($member->role === 'admin' || $member->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Administrator accounts cannot receive member warnings.',
            ], 403);
        }

        if ($member->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'This member is already banned.',
            ], 422);
        }

        $reason = trim($validated['reason']);

        DB::transaction(function () use ($member, $request, $reason) {
            DB::table('member_warnings')->insert([
                'user_id' => $member->id,
                'admin_id' => $request->user()->id,
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $member->notify(new AccountStatusNotification(
                'account',
                'Account Warning',
                'You have received an account warning. Reason: ' . $reason
            ));
        });

        return response()->json([
            'success' => true,
            'message' => 'Warning issued successfully.',
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,banned',
        ]);

        $member = User::findOrFail($id);

        if ($member->role === 'admin' || $member->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Administrator accounts cannot be banned or changed here.',
            ], 403);
        }

        $newStatus = $validated['status'];
        $oldStatus = $member->status;

        if ($newStatus === $oldStatus) {
            return response()->json([
                'success' => true,
                'message' => 'This member already has this status.',
                'member' => [
                    'id' => $member->id,
                    'status' => $member->status,
                ],
            ]);
        }

        if ($newStatus === 'banned') {
            $hasWarning = DB::table('member_warnings')
                ->where('user_id', $member->id)
                ->exists();

            if (!$hasWarning) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please issue a warning before banning this member.',
                ], 422);
            }
        }

        DB::transaction(function () use ($member, $newStatus) {
            $member->status = $newStatus;
            $member->save();

            if ($newStatus === 'banned') {
                $member->notify(new AccountStatusNotification(
                    'account',
                    'Account Banned',
                    'Your account has been banned by an administrator. You will be signed out. Please contact the administrator if you believe this is a mistake.'
                ));
            } else {
                $member->notify(new AccountStatusNotification(
                    'account',
                    'Account Reactivated',
                    'Your account has been reactivated. You can now log in again.'
                ));
            }
        });

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'banned'
                ? 'Member banned successfully.'
                : 'Member unbanned successfully.',
            'member' => [
                'id' => $member->id,
                'status' => $member->status,
            ],
        ]);
    }
}
