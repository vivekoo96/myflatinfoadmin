<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Issue;
use App\Models\IssuePhoto;
use App\Models\Building;
use App\Models\Flat;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\BuildingUser;
use App\Models\Role;

use Illuminate\Support\Facades\Log;
use App\Helpers\NotificationHelper2 as NotificationHelper;

class IssueController extends Controller
{

    public function index()
    {
        $user = Auth::User();
        
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Issue Tracker" || Auth::User()->hasPermission('custom.issuestracking') )
        
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.issue.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function storexxx(Request $request)
    {
        $user = Auth::User();
        if(
            $user->role == 'BA' ||
            (method_exists($user, 'hasRole') && $user->hasRole('president')) ||
            (method_exists($user, 'hasRole') && $user->hasRole('issue')) ||
            (method_exists($user, 'hasPermission') && $user->hasPermission('custom.issuestracking'))
        )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'id' => 'nullable|exists:issues,id',
            // 'building_id' => 'nullable|exists:buildings,id',
            'block_id' => 'nullable|exists:blocks,id',
            'flat_id' => 'nullable|exists:flats,id',
            'role_id' => 'required|exists:roles,id',
            'periority' => 'required|in:High,Medium,Low',
            'desc' => 'required',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
            'status' => 'required|in:On Hold,Pending,Ongoing,Solved,Rejected',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $issue = new Issue();
        $msg = 'Issue created successfully';
        
        
        if($request->id){
            $issue = Issue::find($request->id);
            $msg = 'Issue updated successfully';
            
            // Prevent updates to solved issues
            if($issue->status === 'Solved'){
                if($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Cannot update a solved issue. Solved issues are read-only.'
                    ], 422);
                } else {
                    return redirect()->back()->with('error', 'Cannot update a solved issue. Solved issues are read-only.');
                }
            }
        }
        $issue->user_id=Auth::User()->id;
        $issue->building_id = Auth::User()->building_id;
        $issue->role_id = $request->role_id;
        $issue->block_id = $request->block_id;
        $issue->flat_id = $request->flat_id;
        $issue->created_by_rolename = Auth::user()->role;
        $issue->desc = $request->desc;
        $issue->periority = $request->periority;
        $issue->status = $request->status;
        $issue->save();

        
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $allowedfileExtension = ['jpeg', 'jpg', 'png'];
                $extension = $file->getClientOriginalExtension();
                $filename = 'issues/' . uniqid() . '.' . $extension;
                $path = $file->move(public_path('/images/issues/'), $filename);

                $photo = new IssuePhoto();
                $photo->issue_id = $issue->id;
                $photo->photo = $filename;
                $photo->save();
            }
        }
        
        


    
   
        
        
$building_users = BuildingUser::where('role_id', $request->role_id)
    ->get();

// pluck actual user IDs from BuildingUser records
$user_ids = $building_users->pluck('user_id')->unique()->toArray();


$department_Name = Role::find($issue->role_id)->name;

            
        
foreach ($user_ids as $user) {
    \Log::info('Department ID>>>', ['id' => $user]);
    // Build payload
    $dataPayload = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => "Home",
        'params' => json_encode([
            'ScreenTab' => 'new',
            'screen' => "home",
            'issue_id' => $issue->id,
            'role_id'=>$issue->role_id,
            '$department_Name'=>$department_Name
        ]),
        'categoryId' => 'Issue',
        'channelId' => 'default',
        'sound' => 'default',
        'type' => 'ISSUE_CREATED',
        'role_id' => $request->role_id,
                  '$department_Name'=>$department_Name
    ];

    // Set default notification title/body if not set
    if (!isset($title)) {
        $title = 'New Issue Created';
    }
    if (!isset($body)) {
        $body = 'A new issue has been created.';
    }

    // Send notification
    $notificationResult = NotificationHelper::sendNotification(
        $user,
        $title,
        $body,
        $dataPayload,
        [
            'from_id' => $user,  
            // 'flat_id' => $issue->flat_id,
            'building_id' => $issue->building_id,
            'role_id' => $request->role_id,
            'type' => 'issue_coming',
            // 'apns_client' => $this->apnsClient ?? null,
            'ios_sound' => 'default'
        ],
        ['issue']
    );
}

    
        
        return redirect()->back()->with('success',$msg);
    }


public function store(Request $request)
{
    // Permission check
    $user = Auth::user();
    if(!(
        $user->role == 'BA' ||
        (method_exists($user, 'hasRole') && $user->hasRole('president')) ||
        (method_exists($user, 'hasRole') && $user->hasRole('issue')) ||
        (method_exists($user, 'hasPermission') && $user->hasPermission('custom.issuestracking'))
    )) {
        return redirect('permission-denied')->with('error','Permission denied!');
    }

    $rules = [
        'id' => 'nullable|exists:issues,id',
        'block_id' => 'nullable|exists:blocks,id',
        'flat_id' => 'nullable|exists:flats,id',
        'role_id' => 'required|exists:roles,id',
        'periority' => 'required|in:High,Medium,Low',
        'desc' => 'required',
        'photos' => 'nullable|array',
        'photos.*' => 'image|max:2048',
        'status' => 'required|in:On Hold,Pending,Ongoing,Solved,Rejected',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json(['error' => $validation->errors()->first()], 422);
    }

    $isNew = !$request->id;
    $issue = $isNew ? new Issue() : Issue::find($request->id);
    $msg = $isNew ? 'Issue created successfully' : 'Issue updated successfully';

    // Prevent updates to solved issues
    if(!$isNew && $issue->status === 'Solved') {
        $errorMsg = 'Cannot update a solved issue. Solved issues are read-only.';
        return $request->expectsJson() 
            ? response()->json(['error' => $errorMsg], 422) 
            : redirect()->back()->with('error', $errorMsg);
    }

    $oldStatus = $issue->status ?? null;

    // Fill issue data
    $issue->user_id = Auth::user()->id;
    $issue->building_id = Auth::user()->building_id;
    $issue->role_id = $request->role_id;
    $issue->block_id = $request->block_id;
    $issue->flat_id = $request->flat_id;
    $issue->created_by_rolename = 'BA';
    $issue->desc = $request->desc;
    $issue->periority = $request->periority;
    $issue->status = $request->status;
    $issue->save();

    // Save photos
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename = 'issues/' . uniqid() . '.' . $extension;
            $file->move(public_path('/images/issues/'), $filename);

            IssuePhoto::create([
                'issue_id' => $issue->id,
                'photo' => $filename,
            ]);
        }
    }

    // Determine if notification should be sent
    $sendNotification = false;
    


    if ($isNew && $issue->status === 'Pending') {
        // New issue and Pending
        $sendNotification = true;
        $title = "New Issue Pending";
        $body = "A new issue has been created and is pending.";
        

        $building = Building::find($issue->building_id);
$building_name = $building ? $building->name : '';

$department_Name = Role::find($issue->role_id)->name;


 $title = 'New Issue Raised in '. $building_name .' Building';
 $body =  "A new issue has been reported by ". Auth::user()->name ." in ". $building_name ." Building, for " .$department_Name." department. Please review and take necessary action.";


    } elseif (!$isNew && $oldStatus !== $issue->status && in_array($issue->status, ['Rejected', 'Solved'])) {
        // Issue status changed to Rejected or Solved
        $sendNotification = true;
        $title = "Issue Status Updated";
        $body = "The issue status has been updated to {$issue->status}.";
    }

    if ($sendNotification) {
        $building_users = BuildingUser::where('role_id', $request->role_id)->where('building_id',$issue->building_id)->get();
        // Use the user_id field from BuildingUser model to get user IDs
        $user_ids = $building_users->pluck('user_id')->unique()->toArray();

        foreach ($user_ids as $user) {
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => "Home",
                'params' => json_encode([
                    'ScreenTab' => 'new',
                    'screen' => "home",
                    'issue_id' => $issue->id,
                'role_id' => $request->role_id,
                '$department_Name'=>$department_Name
                ]),
                'categoryId' => 'Issue',
                'channelId' => 'default',
                'sound' => 'default',
                'type' => 'ISSUE_CREATED',
                'role_id' => $request->role_id,
                '$department_Name'=>$department_Name
            ];

            NotificationHelper::sendNotification(
                $user,
                $title,
                $body,
                $dataPayload,
                [
                    'from_id' => $user,
                    'building_id' => $issue->building_id,
                    'role_id' => $request->role_id,
                    'type' => 'issue_coming',
                    'ios_sound' => 'default'
                ],
                ['issue']
            );
        }
    }

    return redirect()->back()->with('success', $msg);
}



    public function show($id)
    {
        $user = Auth::User();
        if(
            $user->role == 'BA' ||
            (method_exists($user, 'hasRole') && $user->hasRole('president')) ||
            (method_exists($user, 'hasRole') && $user->hasRole('issue')) ||
            (method_exists($user, 'hasPermission') && $user->hasPermission('custom.issuestracking'))
        )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $issue = Issue::where('id',$id)->withTrashed()->first();
        if(!$issue){
            return redirect()->route('issue.index');
        }
        return view('admin.issue.show',compact('issue'));
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
        $user = Auth::User();
        if(
            $user->role == 'BA' ||
            (method_exists($user, 'hasRole') && $user->hasRole('president')) ||
            (method_exists($user, 'hasRole') && $user->hasRole('issue')) ||
            (method_exists($user, 'hasPermission') && $user->hasPermission('custom.issuestracking'))
        ) {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $issue = Issue::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $issue->delete();
        }else{
            $issue->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_issue_status(Request $request)
    {
        $user = Auth::User();
        if(
            $user->role == 'BA' ||
            (method_exists($user, 'hasRole') && $user->hasRole('president')) ||
            (method_exists($user, 'hasRole') && $user->hasRole('issue')) ||
            (method_exists($user, 'hasPermission') && $user->hasPermission('custom.issuestracking'))
        ) {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $issue = Issue::where('id',$request->id)->withTrashed()->first();
        if($issue->status == 'Solved'){
            $issue->status = 'On Hold';
        }else{
            $issue->status = 'Solved';
        }
        $issue->save();
        return response()->json([
            'msg' => 'success'
        ],200);
    }

    public function get_flats(Request $request)
    {
        $block_id = $request->block_id;
        $flat_id = $request->flat_id;
        $flats = Flat::where('block_id',$block_id)->where('status','Active')->get();
        return view('partials.flats',compact('flats','flat_id'));
        
    }
}
