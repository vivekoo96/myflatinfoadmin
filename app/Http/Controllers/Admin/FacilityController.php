<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Building;
use App\Models\Booking;
use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && $user->building && $user->building->payment_is_active === 'Yes' && $user->building->facility_is_active === 'Yes' ) {
                if($user->building->razorpay_key === '' || $user->building->razorpay_secret === '')
                return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set settings, please set that first and try again');
            }

            return $next($request);
        });
    }
    
    public function index()
    {
         if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Facility" || Auth::User()->hasPermission('custom.facilities') )
        
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $building = Auth::User()->building;
        $bookings = Booking::where('user_id', $user->id)
            ->where('building_id', $building->id)
            ->with(['timing','facility'])
            ->get()
            ->groupBy('reciept_no')
            ->map(function ($groupedBookings, $recieptNo) {
                return [
                    'reciept_no' => $recieptNo,
                    'bookings' => $groupedBookings,
                    'transaction' => optional($groupedBookings->first()->transaction),
                    'order' => optional($groupedBookings->first()->order),
                    'facility' => optional($groupedBookings->first()->facility),
                ];
            })
            ->values();
        return view('admin.facility.index',compact('building','bookings'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('facility') || Auth::User()->hasPermission('custom.facilities') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        // dd($request->all());
        $rules = [
            'name' => 'required',
            'max_booking' => 'required|int|min:0',
            'per_user_max_booking' => 'required|int|min:0',
            'status' => 'required|in:Active,Inactive,Closed',
            'icon' => 'nullable|image',
            'gst' => 'required',
            'color' => 'required',
        ];
    
        $msg = 'Facility added successfully';
        $facility = new Facility();
    
        if ($request->id) {
            $facility = Facility::withTrashed()->find($request->id);
            $msg = 'Facility Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $facility->building_id = Auth::User()->building_id;
        $facility->name = $request->name;
        $facility->booking_type = $request->booking_type;
        $facility->max_booking = $request->max_booking;
        $facility->per_user_max_booking = $request->per_user_max_booking;
        $facility->status = $request->status;
        $facility->gst = $request->gst;
        $facility->color = $request->color;
        $facility->closing_reason = $request->closing_reason ?? '';
        // if($request->hasFile('icon')) {
        //     $file= $request->file('icon');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($facility->getIconFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/facilities/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $facility->icon = $filename;
        // }
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($facility->icon_filename)) {
                $file_path = public_path('images/' . $facility->icon_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'facilities/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/facilities/'), $filename);
            $facility->icon = $filename;
        }
        $facility->save();
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('facility') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.facilities') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $facility = Facility::where('id',$id)->withTrashed()->first();
        if(!$facility){
            return redirect()->route('facility.index');
        }
        return view('admin.facility.show',compact('facility'));
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
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('facility') || Auth::User()->hasPermission('custom.facilities') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $facility = Facility::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $facility->delete();
        }else{
            $facility->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
