<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use App\Models\Flat;
use App\Models\BuildingUser;
use App\Models\UserDevice;
use App\Services\FCMService;
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

        // Get active FCM tokens for targeted users
        $tokens = UserDevice::whereIn('user_id', $userIds)
            ->whereNotNull('fcm_token')
            ->where('is_active', 1)
            ->pluck('fcm_token')
            ->toArray();

        // Send via FCM v1
        $fcmResult = ['success' => 0, 'failure' => 0];
        if (!empty($tokens)) {
            $fcmService = new FCMService();
            $data = [
                'screen' => 'Notifications',
                'type'   => 'admin_broadcast',
                'image'  => $imagePath ? asset('storage/' . $imagePath) : '',
            ];
            $fcmResult = $fcmService->sendToMultipleDevices($tokens, $request->title, $request->body, $data);
        }

        // Save one broadcast record (not per-user, just a log entry)
        Notification::create([
            'user_id'      => 0,
            'from_id'      => $fromId,
            'building_id'  => $buildingId,
            'title'        => $request->title,
            'body'         => $request->body,
            'image'        => $imagePath,
            'target_roles' => $targetRoles,
            'type'         => 'admin_broadcast',
            'status'       => 1,
            'admin_read'   => 1,
            'dataPayload'  => json_encode([]),
        ]);

        $userCount = $userIds->count();
        $msg = "Notification sent to {$userCount} users. (FCM: {$fcmResult['success']} success, {$fcmResult['failure']} failed)";

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
