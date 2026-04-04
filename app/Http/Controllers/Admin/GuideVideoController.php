<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideVideo;
use Illuminate\Support\Facades\Auth;

class GuideVideoController extends Controller
{
    /**
     * Display building_admin category videos (read-only).
     */
    public function index()
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $videos = GuideVideo::whereIn('category', ['building_admin', 'all'])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.guide_video.index', compact('videos'));
    }

    private function isAllowed(): bool
    {
        $user = Auth::user();
        return $user && ($user->role === 'BA' || ($user->selectedRole && $user->selectedRole->slug === 'president'));
    }
}
