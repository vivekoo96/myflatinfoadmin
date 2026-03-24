<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\User;
use App\Models\BuildingUser;
use App\Models\Classified;
use App\Models\ClassifiedBuilding;
use App\Models\ClassifiedPhoto;
use App\Models\Flat;
use App\Models\Block;
use App\Models\Setting;
use App\Models\Notification as DatabaseNotification;
use App\Services\FCMService;
use App\Jobs\SendClassifiedNotification;
use App\Helpers\NotificationHelper2;

use \Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


use DB;
use \Session;
use Mail;
use \Str;
use \Log;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestException;

class ClassifiedController extends Controller
{

    public function __construct()
    {
        $rdata = Setting::findOrFail(1);
        $this->keyId = $rdata->razorpay_key;
        $this->keySecret = $rdata->razorpay_secret;
        $this->displayCurrency = 'INR';
        $this->api = new Api($this->keyId, $this->keySecret);
    }
    public function index()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::User()->hasPermission('custom.classifieds') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        $buildingId = $building->id;

        // Get classifieds for current building AND global classifieds (building_id = 0)
        // Also consider classifieds linked via classified_buildings table (for BA-created "All Buildings" entries)
        $classifieds = \App\Models\Classified::withTrashed()
            ->where(function($query) use ($buildingId) {
                $query->where('building_id', $buildingId)
                      ->orWhere('building_id', 0)
                      ->orWhereIn('id', function($sub) use ($buildingId) {
                          $sub->select('classified_id')->from('classified_buildings')->where('building_id', $buildingId);
                      });
            })
            ->with(['user', 'building', 'block', 'flat', 'photos'])
            ->get();

        // Manually assign classifieds to building object for the view
        $building->setRelation('classifieds', $classifieds);

        // Compute used counts using building-configured month windows (within_for_month, all_for_month)
        $now = Carbon::now();
        $withinMonthSpan = intval($building->within_for_month ?? 1);
        $allMonthSpan = intval($building->all_for_month ?? 1);

        // Ensure at least 1 month span
        $withinMonthSpan = max(1, $withinMonthSpan);
        $allMonthSpan = max(1, $allMonthSpan);

        $startOfWithinWindow = $now->copy()->subMonths($withinMonthSpan - 1)->startOfMonth();
        $startOfAllWindow = $now->copy()->subMonths($allMonthSpan - 1)->startOfMonth();
        $endOfWindow = $now->copy()->endOfMonth();

        // SIMPLE COUNT LOGIC - PER-USER QUOTA (each user has individual limit)
        $userId = Auth::id();
        
        // Within Building - CURRENT USER'S COUNT
        $within_used_user = \App\Models\Classified::where('building_id', $buildingId)
            ->where('category', 'Within Building')
            ->where('status', 'Approved')
            ->where('is_approved_on_creation', true)
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startOfWithinWindow, $endOfWindow])
            ->count();

        // All Buildings - CURRENT USER'S COUNT
        $all_used_user = \App\Models\Classified::where('building_id', $buildingId)
            ->where('category', 'All Buildings')
            ->where('status', 'Approved')
            ->where('is_approved_on_creation', true)
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startOfAllWindow, $endOfWindow])
            ->count();
        
        // Compute remaining quotas from DB limits (null => unlimited)
        // Each user has their own individual quota (not shared with other users)
        $within_limit = isset($building->classified_limit_within_building) ? $building->classified_limit_within_building : null;
        $all_limit = isset($building->classified_limit_all_building) ? $building->classified_limit_all_building : null;

        $within_remaining = is_null($within_limit) ? null : max(0, intval($within_limit) - intval($within_used_user));
        $all_remaining = is_null($all_limit) ? null : max(0, intval($all_limit) - intval($all_used_user));

        // Provide variables compatible with view expectations
        // Note: with per-user quotas, "used_total" is actually just the user's count
        $within_used_total = $within_used_user;
        $all_used_total = $all_used_user;

        return view('admin.classified.index',compact('building','within_used_total','all_used_total','within_remaining','all_remaining','within_used_user','all_used_user'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasPermission('custom.classifieds') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        // NOTE: $request->block_id may be 'all' or a specific block id
        // We'll persist block_id = 0 for "all" and use persisted values for notifications.

        $request['block_id'] = "all";

        $rules = [
            'building_id' => 'required|exists:buildings,id',
            'block_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === 'all') {
                        return; // "all" is valid
                    }
                    if (!Block::where('id', $value)->exists()) {
                        $fail('The selected block is invalid.');
                    }
                }
            ],
            'category' => [
                'required',
                function ($attribute, $value, $fail) {
                    $building = Auth::user()->building;
                    if ($value === 'Within Building' && !$building->hasPermission('Classified for withinbuilding')) {
                        $fail('You do not have permission to create Within Building classifieds.');
                    }
                    if ($value === 'All Buildings' && !$building->hasPermission('Classified for all buildings')) {
                        $fail('You do not have permission to create All Buildings classifieds.');
                    }
                    if (!in_array($value, ['Within Building', 'All Buildings'])) {
                        $fail('The selected category is invalid.');
                    }
                }
            ],
            'title' => 'required|string|max:255',
            'desc' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120',
           'status' => 'required|in:Pending,Approved,Rejected,Send For Editing',
        ];
    
        $msg = 'Classified added Susccessfully';
        $classified = new Classified();
    
        $originalValues = null;
        if ($request->id) {
            $classified = Classified::withTrashed()->find($request->id);
            $originalValues = $classified ? $classified->getOriginal() : null;
            $msg = 'Classified updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        // SERVER-SIDE QUOTA CHECKS (dynamic computed from DB limits)
        // Treat: null => unlimited, integer >=0 => configured quota
        if (!$request->id) { // only for new classifieds
            $now = Carbon::now();
            $b = Building::find($request->building_id);
            $withinMonths = intval($b->within_for_month ?? 1);
            $allMonths = intval($b->all_for_month ?? 1);
            $withinMonths = max(1, $withinMonths);
            $allMonths = max(1, $allMonths);
            $startOfWithinWindow = $now->copy()->subMonths($withinMonths - 1)->startOfMonth();
            $startOfAllWindow = $now->copy()->subMonths($allMonths - 1)->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            if ($request->category === 'Within Building') {
                $b = Building::find($request->building_id);
                if ($b && !is_null($b->classified_limit_within_building)) {
                    // Count within-building classifieds for this building for current month
                    $used_within = \App\Models\Classified::where('category', 'Within Building')
                        ->where('status', 'Approved')
                        ->where('user_id',Auth::User()->id)
                        ->where('is_approved_on_creation', true)
                        ->whereBetween('created_at', [$startOfWithinWindow, $endOfMonth])
                        ->where(function($q) use ($b) {
                            $q->where('building_id', $b->id)
                              ->orWhere('building_id', 0)
                              ->orWhereIn('id', function($sub) use ($b) {
                                  $sub->select('classified_id')->from('classified_buildings')->where('building_id', $b->id);
                              });
                        })->count();

                    if ($used_within >= intval($b->classified_limit_within_building)) {
                        return redirect()->back()->with('error', 'Within-building quota exhausted for this building.');
                    }
                }
            }

            if ($request->category === 'All Buildings') {
                // For BA users, check their building's "all" quota before allowing creation
                if (Auth::User()->role == 'BA') {
                    $b = Building::find($request->building_id);
                    if ($b && !is_null($b->classified_limit_all_building)) {
                        // Count all-buildings classifieds that apply to this building for current month
                        $used_all = \App\Models\Classified::where('category', 'All Buildings')
                                ->where('status', 'Approved')
                                ->where('is_approved_on_creation', true)
                                ->where('user_id',Auth::User()->id)
                               ->whereNull('deleted_at')
                                ->whereBetween('created_at', [$startOfAllWindow, $endOfMonth])
                                ->where(function($q) use ($b) {
                                    $q->where('building_id', $b->id)
                                      ->orWhere('building_id', 0)
                                      ->orWhereIn('id', function($sub) use ($b) {
                                          $sub->select('classified_id')->from('classified_buildings')->where('building_id', $b->id);
                                      });
                                })->count();
                          
                        if ($used_all >= intval($b->classified_limit_all_building)) {
                            //   dd($b->classified_limit_all_building);
                            return redirect()->back()->with('error', 'All-buildings quota exhausted for your building.');
                            
                        }
                    }
                }
                // Super admin (building_id = 0) is allowed to create global posts without this check
            }
        }
        // Set basic classified data
        $classified->title = $request->title;
        $classified->desc = $request->desc;
        $classified->category = $request->category;
        // dd($request->all());
        // Preserve original creator when updating; when creating, set creator to request user if provided, otherwise current auth user
        if ($request->id) {
            // editing existing classified - ALWAYS preserve the original poster's user_id
            // The modal passes user_id in hidden field to ensure quota goes to original poster
            $originalUserId = $originalValues['user_id'] ?? null;
            
            // CRITICAL: Only override user_id if provided in request AND differs from original
            if ($request->filled('user_id') && $request->user_id != $originalUserId) {
                $classified->user_id = $request->user_id;
                \Log::warning('Classified poster changed during update', [
                    'classified_id' => $classified->id ?? null,
                    'original_user_id' => $originalUserId,
                    'final_user_id' => $request->user_id,
                    'editor_id' => Auth::id()
                ]);
            } else {
                // Keep the original poster - this is the DEFAULT and CORRECT behavior
                // When BA approves someone's post, the ORIGINAL POSTER gets the quota
                $classified->user_id = $originalUserId;
            }
        } else {
            // creating new - if request provides a non-empty user_id (creating on behalf), use it; otherwise use authenticated user
            $classified->user_id = $request->filled('user_id') ? $request->user_id : Auth::User()->id;
        }
        $classified->status = $request->status;
        
        // Handle building_id based on category and user role
        if ($request->category === 'All Buildings') {
            if (Auth::User()->role == 'BA') {
                // Building Admin: save their building_id even for "All Buildings"
                $classified->building_id = $request->building_id;
            } else {
                // Super Admin: 0 indicates all buildings
                $classified->building_id = 0;
            }
        } else {
            $classified->building_id = $request->building_id;
        }
        // dd($request->all());
        // Handle block_id - store the actual value or 0 for "all"
        if ($request->block_id == 'all') {
            $classified->block_id = 0; // 0 indicates all blocks
        } else {
            $classified->block_id = $request->block_id;
        }
        
        // Set flat_id logic:
        // - If request provides a flat_id, use it (creating or editing on-behalf)
        // - If editing and no flat_id provided, preserve the original flat_id
        // - Otherwise default to 0 (no flat targeting)
        if ($request->filled('flat_id')) {
            $classified->flat_id = $request->flat_id;
        } else {
            if ($request->id) {
                $classified->flat_id = $originalValues['flat_id'] ?? 0;
            } else {
                $classified->flat_id = 0;
            }
        }
        
        // Handle reason for rejected/editing status
        if($request->status == 'Rejected' || $request->status == 'Send For Editing'){
            $classified->reason = $request->reason;
        } else {
            $classified->reason = null;
        }
        
        // Set notification_type based on final normalized selections
        if ($classified->category === 'All Buildings' || $classified->block_id === 0) {
            $classified->notification_type = 'all';
        } else {
            $classified->notification_type = 'selected';
        }
        
        // Track who is updating this classified (set updated_by on any save/update)
        if ($request->id) {
            $classified->updated_by = Auth::id();
            // $classified->is_notified = false;
        }
        
        // Track if this is a classified being set to Approved status
        // Only set the flag if:
        // 1. This is a NEW classified AND status is Approved, OR
        // 2. This is an UPDATE where status changed FROM non-Approved TO Approved
        $oldStatus = $originalValues['status'] ?? null;
        if (!$request->id && $classified->status === 'Approved') {
            // New classified with Approved status
            $classified->is_approved_on_creation = true;
            $classified->approved_at = Carbon::now();
        } elseif ($request->id && $oldStatus !== 'Approved' && $classified->status === 'Approved') {
            // Existing classified being approved for the first time
            $classified->is_approved_on_creation = true;
            $classified->approved_at = Carbon::now();
        }
        
        $classified->save();
        
        // Handle photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $extension = $file->getClientOriginalExtension();
                $filename = uniqid('classified_') . '.' . $extension;
                $file->move(public_path('/images/classifieds/'), $filename);

                ClassifiedPhoto::create([
                    'classified_id' => $classified->id,
                    'photo' => $filename,
                ]);
            }
        }
        
        // Handle classified_buildings table
        if (!$request->id) { // Only for new classifieds
            if ($request->category === 'All Buildings') {
                // Save record for each building that allows 'All Buildings' classifieds
                $buildings = Building::whereHas('permissions', function($q) {
                    $q->where('name', 'Classified for all buildings');
                })->get();
                foreach ($buildings as $b) {
                    ClassifiedBuilding::create([
                        'classified_id' => $classified->id,
                        'building_id' => $b->id
                    ]);
                }
                \Log::info("Classified saved for all buildings", [
                    'classified_id' => $classified->id,
                    'total_buildings' => $buildings->count()
                ]);
            } else {
                // Save record for current building only
                ClassifiedBuilding::create([
                    'classified_id' => $classified->id,
                    'building_id' => $classified->building_id
                ]);
                \Log::info("Classified saved for specific building", [
                    'classified_id' => $classified->id,
                    'building_id' => $classified->building_id
                ]);
            }
        }
        // If updating an existing record, ensure classified_buildings reflects the final category and building
        if ($request->id) {
            $originalCategory = ($originalValues['category'] ?? null);
            if ($originalCategory !== $classified->category) {
                // If it changed to All Buildings, recreate records for all buildings
                    if ($classified->category === 'All Buildings') {
                    ClassifiedBuilding::where('classified_id', $classified->id)->delete();
                    // Only associate to buildings that have 'Classified for all buildings' permission
                    $buildings = Building::whereHas('permissions', function($q) {
                        $q->where('name', 'Classified for all buildings');
                    })->get();
                    foreach ($buildings as $b) {
                        ClassifiedBuilding::create([
                            'classified_id' => $classified->id,
                            'building_id' => $b->id
                        ]);
                    }
                    \Log::info("Classified updated to all buildings", [
                        'classified_id' => $classified->id,
                        'total_buildings' => $buildings->count()
                    ]);
                } else {
                    // Changed to a specific building - clear existing and create a single record
                    ClassifiedBuilding::where('classified_id', $classified->id)->delete();
                    // Ensure the building accepts within building classifieds if the category is Within Building
                    $targetBuilding = Building::find($classified->building_id);
                    if ($targetBuilding && ($classified->category !== 'Within Building' || $targetBuilding->hasPermission('Classified for withinbuilding'))) {
                        ClassifiedBuilding::create([
                            'classified_id' => $classified->id,
                            'building_id' => $classified->building_id
                        ]);
                    }
                    \Log::info("Classified updated to specific building", [
                        'classified_id' => $classified->id,
                        'building_id' => $classified->building_id
                    ]);
                }
            }
            // If the building_id changed (and not in All Buildings mode), update the classified_buildings entry
            $originalBuildingId = ($originalValues['building_id'] ?? null);
            if ($originalBuildingId != $classified->building_id && $classified->category !== 'All Buildings') {
                ClassifiedBuilding::where('classified_id', $classified->id)->delete();
                ClassifiedBuilding::create([
                    'classified_id' => $classified->id,
                    'building_id' => $classified->building_id
                ]);
                \Log::info("Classified building updated", [
                    'classified_id' => $classified->id,
                    'building_id' => $classified->building_id,
                    'old_building_id' => $originalBuildingId
                ]);
            }
        }
        
    // ================= STATUS MESSAGE =================

                    $title = null;
                    $body  = null;

                    if ($classified->status === 'Approved') {
                        $title = 'Your Post is Live!';
                        $body  = 'Your classified post is approved and visible to users.';
                    }

                    if ($classified->status === 'Rejected') {
                        $title = 'Your Post Was Rejected';
                        $body  = 'Unfortunately, your classified was rejected.';
                    }

                    if ($classified->status === 'Send For Editing') {
                        $title = 'Your Post Needs Editing';
                        $body  = 'Your classified requires changes. Please review and resubmit.';
                    }


                    // ================= SAVE STATUS NOTIFICATION =================

                        if ($title && $body && $classified->user) {

                            $notification = new DatabaseNotification();
                            $notification->user_id = $classified->user_id;
                            $notification->from_id = Auth::id();
                            $notification->building_id = $classified->building_id;
                            $notification->flat_id = $classified->flat_id;
                            $notification->title = $title;
                            $notification->body  = $body;
                            $notification->type  = 'classified_status';
                            $notification->status = 0;

                            $notification->dataPayload = [
                                'classified_id' => (string) $classified->id,
                                'status' => $classified->status
                            ];

                            $notification->save();
                        }


        
        // Send notification to classified owner using FCMService
        $user = $classified->user;
        if ($user) {
                // Ensure FCMService is initialized before any send attempts
                $fcmService = new FCMService();

                $devices = DB::table('user_devices')
                    ->where('user_id', $user->id)
                    ->whereNotNull('fcm_token')
                    ->where('is_active', 1)
                    ->select('fcm_token', 'device_type', 'app_name')
                    ->get();

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'Classifieds',
                'params' => json_encode(['ScreenTab' => 'Post Status','classified_id' => (string)$classified->id]),
                    'categoryId' => 'Classifieds',
                    'channelId' => 'Community',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'CLASSIFIED_STATUS',
                    'classified_id' => (string)$classified->id,
                ];
                
                // Send APNs to iOS devices once per app_name and FCM to others
                $iosDevices = collect($devices)->filter(fn($d) => strtolower($d->device_type) === 'ios');
                $androidOrWebDevices = collect($devices)->filter(fn($d) => strtolower($d->device_type) !== 'ios');
                
                // Send notifications via FCM to the available devices
                foreach ($devices as $device) {
                    try {
                        // Save notification to database
                        $notification = new DatabaseNotification();
                        $notification->user_id = $user->id;
                        $notification->from_id = Auth::id();
                        $notification->building_id = ($classified->category === 'All Buildings' || $classified->building_id == 0) ? null : $classified->building_id;
                        $notification->title = $title;
                        $notification->body = $body;
                        $notification->type = 'classified_status';
                        // For iOS devices, prefer using APNs via NotificationHelper2 (covers APNs tokens and FCM tokens)
                        \Log::info('Sending classified status notification', [
                            'to_user' => $user->id,
                            'classified_id' => $classified->id,
                            'device_token_snippet' => substr($device->fcm_token ?? '', 0, 10),
                            'from_id' => Auth::id()
                        ]);
                        $result = $fcmService->sendNotification($device->fcm_token, $title, $body, $dataPayload);
                        $notification->status = 0;
                        $notification->save();
                        
                        // previously sent via NotificationHelper2 or FCMService on the branch above
                        
                        if (!$result['success']) {
                            \Log::error('FCM notification failed for classified status', [
                                'user_id' => $user->id,
                                'classified_id' => $classified->id,
                                'error' => $result['error'] ?? 'Unknown error'
                            ]);
                            
                            // Handle invalid tokens
                            if (isset($result['response']['error']['details'][0]['errorCode']) && 
                                $result['response']['error']['details'][0]['errorCode'] === 'UNREGISTERED') {
                                \Log::info("Removing invalid FCM token for user {$user->id}");
                                DB::table('user_devices')
                                    ->where('user_id', $user->id)
                                    ->where('fcm_token', $device->fcm_token)
                                    ->update(['fcm_token' => null, 'is_active' => 0]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Exception sending classified status notification', [
                            'user_id' => $user->id,
                            'classified_id' => $classified->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

            // Convert all data payload values to strings
            foreach ($dataPayload as $key => $value) {
                $dataPayload[$key] = (string)$value;
            }

            $fcmService = new FCMService();
        }
        
        // Determine if notifications should be sent:
        // - New classified (send if Approved)
        // - Updated classified: send if final status is Approved and either
        //   - status changed to Approved OR
        //   - any key fields (title/desc/category/block) changed
        $sendNotification = false;
        if ($classified->status == 'Approved') {
            if (!$request->id) {
                $sendNotification = true;
            } else {
                // check if status has changed to Approved
                $oldStatus = $originalValues['status'] ?? null;
                if ($oldStatus !== 'Approved' && $classified->status === 'Approved') {
                    $sendNotification = true;
                }
                // check for meaningful updates to content
                $oldTitle = (string) ($originalValues['title'] ?? '');
                $oldDesc = (string) ($originalValues['desc'] ?? '');
                $oldCategory = (string) ($originalValues['category'] ?? '');
                $oldBlock = (string) ($originalValues['block_id'] ?? '');
                if (!$sendNotification && (
                    $oldTitle !== (string) $classified->title ||
                    $oldDesc !== (string) $classified->desc ||
                    $oldCategory !== (string) $classified->category ||
                    $oldBlock !== (string) ($classified->block_id ?? '')
                )) {
                    $sendNotification = true;
                }
                // If new photos are uploaded, treat as meaningful change
                if (!$sendNotification && $request->hasFile('photos')) {
                    $sendNotification = true;
                }
            }
        }

        if ($sendNotification) {
            // Dispatch the SendClassifiedNotification job to handle notifications asynchronously
            // This follows the same pattern as NoticeboardController with SendNoticeboardNotification
            try {
                SendClassifiedNotification::dispatch($classified, []);
                \Log::info('SendClassifiedNotification job dispatched', [
                    'classified_id' => $classified->id,
                    'category' => $classified->category,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to dispatch SendClassifiedNotification job', [
                    'classified_id' => $classified->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // dd($request->id);
        // if (Auth::User()->role == 'BA'){
        //      if ($request->id){
        //     try {
        //         $this->sendClassifiedUpdateNotification($classified);
        //         \Log::info('Classified update notification sent', [
        //             'classified_id' => $classified->id,
        //             'updated_by' => Auth::id(),
        //         ]);
        //     } catch (\Exception $e) {
        //         \Log::error('Failed to send classified update notification', [
        //             'classified_id' => $classified->id,
        //             'error' => $e->getMessage(),
        //         ]);
        //     }
        // }
        // }
         
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.classifieds') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $classified = Classified::where('id',$id)->withTrashed()->first();
        if(!$classified){
            return redirect()->route('classified.index');
        }
        
        // Get buildings associated with this classified
        $targetBuildings = collect();
        
        // Always check classified_buildings table first (more reliable)
        $buildingIds = ClassifiedBuilding::where('classified_id', $classified->id)
            ->pluck('building_id');
            
        if ($buildingIds->count() > 0) {
            // Get buildings from classified_buildings table
            $targetBuildings = Building::whereIn('id', $buildingIds)->get();
        } else {
            // Fallback: Get single building from classified record
            if ($classified->building_id && $classified->building_id != 0) {
                $targetBuildings = Building::where('id', $classified->building_id)->get();
            }
        }
        
        return view('admin.classified.show', compact('classified', 'targetBuildings'));
    }
    
    
    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id, Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasPermission('custom.classifieds') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $classified = Classified::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $classified->delete();
        }else{
            $classified->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    
private function sendClassifiedUpdateNotification(Classified $classified)
{
    try {
        Log::info('Classified update notification started', [
            'classified_id' => $classified->id,
            'category' => $classified->category,
        ]);

        $flats = collect();

        /* ================= CATEGORY LOGIC ================= */

        if ($classified->category === 'All Buildings') {

            $buildingIds = ClassifiedBuilding::where('classified_id', $classified->id)
                ->pluck('building_id');

            $allowedBuildingIds = Building::whereIn('id', $buildingIds)
                ->whereHas('permissions', fn ($q) =>
                    $q->where('name', 'Classified for all buildings')
                )
                ->pluck('id');

            if ($allowedBuildingIds->isNotEmpty()) {
                $flats = Flat::whereIn('building_id', $allowedBuildingIds)
                    ->where(fn ($q) =>
                        $q->whereNotNull('owner_id')
                          ->orWhereNotNull('tanent_id')
                    )->get();
            }

        } else {

            $building = Building::find($classified->building_id);

            if ($building && $building->hasPermission('Classified for withinbuilding')) {

                $flats = Flat::when(
                        $classified->block_id != 0,
                        fn ($q) => $q->where('block_id', $classified->block_id),
                        fn ($q) => $q->where('building_id', $building->id)
                    )
                    ->where(fn ($q) =>
                        $q->whereNotNull('owner_id')
                          ->orWhereNotNull('tanent_id')
                    )->get();
            }
        }

        /* ================= USERS ================= */

        $userIds = collect();

        foreach ($flats as $flat) {
            if ($flat->owner_id) {
                $userIds->push($flat->owner_id);
            }
            if ($flat->tanent_id) {
                $userIds->push($flat->tanent_id);
            }
        }

        $userIds = $userIds
            ->unique()
            ->reject(fn ($id) => $id == Auth::id())
            ->values()
            ->toArray();

        $users = User::whereIn('id', $userIds)
            ->whereNull('deleted_at')
            ->get();

        Log::info('Users found', [
            'classified_id' => $classified->id,
            'users' => $users->pluck('id')->toArray()
        ]);

        /* ================= SEND ================= */
        // Prepare title & message
        if ($classified->updated_by == $classified->user_id) {
            $title = $classified->title;
            $message = 'A new classified has been added.';
        } else {
            $title = 'Classified Updated: ' . $classified->title;
            $message = 'A classified you can view has been updated.';
        }
        // dd($message);
            foreach ($users as $user) {
                $result = NotificationHelper2::sendNotification(
                    $user->id,
                    $title,
                    $message,
                    [
                        'params' => json_encode([
                            'classified_id' => $classified->id,
                            'type' => 'classified_updated'
                        ]),
                        'type' => 'classified_updated',
                        'classified_id' => $classified->id
                    ],
                    [
                        'from_id' => Auth::id(),
                        'building_id' => $classified->building_id,
                        'type' => 'classified_updated',
                        'save_to_db' => true
                    ],
                    'user'
                );
                
                  Log::info('Notification result', [
                'user_id' => $user->id,
                'result' => $result
            ]);
            }
    } catch (\Exception $e) {
        Log::error('Classified update notification failed', [
            'classified_id' => $classified->id,
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}


}
