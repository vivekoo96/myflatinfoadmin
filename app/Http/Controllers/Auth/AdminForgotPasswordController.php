<?php 

  

namespace App\Http\Controllers\Auth; 

  

use App\Http\Controllers\Controller;

use Illuminate\Http\Request; 

use DB; 

use Carbon\Carbon; 

use App\Models\User; 
use App\Models\Setting; 

use Mail; 

use Hash;

use Illuminate\Support\Str;

  

class AdminForgotPasswordController extends Controller

{

      public function showForgetPasswordForm()

      {
         return view('admin.forgot_password');
      }
      
      public function submitForgetPasswordForm(Request $request)

      {
            $request->validate([
                'email' => 'required|email|exists:users',
                'reset_method' => 'required|in:link,otp',
            ]);
            
            $email = $request->email;
            $user = User::where('email', $request->email)->first();
            $setting = Setting::first();
            $resetMethod = $request->reset_method;
            
            if ($resetMethod === 'otp') {
                // Generate 6-digit OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Delete any existing OTPs for this email
                DB::table('password_reset_otps')->where('email', $email)->delete();
                
                // Store OTP in database with 10-minute expiry
                DB::table('password_reset_otps')->insert([
                    'email' => $email,
                    'otp' => $otp,
                    'expires_at' => Carbon::now()->addMinutes(10),
                    'created_at' => Carbon::now(),
                    'is_used' => false
                ]);
                
                $info = array(
                    'otp' => $otp,
                    'name' => $user->name,
                    'logo' => $setting->logo,
                );
                
                Mail::send('email.forget_password_otp', $info, function ($message) use ($email) {
                    $message->to($email)->subject('Password Reset OTP');
                });
                
                return redirect('verify-otp')->with([
                    'success' => 'OTP sent to your email address',
                    'email' => $email
                ]);
            } else {
                // Original link method
                $token = Str::random(64);
                DB::table('password_resets')->insert([
                    'email' => $request->email, 
                    'token' => $token, 
                    'created_at' => Carbon::now()
                ]);
                
                $info = array(
                    'link' => url('reset-password/'.$token),
                    'name' => $user->name,
                    'logo' => $setting->logo,
                );
                
                Mail::send('email.forget_password', $info, function ($message) use ($email) {
                    $message->to($email)->subject('Reset Password');
                });
                
                return back()->with('success', 'Reset Password Link sent to your email');
            }
      }
      
      public function showResetPasswordForm($token) {
        $password_reset = DB::table('password_resets')->where(['token'=> $token])->first();
        if(!$password_reset){
            return redirect('forget-password')->with('error','Token expired, send again');
        }
        return view('admin.reset_password',compact('password_reset'));
      }

      public function submitResetPasswordForm(Request $request)
      {
          $request->validate([
                'email' => 'required|email|exists:users',
                'password' =>[
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                'confirmed',
            ],
                'password_confirmation' => 'required',
          ]);
          $updatePassword = DB::table('password_resets')->where(['email' => $request->email, 'token' => $request->token])->first();
          if(!$updatePassword){
              return back()->withInput()->with('error', 'Invalid token!');
          }
          $user = User::where('email', $request->email)->first();
          $user->password = Hash::make($request->password);
          $user->save();
                      
          DB::table('password_resets')->where(['email'=> $request->email])->delete();
          return redirect('/')->with('success', 'Your password has been changed!');
      }

      public function showVerifyOtpForm(Request $request)
      {
          $email = $request->session()->get('email') ?? $request->get('email');
          if (!$email) {
              return redirect('forget-password')->with('error', 'Session expired. Please try again.');
          }
          return view('admin.verify_otp', compact('email'));
      }

      public function verifyOtp(Request $request)
      {
          $request->validate([
              'email' => 'required|email|exists:users',
              'otp' => 'required|digits:6',
          ]);

          $email = $request->email;
          $otp = $request->otp;

          // Find valid OTP
          $otpRecord = DB::table('password_reset_otps')
              ->where('email', $email)
              ->where('otp', $otp)
              ->where('is_used', false)
              ->where('expires_at', '>', Carbon::now())
              ->first();

          if (!$otpRecord) {
              return back()->with('error', 'Invalid or expired OTP. Please try again.');
          }

          // Mark OTP as used
          DB::table('password_reset_otps')
              ->where('id', $otpRecord->id)
              ->update(['is_used' => true]);

          // Generate a temporary token for password reset
          $token = Str::random(64);
          DB::table('password_resets')->insert([
              'email' => $email,
              'token' => $token,
              'created_at' => Carbon::now()
          ]);

          return redirect('reset-password/' . $token)->with('success', 'OTP verified successfully. Please set your new password.');
      }

      public function resendOtp(Request $request)
      {
          $email = $request->get('email');
          if (!$email) {
              return redirect('forget-password')->with('error', 'Invalid request.');
          }

          $user = User::where('email', $email)->first();
          if (!$user) {
              return redirect('forget-password')->with('error', 'User not found.');
          }

          // Check if user has requested OTP recently (rate limiting)
          $recentOtp = DB::table('password_reset_otps')
              ->where('email', $email)
              ->where('created_at', '>', Carbon::now()->subMinutes(2))
              ->first();

          if ($recentOtp) {
              return back()->with('error', 'Please wait 2 minutes before requesting a new OTP.');
          }

          // Generate new OTP
          $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
          $setting = Setting::first();

          // Delete old OTPs
          DB::table('password_reset_otps')->where('email', $email)->delete();

          // Store new OTP
          DB::table('password_reset_otps')->insert([
              'email' => $email,
              'otp' => $otp,
              'expires_at' => Carbon::now()->addMinutes(10),
              'created_at' => Carbon::now(),
              'is_used' => false
          ]);

          $info = array(
              'otp' => $otp,
              'name' => $user->name,
              'logo' => $setting->logo,
          );

          Mail::send('email.forget_password_otp', $info, function ($message) use ($email) {
              $message->to($email)->subject('Password Reset OTP');
          });

          return back()->with('success', 'New OTP sent to your email address.');
      }

}