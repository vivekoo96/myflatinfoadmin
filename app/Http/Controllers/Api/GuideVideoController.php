<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GuideVideo;

class GuideVideoController extends Controller
{
    /**
     * Get guide videos for a given app category.
     *
     * POST get-guide-videos
     * Body: { "category": "user_app" }  (optional — omit for all active videos)
     *
     * Categories: user_app | security_app | role_app | building_admin
     */
    public function getGuideVideos(Request $request)
    {
        $request->validate([
            'category' => 'nullable|in:user_app,security_app,role_app,building_admin',
        ]);

        $query = GuideVideo::where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'asc');

        if ($request->filled('category')) {
            // Return videos for this specific category + videos marked for all apps
            $query->where(function ($q) use ($request) {
                $q->where('category', $request->category)
                  ->orWhere('category', 'all');
            });
        }

        $videos = $query->get()->map(function (GuideVideo $v) {
            return [
                'id'           => $v->id,
                'title'        => $v->title,
                'description'  => $v->description,
                'category'     => $v->category,
                'youtube_link' => $v->youtube_link,
                'youtube_id'   => $v->youtube_id,
                'thumbnail'    => $v->thumbnail,
            ];
        });

        return response()->json(['videos' => $videos], 200);
    }
}
