<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\AdminForgotPasswordController;

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\NoticeboardController;
use App\Http\Controllers\Admin\ClassifiedController;
use App\Http\Controllers\Admin\RoleController;
// use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\FlatController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\TimingController;
use App\Http\Controllers\Admin\VisitorController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\GuardController;
use App\Http\Controllers\Admin\GuardPatrolAssignmentController;
use App\Http\Controllers\Admin\EssentialController;
use App\Http\Controllers\Admin\FundController;
use App\Http\Controllers\Admin\GateController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ParkingController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PollController;
use App\Http\Controllers\Admin\GuideVideoController;
use App\Http\Controllers\Admin\MeetingMinuteController;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\Admin\SecurityNoteController;
use App\Http\Controllers\Admin\PatrolLocationController;
use App\Http\Controllers\Admin\GuardPatrolController;
use App\Http\Controllers\Admin\BuildingShiftController;
use App\Http\Controllers\Admin\ActivityAdminController;
use App\Http\Controllers\Admin\DeliveryRestrictionController;
use App\Http\Controllers\Admin\GstController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetAmcController;
use App\Http\Controllers\Admin\MoveInOutController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AttendanceController;

use Illuminate\Support\Facades\Mail;
use App\Models\Setting;

// Public route for about us information
Route::get('/info', function() {
    $aboutUs = Setting::first()->about_us;
    return view('info', compact('aboutUs'));
})->name('info');

Route::get('clear-cache',function(){
    //\Artisan::call('storage:link');
    //\Artisan::call('vendor:publish --provider="Fruitcake\Cors\CorsServiceProvider');
    \Artisan::call('config:clear');
    \Artisan::call('cache:clear');
    \Artisan::call('view:clear');
    \Artisan::call('route:clear');
    \Artisan::call('config:cache');
});

Route::get('/test-mail', function () {
    try {
        Mail::raw('This is a test email.', function ($message) {
            $message->to('krguptahemant@gmail.com')
                    ->subject('Test Mail');
        });

        return 'Mail sent!';
    } catch (\Exception $e) {
        return 'Failed: ' . $e->getMessage();
    }
});


Route::middleware('guest')->group(function () {

	Route::get('/',[AdminController::class, 'index'])->name('login');
	Route::post('/login',[AdminController::class, 'login']);
	Route::post('/verifyotp',[AdminController::class, 'verifyotp']);
	
	Route::get('forget-password', [AdminForgotPasswordController::class, 'showForgetPasswordForm']);
	Route::post('forget-password', [AdminForgotPasswordController::class, 'submitForgetPasswordForm']);
    Route::get('verify-otp', [AdminForgotPasswordController::class, 'showVerifyOtpForm']);
    Route::post('verify-otp', [AdminForgotPasswordController::class, 'verifyOtp']);
    Route::get('resend-otp', [AdminForgotPasswordController::class, 'resendOtp']);
    Route::get('reset-password/{token}', [AdminForgotPasswordController::class, 'showResetPasswordForm']);
    Route::post('reset-password', [AdminForgotPasswordController::class, 'submitResetPasswordForm']);
});

Route::post('/check-phone', [AdminController::class, 'checkPhone']);
Route::post('/save-token',[AdminController::class, 'save_token'])->name('save.token');

Route::middleware('admin')->group(function () {
    Route::get('/permission-denied',[AdminController::class, 'permission_denied']);
    Route::get('/building-option',[AdminController::class, 'building_option']);
    Route::get('/select-building/{id}',[AdminController::class, 'select_building']);
    Route::get('/select-role',[AdminController::class, 'role_option']);
    Route::get('/select-role/{role_id}',[AdminController::class, 'select_role']);

    Route::middleware('building')->group(function () {
    // Unified Visitor Management Routes
    Route::get('/visitor/invitations', [VisitorController::class, 'invitationsList'])->name('admin.visitor.invitationsList');
    Route::get('/visitor/invite-guest', [VisitorController::class, 'inviteGuestForm'])->name('admin.visitor.inviteGuestForm');
    Route::post('/visitor/invite-guest', [VisitorController::class, 'inviteGuest'])->name('admin.visitor.inviteGuest');
    Route::post('/visitor/save-invitation', [VisitorController::class, 'saveInvitation'])->name('admin.visitor.saveInvitation');
    Route::post('/get-flats', [FlatController::class, 'getFlatsByBlock'])->name('get.flats');
        Route::get('/dashboard',[AdminController::class, 'dashboard']);
           Route::post('/get-building-flats', [FlatController::class, 'getFlatsByBuilding'])->name('get.building.flats');
        Route::get('/profile',[AdminController::class, 'profile']);
        Route::post('/update-profile',[AdminController::class, 'update_profile']);
        Route::post('/change-password',[AdminController::class, 'change_password']);
        Route::get('/users',[AdminController::class, 'users']);
        Route::get('/other-users',[AdminController::class, 'other_users']);
           Route::get('/user/{id}/{building_user_id}',[AdminController::class, 'show_user']);
        Route::post('/store-user',[AdminController::class, 'store_user']);
        Route::post('/delete-user',[AdminController::class, 'delete_user']);
        
           Route::post('/delete-user-enhanced',[AdminController::class, 'delete_user_enhanced']);
        Route::post('/get-user-building-info',[AdminController::class, 'get_user_building_info']);
         Route::post('/get-user-building-info',[AdminController::class, 'get_user_building_info']);
        Route::post('/update-user-status',[AdminController::class, 'update_user_status']);
          Route::post('/get-user-guard-info',[AdminController::class, 'get_user_guard_info']);
        Route::post('/get-user-by-email',[AdminController::class, 'get_user_by_email']);
        Route::post('/check-email-exists',[AdminController::class, 'checkEmailExists']);
        
        Route::post('bulk-upload-users', [AdminController::class, 'bulkUpload']);
        Route::get('download-sample-users', [AdminController::class, 'downloadSample']);

        Route::post('bulk-upload-flats', [FlatController::class, 'bulkUploadFlats']);
        Route::get('download-sample-flats', [FlatController::class, 'downloadSampleFlats']);
        
        Route::get('/orders',[AdminController::class, 'orders']);
        Route::get('/transactions',[AdminController::class, 'transactions']);
        
        Route::resource('/guard', GuardController::class)->middleware('staff');
        Route::post('/store-new-guard',[GuardController::class, 'store_new_guard']);
        
        Route::resource('/city', CityController::class);
        Route::resource('/role', RoleController::class);
//         Route::resource('/permission', PermissionController::class);
        Route::get('/get-role-permissions/{id}', [RoleController::class, 'getRolePermissions'])->name('getRolePermissions');
        Route::post('/store-user-role',[RoleController::class, 'store_user_role']);
        Route::post('/update-user-role',[RoleController::class, 'update_user_role']);
        Route::post('/delete-user-role',[RoleController::class, 'delete_user_role']);
        Route::get('/custom-departments', [RoleController::class, 'custom_departments'])->middleware('staff');
        Route::get('/department/{role_slug}', [RoleController::class, 'get_departments'])->middleware('staff');
        Route::get('/issue-department', [RoleController::class, 'index'])->middleware('issue');
        Route::get('/issue-department/{role_slug}', [RoleController::class, 'get_departments'])->middleware('staff');
        Route::post('/get-department-users/{role_slug}', [RoleController::class, 'get_department_users'])->middleware('staff');
        
        Route::resource('/buildings', BuildingController::class);
        Route::post('/update-building-status',[BuildingController::class, 'update_building_status']);
                
        Route::resource('/block', BlockController::class);
        Route::post('/update-block-status',[BlockController::class, 'update_block_status']);
        Route::post('/toggle-maintenance', function (Request $request) {
                if ($request->password !== "Hello@123!") {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid password'
                    ], 403);
                }
                if (app()->isDownForMaintenance()) {
                    Artisan::call('up');
                    return response()->json([
                        'status' => true,
                        'message' => 'Website is LIVE now'
                    ]);
                }
                Artisan::call('down', [
                    '--retry' => 60,
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'Website is in MAINTENANCE mode'
                ]);
            });
        
        Route::post('/get-gates',[GateController::class, 'get_gates']);
        Route::resource('/gate', GateController::class);
        Route::post('/update-gate-status',[GateController::class, 'update_gate_status']);

        Route::resource('/parking', ParkingController::class);
        
        Route::resource('/flat', FlatController::class);
        Route::post('/update-flat-status',[FlatController::class, 'update_flat_status']);
        Route::get('/get-flats/{blockId}',[FlatController::class, 'getFlats']);
        Route::post('/update-corpus-fund',[FlatController::class, 'update_corpus_fund'])->middleware('corpusfund');
        Route::post('/get-flat',[FlatController::class, 'get_flat']);
        Route::post('/get-flat-data',[FlatController::class, 'get_flat_data']);
        Route::post('/get-flat-details',[FlatController::class, 'getFlatDetails'])->name('get.flat.details');
        Route::post('/store-parking-flat',[FlatController::class, 'store_parking_flat']);
        Route::post('/delete-parking-flat',[FlatController::class, 'delete_parking_flat']);

        Route::middleware('event')->group(function () {
            Route::resource('/event', EventController::class);
            Route::post('/update-event-status',[EventController::class, 'update_event_status']);
            Route::post('/update-event-payment-status',[EventController::class, 'update_event_payment_status']);
        });

        Route::resource('/payment', PaymentController::class);
        // Route::get('event/invoice/{payment_id}',[AccountController::class, 'event_invoice']);
        Route::get('event/reciept/{payment_id}',[AccountController::class, 'event_reciept']);

        Route::middleware('noticeboard')->group(function () {
            Route::resource('/noticeboard', NoticeboardController::class);
        });
        Route::middleware('classified')->group(function () {
            Route::resource('/classified', ClassifiedController::class);
        });
        Route::post('/get-flats',[IssueController::class, 'get_flats'])->middleware('issue');
        Route::resource('/issue', IssueController::class)->middleware('issue');
        Route::post('/update-issue-status',[IssueController::class, 'update_issue_status'])->middleware('issue');
        Route::post('add-comment', [CommentController::class, 'addComment'])->middleware('issue');
        Route::post('add-reply', [CommentController::class, 'addReply'])->middleware('issue');

        Route::resource('/booking', BookingController::class)->middleware('facility');
        Route::post('change-booking-status', [BookingController::class, 'change_booking_status'])->middleware('facility');
        Route::post('cancel-slot-booking', [BookingController::class, 'cancel_slot_booking'])->middleware('facility');
        Route::resource('/facility', FacilityController::class)->middleware('facility');
        Route::resource('/timing', TimingController::class)->middleware('facility');
        Route::resource('/visitor', VisitorController::class)->middleware('visitor');
        Route::get('/move-in-out', [MoveInOutController::class, 'index'])->name('move-in-out.index');
        Route::get('/move-in-out/create', [MoveInOutController::class, 'create'])->name('move-in-out.create');
        Route::post('/move-in-out', [MoveInOutController::class, 'store'])->name('move-in-out.store');
        Route::post('/move-in-out/approve/{id}', [MoveInOutController::class, 'approve'])->name('move-in-out.approve');
        Route::post('/move-in-out/reject/{id}', [MoveInOutController::class, 'reject'])->name('move-in-out.reject');
        
        Route::get('/visitor/user-history/{phone}', [VisitorController::class, 'userHistory'])->name('visitor.user.history');
        Route::get('/visitor/{id}/timeline', [VisitorController::class, 'getTimeline'])->name('visitor.timeline');
        Route::resource('/vehicles', VehicleController::class)->middleware('vehicle');
        Route::resource('/expense', ExpenseController::class);
        Route::post('/get-model-data',[ExpenseController::class, 'get_model_data']);
        Route::get('/vehicle-inouts',[VehicleController::class, 'vehicle_inouts'])->middleware('vehicle');
          Route::post('/vehicle-inouts/update',[VehicleController::class, 'update_vehicle_inout'])->middleware('vehicle');
        Route::post('/vehicle-inouts/destroy',[VehicleController::class, 'destroy_vehicle_inout'])->middleware('vehicle');
        Route::resource('/maintenance', MaintenanceController::class)->middleware('maintenance');
        Route::post('/update-maintenance-status',[MaintenanceController::class, 'update_maintenance_status'])->middleware('maintenance');
        Route::post('/store-maintenance-payment',[MaintenanceController::class, 'store_maintenance_payment'])->middleware('maintenance');
        Route::post('/approve-upi-payment',[MaintenanceController::class, 'approve_upi_payment'])->middleware('maintenance');
        Route::post('/reject-upi-payment',[MaintenanceController::class, 'reject_upi_payment'])->middleware('maintenance');
        
        Route::resource('/essential', EssentialController::class)->middleware('essential');
        Route::post('/update-essential-status',[EssentialController::class, 'update_essential_status'])->middleware('essential');
        Route::get('/essential/pay/{payment_id}',[AccountController::class, 'pay_essential'])->middleware('essential');
        Route::post('/pay-essential-bill}',[AccountController::class, 'pay_essential_bill'])->middleware('essential');
        Route::post('/store-essential-payment',[EssentialController::class, 'store_essential_payment'])->middleware('essential');
        Route::get('/essential/reciept/{payment_id}',[AccountController::class, 'essential_reciept'])->middleware('essential');
        Route::get('/essential/invoice/{payment_id}',[AccountController::class, 'essential_invoice'])->middleware('essential');
        
        Route::get('/society-fund/expenses',[ExpenseController::class, 'index'])->middleware('societyfund');
        Route::get('/society-fund/maintenance',[FundController::class, 'get_maintenance_funds'])->middleware('societyfund');
        Route::get('/society-fund/essential',[FundController::class, 'get_essential_funds'])->middleware('societyfund');
        Route::get('/society-fund/event',[FundController::class, 'get_event_funds'])->middleware('societyfund');
        Route::get('/society-fund/corpus',[FundController::class, 'get_corpus_funds'])->middleware('societyfund');
        Route::get('/society-fund/reciepts',[FundController::class, 'get_reciepts'])->middleware('societyfund');

        // new routes
        Route::get('/account/opening-balance',[AccountController::class, 'opening_balance'])->middleware('societyfund');
        Route::post('/account/update-opening-balance',[AccountController::class, 'update_opening_balance'])->middleware('societyfund');
        Route::get('/account/statement/income-and-expenditure',[AccountController::class, 'income_and_expenditure'])->middleware('societyfund');
        Route::get('/account/forms/payment',[AccountController::class, 'payment']);
        Route::get('/account/forms/reciept',[AccountController::class, 'reciept']);
        
        Route::get('/account/maintenance/manage',[AccountController::class, 'manage_maintenance'])->middleware('maintenance');
        Route::get('/account/maintenance/pay/{flat_id}',[AccountController::class, 'pay_maintenance'])->middleware('maintenance');
        Route::post('/account/maintenance/pay-maintenance-bill',[AccountController::class, 'pay_maintenance_bill'])->middleware('maintenance');
        Route::get('/account/maintenance/invoice/{maintenance_payment_id}',[AccountController::class, 'maintenance_invoice'])->middleware('maintenance');
        Route::get('/account/maintenance/reciept/{maintenance_payment_id}',[AccountController::class, 'maintenance_reciept'])->middleware('maintenance');
        
        Route::get('/account/pending-bills',[AccountController::class, 'pending_bills']);
        Route::get('/account/upi-pending',[AccountController::class, 'upi_pending']);
        Route::get('/account/upi-history',[AccountController::class, 'upi_history']);
        Route::post('/account/send-due-notifications',[AccountController::class, 'send_due_notifications']);
        Route::get('/account/reminder-history',[AccountController::class, 'reminder_history']);
        Route::get('/account/gst',[GstController::class, 'index'])->name('gst.index');

        // Asset & Inventory Management
        Route::get('/asset', [AssetController::class, 'index'])->name('asset.index');
        Route::post('/asset', [AssetController::class, 'store'])->name('asset.store');
        Route::post('/asset/update-status', [AssetController::class, 'updateStatus'])->name('asset.updateStatus');
        Route::post('/asset/update-stock', [AssetController::class, 'updateStock'])->name('asset.updateStock');
        Route::get('/asset/inventory', [AssetController::class, 'inventory'])->name('asset.inventory');
        Route::delete('/asset/{id}', [AssetController::class, 'destroy'])->name('asset.destroy');

        // AMC Tracking
        Route::get('/asset-amc', [AssetAmcController::class, 'index'])->name('asset-amc.index');
        Route::post('/asset-amc', [AssetAmcController::class, 'store'])->name('asset-amc.store');
        Route::delete('/asset-amc/{id}', [AssetAmcController::class, 'destroy'])->name('asset-amc.destroy');

        //
        
        // Polls & Surveys
        Route::middleware('poll')->group(function () {
            Route::resource('/poll', PollController::class)->only(['index', 'store', 'show', 'destroy']);
            Route::post('/poll/{id}/activate', [PollController::class, 'activate'])->name('poll.activate');
            Route::post('/poll/{id}/close', [PollController::class, 'close'])->name('poll.close');
            Route::post('/poll/{id}/release-results', [PollController::class, 'releaseResults'])->name('poll.releaseResults');
            Route::post('/poll/{id}/update-expiry', [PollController::class, 'updateExpiry'])->name('poll.updateExpiry');
        });

        // Guided Video Tutorials (read-only display for BA)
        Route::get('/guide-video', [GuideVideoController::class, 'index'])->name('guide-video.index');

        // Video Tutorials (grouped display)
        Route::get('/video-tutorials', function() {
            return view('admin.video_tutorials');
        })->name('video-tutorials.index');

        // Meeting Minutes
        Route::get('/meeting-minute', [MeetingMinuteController::class, 'index'])->name('meeting-minute.index');
        Route::post('/meeting-minute', [MeetingMinuteController::class, 'store'])->name('meeting-minute.store');

        // Security Notes
        Route::get('/security-notes', [SecurityNoteController::class, 'index'])->name('security-notes.index');

        // Patrol Locations
        Route::get('/patrol-location', [PatrolLocationController::class, 'index'])->name('patrol-location.index');
        Route::post('/patrol-location', [PatrolLocationController::class, 'store'])->name('patrol-location.store');
        Route::get('/patrol-location/{id}', [PatrolLocationController::class, 'show'])->name('patrol-location.show');
        Route::delete('/patrol-location/{id}', [PatrolLocationController::class, 'destroy'])->name('patrol-location.destroy');

        // Guard Patrols
        Route::get('/guard-patrol', [GuardPatrolController::class, 'index'])->name('guard-patrol.index');
        Route::get('/guard-patrol/checkin', [GuardPatrolController::class, 'checkin'])->name('guard-patrol.checkin');
        Route::post('/guard-patrol/checkin', [GuardPatrolController::class, 'submitCheckin'])->name('guard-patrol.submit');
        Route::get('/guard-patrol/resolve-qr', [GuardPatrolController::class, 'resolveQr'])->name('guard-patrol.resolveQr');

        // Building Shifts
        Route::get('/building-shift', [BuildingShiftController::class, 'index'])->name('building-shift.index');
        Route::post('/building-shift', [BuildingShiftController::class, 'store'])->name('building-shift.store');
        Route::delete('/building-shift/{id}', [BuildingShiftController::class, 'destroy'])->name('building-shift.destroy');

        // Guard Patrol Assignments
        Route::get('/patrol-assignment', [GuardPatrolAssignmentController::class, 'index'])->name('patrol-assignment.index');
        Route::post('/patrol-assignment', [GuardPatrolAssignmentController::class, 'store'])->name('patrol-assignment.store');

        // Duty Check-Ins
        Route::get('/duty-checkins', [\App\Http\Controllers\Admin\DutyCheckinController::class, 'index'])->name('duty-checkin.index');
        Route::post('/duty-checkins/interval', [\App\Http\Controllers\Admin\DutyCheckinController::class, 'updateInterval'])->name('duty-checkin.interval');
        Route::delete('/patrol-assignment/{id}', [GuardPatrolAssignmentController::class, 'destroy'])->name('patrol-assignment.destroy');

        // Delivery Entry Restriction
        Route::get('/delivery-restriction', [DeliveryRestrictionController::class, 'index'])->name('delivery-restriction.index');
        Route::put('/delivery-restriction', [DeliveryRestrictionController::class, 'update'])->name('delivery-restriction.update');

        // Community Activities
        Route::get('/community-activities', [ActivityAdminController::class, 'index'])->name('activity.index');
        Route::get('/community-activities/{id}', [ActivityAdminController::class, 'show'])->name('activity.show');
        Route::post('/community-activities/{id}/approve', [ActivityAdminController::class, 'approve'])->name('activity.approve');
        Route::post('/community-activities/{id}/reject', [ActivityAdminController::class, 'reject'])->name('activity.reject');
        Route::delete('/community-activities/{id}', [ActivityAdminController::class, 'delete'])->name('activity.delete');

        // Staff Attendance Management
        Route::prefix('staff')->name('admin.staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            // NOTE: /create must be declared before /{staff} so it isn't captured as a param.
            Route::get('/pending', [StaffController::class, 'pending'])->name('pending');
            Route::post('/{staff}/approve', [StaffController::class, 'approve'])->name('approve');
            Route::post('/{staff}/reject', [StaffController::class, 'reject'])->name('reject');
            Route::get('/create', [StaffController::class, 'create'])->name('create');
            Route::post('/store-type', [StaffController::class, 'storeType'])->name('store-type');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::post('/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{staff}', [StaffController::class, 'show'])->name('show');
            Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
            Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('attendance')->name('admin.attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/mark', [AttendanceController::class, 'markAttendance'])->name('mark');
            Route::get('/report', [AttendanceController::class, 'report'])->name('report');
        });

        // Meetings
        Route::get('/meeting', [MeetingController::class, 'index'])->name('meeting.index');
        Route::post('/meeting', [MeetingController::class, 'store'])->name('meeting.store');
        Route::delete('/meeting/{id}', [MeetingController::class, 'destroy'])->name('meeting.destroy');

        Route::resource('/notification', NotificationController::class);
        Route::get('/notification-history', [NotificationController::class, 'history'])->name('notification.history');
        Route::post('/notification-mark-all-read', [NotificationController::class, 'mark_all_as_read'])->name('notification.mark_all_as_read');
        Route::resource('/setting',SettingController::class);
        Route::get('/taxes',[SettingController::class, 'taxes']);
        Route::post('/update-taxes',[SettingController::class, 'update_taxes']);
        Route::get('/building-policy',[SettingController::class, 'building_policy']);
        Route::post('/update-building-policy',[SettingController::class, 'update_building_policy']);
        Route::get('/privacy-policy',[SettingController::class, 'privacy_policy']);
        Route::post('/update-privacy-policy',[SettingController::class, 'update_privacy_policy']);
        Route::get('/terms-conditions',[SettingController::class, 'terms_conditions']);
        Route::post('/update-terms-conditions',[SettingController::class, 'update_terms_conditions']);
        Route::get('/about-us',[SettingController::class, 'about_us']);
        Route::post('/update-about-us',[SettingController::class, 'update_about_us']);
        Route::get('/how-it-works',[SettingController::class, 'how_it_works']);
        Route::post('/update-how-it-works',[SettingController::class, 'update_how_it_works']);
        Route::get('/return-and-refund-policy',[SettingController::class, 'return_and_refund_policy']);
        Route::post('/update-return-and-refund-policy',[SettingController::class, 'update_return_and_refund_policy']);
        Route::get('/accidental-policy',[SettingController::class, 'accidental_policy']);
        Route::post('/update-accidental-policy',[SettingController::class, 'update_accidental_policy']);
        Route::get('/cancellation-policy',[SettingController::class, 'cancellation_policy']);
        Route::post('/update-cancellation-policy',[SettingController::class, 'update_cancellation_policy']);
        Route::get('/delete-account-policy',[SettingController::class, 'delete_account_policy']);
        Route::post('/update-delete-account-policy',[SettingController::class, 'update_delete_account_policy']);
        Route::get('/faqs',[SettingController::class, 'faqs']);
        Route::post('/update-faqs',[SettingController::class, 'update_faqs']);
    });
    
	Route::post('/logout',[AdminController::class, 'logout']);
});