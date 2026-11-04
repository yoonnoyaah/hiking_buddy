<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\PostLike;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommunityPostController extends Controller
{
    // Get all community posts
    public function index()
    {
        $posts = CommunityPost::with([
            'user:id,name,email',
            'likes.user:id,email',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with([
                        'user:id,name,email',
                        'replies.user:id,name,email'
                    ])
                    ->oldest();
            },
        ])
        ->latest()
        ->get()
        ->map(fn ($post) => $this->formatPost($post));

        return response()->json([
            'success' => true,
            'posts' => $posts
        ]);
    }

    // Create a new post
    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:100',
            'body' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (empty(trim($validated['body'] ?? '')) && !$request->hasFile('photo')) {
            return response()->json([
                'message' => 'Please write something or upload a photo.',
            ], 422);
        }

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('community', 'public');
        }

        $post = CommunityPost::create([
            'user_id' => $request->user()->id,
            'topic' => $validated['topic'],
            'body' => $validated['body'] ?? '',
            'photo_path' => $photoPath,
        ]);

        $post->load([
            'user:id,name,email',
            'likes.user:id,email',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with([
                        'user:id,name,email',
                        'replies.user:id,name,email'
                    ])
                    ->oldest();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post published successfully.',
            'post' => $this->formatPost($post),
        ], 201);
    }

    // Edit a post
    public function update(Request $request, $id)
    {
        $post = CommunityPost::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        if ((int) $post->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'You cannot edit another user’s post.',
            ], 403);
        }

        $validated = $request->validate([
            'topic' => 'required|string|max:100',
            'body' => 'nullable|string|max:5000',
        ]);

        if (empty(trim($validated['body'] ?? '')) && !$post->photo_path) {
            return response()->json([
                'message' => 'Post content cannot be empty.',
            ], 422);
        }

        $post->update([
            'topic' => $validated['topic'],
            'body' => $validated['body'] ?? '',
        ]);

       $post->load([
            'user:id,name,email',
            'likes.user:id,email',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                    ->with([
                        'user:id,name,email',
                        'replies.user:id,name,email'
                    ])
                    ->oldest();
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'post' => $this->formatPost($post),
        ]);
    }

    // Delete a post
    public function destroy(Request $request, $id)
    {
        $post = CommunityPost::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $user = $request->user();

        if (
            (int) $post->user_id !== (int) $user->id &&
            $user->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'You are not allowed to delete this post.',
            ], 403);
        }

        if ($post->photo_path) {
            Storage::disk('public')->delete($post->photo_path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    // Like or unlike a post
    public function toggleLike(Request $request, $id)
    {
        $post = CommunityPost::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $like = PostLike::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            PostLike::create([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }

    // Add a comment
    public function addComment(Request $request, $id)
    {
        $post = CommunityPost::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:post_comments,id',
        ]);

        // Only allow replies to top-level comments belonging to this post.
        if (!empty($validated['parent_id'])) {
            $parent = PostComment::where('id', $validated['parent_id'])
                ->where('post_id', $post->id)
                ->whereNull('parent_id')
                ->first();

            if (!$parent) {
                return response()->json([
                    'message' => 'Invalid comment to reply to.'
                ], 422);
            }
        }

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => trim($validated['body']),
        ]);

        $comment->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment' => $this->formatComment($comment)
        ], 201);
    }

    // Convert database data into the format expected by React
  private function formatPost($post)
    {
        return [
            'id' => $post->id,
            'author' => $post->user->name,
            'email' => $post->user->email,
            'createdBy' => $post->user->email,
            'user_id' => $post->user_id,
            'topic' => $post->topic,
            'body' => $post->body,
            'photo' => $post->photo_path
                ? url('storage/' . $post->photo_path)
                : null,
            'likes' => $post->likes->map(
                fn ($like) => $like->user->email
            )->values(),
            'comments' => $post->comments->map(
                fn ($comment) => $this->formatComment($comment)
            )->values(),
            'date' => $post->created_at->toISOString(),
        ];
    }

    private function formatComment($comment)
    {
        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'author' => $comment->user->name,
            'email' => $comment->user->email,
            'body' => $comment->body,
            'date' => $comment->created_at->toISOString(),

            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(
                    fn ($reply) => $this->formatComment($reply)
                )->values()
                : [],
        ];
    }
}