<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use App\Models\Flat;
use App\Models\BuildingUser;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use \Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $buildingId = Auth::user()->building_id;

        // Previous notifications sent by this admin as broadcast
        $sentNotifications = Notification::where('building_id', $buildingId)
            ->where('from_id', Auth::user()->id)
            ->where('type', 'admin_broadcast')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.notifications.index', compact('sentNotifications'));
    }

    public function create()
    {
        return redirect()->route('notification.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'image'        => 'nullable|image|max:4096',
            'target_roles' => 'required|array|min:1',
        ]);

        $buildingId = Auth::user()->building_id;
        $fromId     = Auth::user()->id;
        $targetRoles = $request->target_roles; // e.g. ['all_flat_users', 'security']

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('notifications', 'public');
        }

        // Collect target user IDs based on selected roles
        $userIds = collect();

        $flats = Flat::where('building_id', $buildingId)->get();

        if (in_array('all_flat_users', $targetRoles)) {
            $userIds = $userIds
                ->merge($flats->whereNotNull('owner_id')->pluck('owner_id'))
                ->merge($flats->whereNotNull('tanent_id')->pluck('tanent_id'));
        } else {
            if (in_array('owners', $targetRoles)) {
                $userIds = $userIds->merge($flats->whereNotNull('owner_id')->pluck('owner_id'));
            }
            if (in_array('tenants', $targetRoles)) {
                $userIds = $userIds->merge($flats->whereNotNull('tanent_id')->pluck('tanent_id'));
            }
        }

        if (in_array('security', $targetRoles)) {
            $ids = BuildingUser::where('building_id', $buildingId)
                ->whereHas('role', fn($q) => $q->where('slug', 'security'))
                ->pluck('user_id');
            $userIds = $userIds->merge($ids);
        }

        if (in_array('issue_management', $targetRoles)) {
            $ids = BuildingUser::where('building_id', $buildingId)
                ->whereHas('role', fn($q) => $q->where('type', 'issue'))
                ->pluck('user_id');
            $userIds = $userIds->merge($ids);
        }

        if (in_array('accounts', $targetRoles)) {
            $ids = BuildingUser::where('building_id', $buildingId)
                ->whereHas('role', fn($q) => $q->where('slug', 'accounts'))
                ->pluck('user_id');
            $userIds = $userIds->merge($ids);
        }

        $userIds = $userIds->unique()->filter()->values();

        // Send notification to each user (saves per-user DB record + sends push)
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen'       => 'Notifications',
            'type'         => 'admin_broadcast',
            'categoryId'   => 'broadcast',
            'channelId'    => 'default',
            'sound'        => 'default',
            'image'        => $imagePath ? asset('storage/' . $imagePath) : '',
        ];

        $result = NotificationHelper::sendBulkNotifications(
            $userIds->toArray(),
            $request->title,
            $request->body,
            $dataPayload,
            [
                'from_id'      => $fromId,
                'building_id'  => $buildingId,
                'type'         => 'admin_broadcast',
            ]
        );

        $msg = "Notification sent to {$result['total_users']} users. ({$result['successful']} success, {$result['failed']} failed, {$result['total_devices']} devices)";

        return redirect()->route('notification.history')->with('success', $msg);
    }

    public function history()
    {
        $buildingId = Auth::user()->building_id;

        $sentNotifications = Notification::where('building_id', $buildingId)
            ->where('from_id', Auth::user()->id)
            ->where('type', 'admin_broadcast')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.notifications.history', compact('sentNotifications'));
    }

    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return view('admin.notifications.show', compact('notification'));
    }

    public function edit($id) {}

    public function update(Request $request, $id) {}

    public function destroy($id) {}

    public function mark_all_as_read(Request $request)
    {
        Notification::where('admin_read', 0)->update(['admin_read' => 1]);
        return redirect()->back()->with('success', 'All notifications marked as read');
    }
}
