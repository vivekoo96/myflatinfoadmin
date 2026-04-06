<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoModule;
use App\Models\VideoTutorial;
use Illuminate\Http\Request;

class VideoTutorialController extends Controller
{
    // POST /otherfun/video_tutorials/create_module
    public function createModule(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $module = VideoModule::create(['title' => $request->title]);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
            'data'    => $module,
        ], 201);
    }

    // GET /otherfun/video_tutorials/create_module
    public function getModules()
    {
        $modules = VideoModule::orderBy('created_at', 'asc')->get();

        return response()->json(['success' => true, 'data' => $modules]);
    }

    // POST /otherfun/video_tutorials/post_video
    public function postVideo(Request $request)
    {
        $request->validate([
            'moduleId'   => 'required|exists:video_modules,id',
            'title'      => 'required|string|max:255',
            'text'       => 'nullable|string',
            'videoUrl'   => 'required|string',
            'videoType'  => 'nullable|string',
            'interfaces' => 'nullable|array',
            'interfaces.*' => 'string',
        ]);

        $video = VideoTutorial::create([
            'module_id'  => $request->moduleId,
            'title'      => $request->title,
            'text'       => $request->text,
            'video_url'  => $request->videoUrl,
            'video_type' => $request->videoType ?? 'youtube',
            'interfaces' => $request->interfaces,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video tutorial created successfully',
            'data'    => $video->load('module'),
        ], 201);
    }

    // GET /otherfun/video_tutorials/post_video
    public function getVideos(Request $request)
    {
        $limit  = (int) ($request->query('limit', 10));
        $page   = (int) ($request->query('page', 1));
        $search = $request->query('search');
        $role   = $request->query('role');

        $query = VideoTutorial::with('module');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('text', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->whereJsonContains('interfaces', $role);
        }

        $total  = $query->count();
        $videos = $query->orderBy('created_at', 'asc')
                        ->skip(($page - 1) * $limit)
                        ->take($limit)
                        ->get();

        return response()->json([
            'success' => true,
            'data'    => $videos,
            'meta'    => [
                'total'        => $total,
                'per_page'     => $limit,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $limit),
            ],
        ]);
    }
}
