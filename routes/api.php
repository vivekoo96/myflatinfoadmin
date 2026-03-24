<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\TruthScreenController;
use App\Http\Controllers\Api\WebhookController;

// Route::prefix('customer')->group(function () {
    Route::post('register',[CustomerController::class,'register']);
    Route::post('login',[CustomerController::class,'login']);
    Route::post('send-otp',[CustomerController::class,'send_otp']);
    Route::post('resend-otp',[CustomerController::class,'resend_otp']);
    
    Route::post('forget-password',[CustomerController::class,'forget_password']);
    Route::post('verify-otp',[CustomerController::class,'verify_otp']);
    Route::post('logout',[CustomerController::class,'logout']);

    Route::post('get-setting',[CustomerController::class,'get_setting']);
    Route::post('get-logo',[CustomerController::class,'get_logo']);
    Route::post('onboarding',[CustomerController::class,'onboarding']);
    Route::post('facility-failed-webhook',[WebhookController::class,'facility_failed_webhook']);
    Route::post('send-push-notification',[CustomerController::class,'send_push_notification']);
   
    
    Route::middleware(['auth:api'])->group(function (){
        
    Route::post('get-profile2',[CustomerController::class,'profile2']);
    Route::post('get-notifications',[CustomerController::class,'get_notifications']);
        
       Route::post('mark-notification-read-user',[CustomerController::class,'mark_notification_read']);
        Route::post('mark-all-notifications-read',[CustomerController::class,'mark_all_notifications_read']);
      Route::post('clear-all-notifications-user',[CustomerController::class,'clear_all_notifications_user']);

        Route::post('test-notification',[CustomerController::class,'test_notification']);
        
        Route::post('update-password',[CustomerController::class,'update_password']);
        
        Route::post('change-password',[CustomerController::class,'change_password']);
        
        Route::post('get-flats',[CustomerController::class,'get_flats']);
        Route::post('select-flat',[CustomerController::class,'select_flat']);

      Route::post('get-issue-comments-user',[CustomerController::class,'get_issue_comments_user']);


        Route::middleware(['flat'])->group(function (){
        Route::post('get-tokenData',[CustomerController::class,'get_tokenData']);
            Route::post('get-treasurer',[CustomerController::class,'get_treasurer']);
            Route::post('get-ads',[CustomerController::class,'get_ads']);
            Route::post('get-overdues',[CustomerController::class,'get_overdues']);
            Route::post('payment-options',[CustomerController::class,'payment_options']);
            Route::post('profile',[CustomerController::class,'profile']);
            Route::post('update-profile',[CustomerController::class,'update_profile']);
            Route::post('get-noticeboards',[CustomerController::class,'get_noticeboards']);
            Route::post('get-events',[CustomerController::class,'get_events']);
            Route::post('single-event-history',[CustomerController::class,'single_event_history']);
            Route::post('get-classifieds',[CustomerController::class,'get_classifieds']);
            Route::post('my-classifieds',[CustomerController::class,'my_classifieds']);
            Route::post('create-classified',[CustomerController::class,'create_classified']);
            Route::post('update-classified',[CustomerController::class,'create_classified']);
            Route::post('delete-classified-photo',[CustomerController::class,'delete_classified_photo']);
            Route::post('delete-classified',[CustomerController::class,'delete_classified']);
            Route::post('get-departments',[CustomerController::class,'get_departments']);
            
            
            Route::post('get-staff-directory',[CustomerController::class,'get_staff_directory']);
            Route::post('get-flat-directory',[CustomerController::class,'get_flats_directory']);
            
            
            Route::post('raise-an-issue',[CustomerController::class,'raise_an_issue']);
            Route::post('user-issues',[CustomerController::class,'user_issues']);
            Route::post('add-comment',[CustomerController::class,'add_comment']);
            Route::post('add-reply',[CustomerController::class,'add_reply']);
            Route::post('get-facilities',[CustomerController::class,'get_facilities']);
            Route::post('get-facility-timings',[CustomerController::class,'get_facility_timings']);
            Route::post('create-facility-order',[CustomerController::class,'create_facility_order']);
            Route::post('verify-facility-order',[CustomerController::class,'verify_facility_order']);
            Route::post('book-facility',[CustomerController::class,'book_facility']);
            Route::post('cancel-bookings',[CustomerController::class,'cancel_bookings']);
            Route::post('delete-booked-facility',[CustomerController::class,'delete_facility']);
            Route::post('my-bookings',[CustomerController::class,'my_bookings']);
            Route::post('get-bookings-by-reciept',[CustomerController::class,'get_bookings_by_reciept']);
            Route::post('book-offline-facility',[CustomerController::class,'book_offline_facility']);
            
            Route::post('create-own-vehicle',[CustomerController::class,'create_vehicle']);
            Route::post('update-own-vehicle',[CustomerController::class,'update_vehicle']);
            Route::post('delete-own-vehicle',[CustomerController::class,'delete_vehicle']);
            
            Route::post('create-visitor',[CustomerController::class,'create_visitor']);
            Route::post('update-visitor',[CustomerController::class,'create_visitor']);
            Route::post('delete-visitor',[CustomerController::class,'delete_visitor']);
            Route::post('update-checkin-checkout-status',[CustomerController::class,'update_checkin_checkout_status']);
            Route::post('create-gate-pass',[CustomerController::class,'create_gate_pass']);
            Route::post('get-gate-passes',[CustomerController::class,'get_gate_passes']);
            Route::post('take-gate-pass-action',[CustomerController::class,'take_gate_pass_action']);
            Route::post('gate-pass-details',[CustomerController::class,'gate_pass_details']);
            Route::post('my-visitor-in-out-history',[CustomerController::class,'my_visitor_in_out_history']);
            Route::post('add-family-member',[CustomerController::class,'create_family_member']);
            Route::post('update-family-member',[CustomerController::class,'create_family_member']);
            Route::post('delete-family-member',[CustomerController::class,'delete_family_member']);
            Route::post('my-family-members',[CustomerController::class,'my_family_members']);
            Route::post('get-visitors',[CustomerController::class,'get_visitors']);
            Route::post('get-vehicles',[CustomerController::class,'get_vehicles']);
            Route::post('get-my-parcels',[CustomerController::class,'get_my_parcels']);
            Route::post('take-parcel-action',[CustomerController::class,'take_parcel_action']);
            Route::post('update-parcel-status',[CustomerController::class,'update_parcel_status']);
            Route::post('take-visitor-action',[CustomerController::class,'take_visitor_action']);
            Route::post('get-visitor-details',[CustomerController::class,'get_visitor_details']);
            
            Route::post('maintenance-payments',[CustomerController::class,'maintenance_payments']);
            Route::post('create-maintenance-payment-order',[CustomerController::class,'create_maintenance_payment_order']);
            Route::post('verify-maintenance-payment-signature',[CustomerController::class,'verify_maintenance_payment_signature']);
            
            Route::post('maintenance-invoice',[CustomerController::class,'maintenance_invoice']);
            Route::post('maintenance-reciept',[CustomerController::class,'maintenance_reciept']);
            
            Route::post('essential-payments',[CustomerController::class,'essential_payments']);
            Route::post('create-essential-payment-order',[CustomerController::class,'create_essential_payment_order']);
            Route::post('verify-essential-payment-signature',[CustomerController::class,'verify_essential_payment_signature']);
            
            Route::post('create-event-payment-order',[CustomerController::class,'create_event_payment_order']);
            Route::post('verify-event-payment-signature',[CustomerController::class,'verify_event_payment_signature']);
            
            Route::post('essential-history',[CustomerController::class,'essential_history']);
            Route::post('event-history',[CustomerController::class,'event_history']);

            Route::post('corpus-fund',[CustomerController::class,'corpus_fund']);
            Route::post('society-fund',[CustomerController::class,'society_fund']);
            Route::post('get-model-data',[CustomerController::class,'get_model_data']);
            Route::post('get-parkings',[CustomerController::class,'get_parkings']);

            Route::post('get-access',[CustomerController::class,'get_access']);
            Route::post('dnd-mode',[CustomerController::class,'dnd_mode']);
            Route::post('update-dnd-mode',[CustomerController::class,'update_dnd_mode']);
            Route::post('building-policy',[CustomerController::class,'building_policy']);
            
        });
        //Route::post('create-razorpay-order', [CustomerController::class,'create_razorpay_order']);
        //Route::post('verify-razorpay-signature', [CustomerController::class,'verify_razorpay_signature']);
            
        //Route::post('get-notifications', [CustomerController::class,'get_notifications']);
        //Route::post('read-notification', [CustomerController::class,'read_notification']);
        
        
        // guards route
        
        Route::post('get-gates',[CustomerController::class,'get_gates']);
        Route::post('select-gate',[CustomerController::class,'select_gate']);
        Route::middleware(['gate'])->group(function (){
               Route::post('update-profile-se',[CustomerController::class,'update_profile_se']);
             Route::post('get-notifications-se',[CustomerController::class,'get_notifications_se']);
             Route::post('mark-notification-read-se',[CustomerController::class,'mark_notification_read']);
             Route::post('mark-all-notifications-read-se',[CustomerController::class,'mark_all_notifications_read_se']);
             Route::post('clear-all-notifications-se',[CustomerController::class,'clear_all_notifications_serol']);
             
            Route::post('get-building-access',[CustomerController::class,'get_building_access']);
            Route::post('security-profile',[CustomerController::class,'security_profile']);
            Route::post('get-building-flats',[CustomerController::class,'get_building_flats']);
            Route::post('get-building-visitors',[CustomerController::class,'get_building_visitors']);
            Route::post('get-building-visitors-gatepass',[CustomerController::class,'get_building_visitors_gatepass']);
            Route::post('get-building-visitors-history',[CustomerController::class,'get_building_visitors_history']);
            Route::post('visitor-details',[CustomerController::class,'visitor_details']);
            Route::post('get-building-vehicles',[CustomerController::class,'get_building_vehicles']);
            Route::post('get-flat-vehicles',[CustomerController::class,'get_flat_vehicles']);
            Route::post('create-vehicle',[CustomerController::class,'create_vehicle']);
            Route::post('create-outsider-vehicle',[CustomerController::class,'create_vehicle']);
            Route::post('all-flats-vehicles',[CustomerController::class,'all_flat_vehicles']);
            
            Route::post('update-vehicle',[CustomerController::class,'create_vehicle']);
            Route::post('delete-vehicle',[CustomerController::class,'delete_vehicle']);
            Route::post('visitor-in-out',[CustomerController::class,'visitor_in_out']);
            Route::post('visitor-in-out-history',[CustomerController::class,'visitor_in_out_history']);
            Route::post('vehicle-in-out',[CustomerController::class,'vehicle_in_out']);
            Route::post('all-vehicles',[CustomerController::class,'all_vehicles']);
            Route::post('vehicle-in-out-history',[CustomerController::class,'vehicle_in_out_history']);
            Route::post('search-flat',[CustomerController::class,'search_flat']);
            Route::post('create-unplanned-visitor',[CustomerController::class,'create_unplanned_visitor']);
            Route::post('resend-visitor-request',[CustomerController::class,'resend_visitor_request']);
            Route::post('cancel-visitor-request',[CustomerController::class,'cancel_visitor_request']);
            Route::post('get-building-parcels',[CustomerController::class,'get_building_parcels']);
            Route::post('get-flat-parcels',[CustomerController::class,'get_flat_parcels']);
            Route::post('create-parcel',[CustomerController::class,'create_parcel']);
            Route::post('update-parcel',[CustomerController::class,'create_parcel']);
            Route::post('update-security-parcel-status',[CustomerController::class,'update_security_parcel_status']);
            Route::post('resend-recieve-request',[CustomerController::class,'resend_recieve_request']);
            Route::post('parcel-handover-to-owner',[CustomerController::class,'parcel_handover_to_owner']);
            Route::post('complete-visitor-journey',[CustomerController::class,'complete_visitor_journey']);
            Route::post('get-todays-completed-visitors',[CustomerController::class,'get_todays_completed_visitors']);
            
            Route::post('get-building-gate-passes',[CustomerController::class,'get_building_gate_passes']);
            Route::post('resend-gate-pass-request',[CustomerController::class,'resend_gate_pass_request']);
            Route::post('take-gate-pass-action-building',[CustomerController::class,'take_gate_pass_action_building']);
            Route::post('security-gate-pass-details',[CustomerController::class,'gate_pass_details']);
            
            Route::post('extend-stay-time',[CustomerController::class,'extend_stay_time']);
            Route::post('missing-alert',[CustomerController::class,'missing_alert']);
            
            Route::post('get-homepage-count',[CustomerController::class,'get_homepage_count']);
            
        });
        
        // role route  //20nov2025 11:48
        Route::post('my-departments',[CustomerController::class,'my_departments']);
        Route::post('select-department',[CustomerController::class,'select_department']);
        
        Route::middleware(['department'])->group(function (){
            Route::post('update-profile-role',[CustomerController::class,'update_profile']);
            Route::post('mark-notification-read',[CustomerController::class,'mark_notification_read']);
            Route::post('mark-all-notifications-read-role',[CustomerController::class,'mark_all_notifications_read_se']);
            Route::post('mark-all-notifications-read-role',[CustomerController::class,'mark_all_notifications_read_se']);
            Route::post('clear-all-notifications-role',[CustomerController::class,'clear_all_notifications_serol']);
            
            Route::post('department-profile',[CustomerController::class,'department_profile']);
            Route::post('get-issues',[CustomerController::class,'get_issues']);
            Route::post('accept-issue',[CustomerController::class,'accept_issue']);
            Route::post('issue-history',[CustomerController::class,'issue_history']);
            Route::post('add-issue-comment',[CustomerController::class,'add_comment']);
            Route::post('add-issue-reply',[CustomerController::class,'add_reply']);
            Route::post('update-issue-status',[CustomerController::class,'update_issue_status']);
            Route::post('get-notifications-department',[CustomerController::class,'get_notifications_department']);
            
            Route::post('get-issue-comments',[CustomerController::class,'get_issue_comments']);
            Route::post('get-comment-replies',[CustomerController::class,'get_comment_replies']);
        });
    });
    
    // accounts routes
    Route::prefix('accounts')->group(function (){
          Route::post('register',[CustomerController::class,'register']);
        Route::post('get-notifications',[CustomerController::class,'get_notifications']);
        Route::post('update-profile-accounts',[CustomerController::class,'update_profile']);
        Route::post('login',[AccountController::class,'login']);
        Route::post('send-otp',[AccountController::class,'send_otp']);
        Route::post('resend-otp',[AccountController::class,'resend_otp']);
        Route::post('forget-password',[AccountController::class,'forget_password']);
        Route::post('verify-otp',[AccountController::class,'verify_otp']);
    
        Route::post('get-setting',[AccountController::class,'get_setting']);
        Route::post('get-logo',[AccountController::class,'get_logo']);
        Route::post('onboarding',[AccountController::class,'onboarding']);
        
        Route::middleware(['auth:api','accounts'])->group(function (){
            Route::post('profile',[AccountController::class,'profile']);
            Route::post('update-password',[AccountController::class,'update_password']);
            Route::post('change-password',[AccountController::class,'change_password']);
            
            Route::post('get-buildings',[AccountController::class,'get_buildings']);
            Route::post('select-building',[AccountController::class,'select_building']);
            
            Route::middleware(['building'])->group(function (){
                Route::post('get-flats',[AccountController::class,'get_flats']);
                Route::post('flat-details',[AccountController::class,'flat_details']);
                Route::post('update-corpus-fund',[AccountController::class,'update_corpus_fund']);
                
                Route::post('get-opening-balance',[AccountController::class,'get_opening_balance']);
                Route::post('update-opening-balance',[AccountController::class,'update_opening_balance']);
                Route::post('income-and-expenditure',[AccountController::class,'income_and_expenditure']);
                Route::post('get-model-data',[AccountController::class,'get_model_data']);
                Route::post('form-payments',[AccountController::class,'form_payments']);
                Route::post('form-store-payment',[AccountController::class,'form_store_payment']);
                Route::post('form-reciepts',[AccountController::class,'form_reciepts']);
                Route::post('form-store-reciept',[AccountController::class,'form_store_reciept']);
                  Route::post('download-expense-receipt',[AccountController::class,'download_expense_receipt']);
                
                Route::post('generate-maintenance',[AccountController::class,'generate_maintenance']);
                Route::post('add-new-maintenance',[AccountController::class,'add_new_maintenance']);
                Route::post('update-maintenance',[AccountController::class,'add_new_maintenance']);
                Route::post('manage-maintenance',[AccountController::class,'manage_maintenance']);
                Route::post('pay-maintenance',[AccountController::class,'pay_maintenance']);
                Route::post('pay-maintenance-bill',[AccountController::class,'pay_maintenance_bill']);
                Route::post('maintenance-invoice',[AccountController::class,'maintenance_invoice']);
                Route::post('maintenance-reciept',[AccountController::class,'maintenance_reciept']);
                
                Route::post('get-events',[AccountController::class,'get_events']);
                Route::post('add-new-event',[AccountController::class,'add_new_event']);
                Route::post('update-event',[AccountController::class,'add_new_event']);
                Route::post('event-payments',[AccountController::class,'event_payments']);
                Route::post('add-event-payment',[AccountController::class,'store_event_payment']);
                Route::post('event-reciept',[AccountController::class,'event_reciept']);
                Route::post('get-user-by-email',[AccountController::class,'get_user_by_email']);
                
                Route::post('get-essentials',[AccountController::class,'get_essentials']);
                Route::post('add-new-essential',[AccountController::class,'store_essential']);
                Route::post('update-essential',[AccountController::class,'store_essential']);
                Route::post('essential-payments',[AccountController::class,'essential_payments']);
                Route::post('store-essential-payment',[AccountController::class,'store_essential_payment']);
                Route::post('essential-invoice',[AccountController::class,'essential_invoice']);
                Route::post('essential-reciept',[AccountController::class,'essential_reciept']);
                
                Route::post('get-bookings',[AccountController::class,'get_bookings']);
                Route::post('get-facilities',[AccountController::class,'get_facilities']);
                Route::post('society-fund',[AccountController::class,'society_fund']);
            });
            
        });
    });
    
    
// });




