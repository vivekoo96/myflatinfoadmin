<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Building;
use App\Models\Role;
use App\Models\BuildingUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use \Auth;

class SettingController extends Controller
{

    public function index()
    {
       if(Auth::User()->role == 'BA')
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        $setting = Setting::first();
        // Determine role and building users for initial render
        $role = null;
        $building_users = collect();
        if(!empty($building->treasurer_type)){
            // normalize any known typos (e.g., 'Presedent' -> 'President')
            $treasurerType = $building->treasurer_type;
            if (strtolower($treasurerType) == 'presedent') {
                $treasurerType = 'President';
            }
            $role = Role::where('name', $treasurerType)->first();
            if($role){
                $building_users = BuildingUser::where('building_id', $building->id)->where('role_id', $role->id)->with('user')->get();
            }
        }
        return view('admin.settings.index',compact('building','setting','role','building_users'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        // Phone validation
        // Support both 10-digit Indian numbers and ones prefixed with +91 or 91. We'll normalize before saving.
        $rules = [
            'call_support_number' => ['required', 'regex:/^(?:\+?91)?[6-9][0-9]{9}$/'],
            'whatsapp_support_number' => ['required', 'regex:/^(?:\+?91)?[6-9][0-9]{9}$/'],
        ];
        $messages = [
            'call_support_number.regex' => 'Please enter a valid 10-digit Indian mobile number starting from 6 to 9.',
            'whatsapp_support_number.regex' => 'Please enter a valid 10-digit Indian mobile number starting from 6 to 9.',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $building = Auth::User()->building;
        $building->razorpay_key = $request->razorpay_key;
        $building->razorpay_secret = $request->razorpay_secret;
        $building->gst_no = $request->gst_no;
        // $building->classified_limit_within_building = $request->classified_limit_within_building;
        // $building->classified_limit_all_building = $request->classified_limit_all_building;
        // Normalize phone numbers to 10-digit format before saving
        $callNumber = preg_replace('/\D+/', '', $request->call_support_number);
        if (strlen($callNumber) == 12 && substr($callNumber, 0, 2) == '91') {
            $callNumber = substr($callNumber, 2);
        }
        $whatsappNumber = preg_replace('/\D+/', '', $request->whatsapp_support_number);
        if (strlen($whatsappNumber) == 12 && substr($whatsappNumber, 0, 2) == '91') {
            $whatsappNumber = substr($whatsappNumber, 2);
        }
        $building->call_support_number = $callNumber;
        $building->whatsapp_support_number = $whatsappNumber;
        // Normalize treasurer type to correct spelling if user submits a typo
        $treasurerType = $request->treasurer_type;
        if (strtolower($treasurerType) == 'presedent') {
            $treasurerType = 'President';
        }
        $building->treasurer_type = $treasurerType;
        $building->treasurer_id = $request->treasurer_id;
        $building->save();
        return redirect()->back()->with('success','Setting saved');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
    
    public function privacy_policy()
    {
    $privacy_policy = Setting::pluck('privacy_policy')->first();
    $privacy_policy = html_entity_decode($privacy_policy);
    return view('admin.settings.privacy_policy',compact('privacy_policy'));
    }
    
    public function terms_conditions()
    {
        $terms_conditions = Setting::pluck('terms_conditions')->first();
        return view('admin.settings.terms_conditions',compact('terms_conditions'));
    }
    
    public function cancellation_policy()
    {
        $cancellation_policy = Setting::pluck('cancellation_policy')->first();
        return view('admin.settings.cancellation_policy',compact('cancellation_policy'));
    }
    
    public function building_policy()
    {
        $building_policy = Building::where('id',Auth::User()->building_id)->pluck('building_policy')->first();
        return view('admin.settings.building_policy',compact('building_policy'));
    }
    
    public function update_building_policy(Request $request)
    {
        $building = Building::where('id',Auth::User()->building_id)->first();
        $building->building_policy = $request->building_policy;
        $building->save();
        return redirect()->back()->with('success','Building policy updated');
    }
    
    
}
