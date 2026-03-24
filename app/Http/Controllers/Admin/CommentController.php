<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Role;
use Auth;
use Illuminate\Support\Facades\Log;

use App\Helpers\NotificationHelper2 as NotificationHelper;

class CommentController extends Controller
{
    public function addComment(Request $request)
    {
        $rules = [
            'issue_id' => 'required|exists:issues,id',
            'text' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ],422);
        }
        $comment = new Comment();
        $comment->issue_id = $request->issue_id;
        
         if(Auth::User()->role == 'BA'){
           $comment->role_user_id = Auth::id();
           $comment->commentedbyRole ='BA';
        } else {
             $comment->user_id = Auth::id();
              $comment->commentedbyRole ='Issue Tracker';
        }
       
        $comment->text = $request->text;
        $comment->save();
        
        // Log::info(Auth::user()->name);
        
        $title = 'New Comment on Your Issue';
        $body = Auth::user()->name . ' commented: ' . $request->text ;
        
        $issue = $comment->issue;
        // $notifiedUserIds = [];
        if ($issue) {
            // Notify issue owner
    if ($issue->user_id && $issue->user_id != Auth::id()) {
        $ScreenTab='Pending Issue';
        $screen='Raise an Issue';
            $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => $screen,
            'params' => json_encode([
                'screen' => $screen,
                'ScreenTab' => $ScreenTab,
                'issue_id' => $issue->id,
                'openIssues_comment'=>$comment->id
            ]),
            'categoryId' => 'IssueUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'user_id' => (string)$issue->user_id,
            'flat_id' => (string)$issue->flat_id,
            'building_id' => (string)$issue->building_id,
            'issue_id' => (string)$issue->id,
        ];
        
            $notificationResult = NotificationHelper::sendNotification(
            $issue->user_id,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $issue->user_id,  // From the person accepting
                'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                // 'department_id' =>$issue->role_user->department_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],['user']
            );
      
            }
            // Notify department/role user if exists
            if ($issue->role_user_id && $issue->role_user_id != Auth::id()) {
    $statusMap = [
    'Pending'   => 'new',
    'Ongoing'   => 'ongoing',
    'Onhold'    => 'onhold',
    'Completed' => 'completed',
];

$ScreenTab = $statusMap[$issue->status] ?? 'unknown';
$department_Name = Role::find($issue->role_id)->name;


        $screen ='Home';
            $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            // 'screen' => $screen,
            'params' => json_encode([
                'screen' => $screen,
                'ScreenTab' => $ScreenTab,
                'issue_id' => $issue->id,
                'openIssues_comment'=>$comment->id,
                'role_id' => $issue->role_id,
                '$department_Name'=>$department_Name
            ]),
            'categoryId' => 'IssueUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'user_id' => (string)$issue->role_user_id,
            'flat_id' => (string)$issue->flat_id,
            'building_id' => (string)$issue->building_id,
            'issue_id' => (string)$issue->id,
            'role_id' => $issue->role_id,
            '$department_Name'=>$department_Name
        ];
        
            $notificationResult = NotificationHelper::sendNotification(
            $issue->role_user_id,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $issue->role_user_id,  // From the person accepting
                // 'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                'role_id' =>$issue->role_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],['issue']);
            }
        }
        
        
        
  
        
  return back()->with('success', 'Comment added successfully.');
        // return response()->json([
        //     'status' => 'success',
        //     'html' => view('partials.comment', compact('comment'))->render()
        // ]);
    }

    public function addReply(Request $request)
    {
        $rules = [
            'comment_id' => 'required|exists:comments,id',
            'text' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ],422);
        }
        
        $reply = new Reply();
        $reply->comment_id = $request->comment_id;
        $reply->user_id = Auth::id();
        $reply->text = $request->text;
        $reply->save();

        return response()->json([
            'status' => 'success',
            'html' => view('partials.reply', compact('reply'))->render()
        ]);
    }
}
