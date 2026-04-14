<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoModule;
use Illuminate\Support\Facades\Auth;

class GuideVideoController extends Controller
{
    /**
     * Display video tutorials grouped by category (read-only).
     */
    public function index()
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $modules = VideoModule::with(['videos' => function ($q) {
            $q->where(function ($query) {
                $query->whereJsonContains('interfaces', 'BA')
                      ->orWhereJsonContains('interfaces', 'user')
                      ->orWhereNull('interfaces');
            })->orderBy('created_at', 'asc');
        }])->orderBy('created_at', 'asc')->get();

        return view('admin.guide_video.index', compact('modules'));
    }

    private function isAllowed(): bool
    {
        $user = Auth::user();
        return $user && ($user->role === 'BA' || ($user->selectedRole && $user->selectedRole->slug === 'president'));
    }
}
