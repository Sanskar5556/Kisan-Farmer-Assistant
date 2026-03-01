<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\PostLike;
use App\Models\UserCropPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// ============================================================
// COMMUNITY CONTROLLER
//
// Social features:
// - Create posts with optional images
// - Like / unlike
// - Comment + reply on posts
// - Filter by crop tag
// ============================================================
class CommunityController extends Controller
{
    /**
     * List all posts (paginated), filter by crop_tag optional
     * GET /api/community/posts?crop_tag=wheat&page=1
     */
    public function index(Request $request)
    {
        $query = Post::with(['user:id,name,district', 'comments' => function($q) {
                $q->with(['user:id,name', 'replies.user:id,name'])->limit(3); // Preview: 3 comments per post
            }])
            ->withCount('likes')            // Add likes_count dynamically
            ->orderBy('created_at', 'desc');

        if ($request->crop_tag) {
            $query->where('crop_tag', $request->crop_tag);
        }

        $posts = $query->paginate(10);

        // Track user viewed this crop if crop_tag is provided
        if ($request->crop_tag && auth()->check()) {
            $this->trackCropInteraction(auth()->id(), $request->crop_tag);
        }

        return response()->json([
            'success' => true,
            'posts'   => $posts,
        ]);
    }

    /**
     * Create a new post
     * POST /api/community/posts
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'body'     => 'required|string',
            'image'    => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'crop_tag' => 'nullable|string|max:100',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('uploads/community', 'public');
        }

        $post = Post::create([
            'user_id'  => auth()->id(),
            'title'    => $request->title,
            'body'     => $request->body,
            'image_path' => $imagePath,
            'crop_tag' => $request->crop_tag ? strtolower($request->crop_tag) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created!',
            'post'    => $post->load('user:id,name'),
        ], 201);
    }

    /**
     * Toggle like (like if not liked, unlike if already liked)
     * POST /api/community/posts/{id}/like
     */
    public function toggleLike($postId)
    {
        $post   = Post::findOrFail($postId);
        $userId = auth()->id();

        $existingLike = PostLike::where('post_id', $postId)->where('user_id', $userId)->first();

        if ($existingLike) {
            // Already liked → unlike
            $existingLike->delete();
            $post->decrement('likes_count');
            $action = 'unliked';
        } else {
            // Not liked → like
            PostLike::create(['post_id' => $postId, 'user_id' => $userId]);
            $post->increment('likes_count');
            $action = 'liked';
        }

        return response()->json([
            'success'     => true,
            'action'      => $action,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    /**
     * Add a comment or reply to a post
     * POST /api/community/posts/{id}/comments
     * Body: body (string), parent_id (optional, for replies)
     */
    public function addComment(Request $request, $postId)
    {
        Post::findOrFail($postId); // Validate post exists

        $request->validate([
            'body'      => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id', // Reply to existing comment
        ]);

        $comment = Comment::create([
            'post_id'   => $postId,
            'user_id'   => auth()->id(),
            'parent_id' => $request->parent_id,
            'body'      => $request->body,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->parent_id ? 'Reply added!' : 'Comment added!',
            'comment' => $comment->load('user:id,name'),
        ], 201);
    }

    /**
     * Delete a post (only the post owner can delete)
     * DELETE /api/community/posts/{id}
     */
    public function destroy($postId)
    {
        $post = Post::findOrFail($postId);

        // Make sure only the post owner can delete
        if ($post->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return response()->json(['success' => true, 'message' => 'Post deleted']);
    }

    private function trackCropInteraction(int $userId, string $cropName): void
    {
        // Uses updateOrCreate to either create a new record or increment the count
        UserCropPreference::updateOrCreate(
            ['user_id' => $userId, 'crop_name' => strtolower($cropName)],
            ['interaction_count' => \DB::raw('interaction_count + 1'), 'last_viewed_at' => now()]
        );
    }
}
