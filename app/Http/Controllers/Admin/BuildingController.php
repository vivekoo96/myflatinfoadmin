<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\Setting;
use App\Models\User;
use App\Models\BuildingUser;
use App\Models\City;

use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{

    public function index()
    {
        $buildings = Auth::User()->buildings;
        $cities = City::all();
        return view('admin.building.index',compact('buildings','cities'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'name' => 'required',
            'owner_name' => 'required',
            'owner_contact_no' => 'required',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required',
            'zip_code' => 'required',
            'status' => 'required|in:Active,Pending',
            'image' => 'nullable|image|max:2048',
        ];
    
        $msg = 'Building added Susccessfully';
        $building = new Building();
    
        if ($request->id) {
            $building = Building::withTrashed()->find($request->id);
            $msg = 'Building updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['JPEG','jpg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     $check = in_array($extension,$allowedfileExtension);
        //     // if($check){
        //         $file_path = public_path('/images/buildings'.$building->image);
        //         if(file_exists($file_path) && $building->image != '')
        //         {
        //             unlink($file_path);
        //         }
        //         $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //         $filename = substr(str_shuffle(str_repeat($pool, 5)), 0, 12) .'.'.$extension;
        //         $path = $file->move(public_path('/images/buildings'), $filename);
        //         $building->image = $filename;
        //     // }
        // }
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($building->image_filename)) {
                $file_path = public_path('images/' . $building->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'buildings/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/buildings/'), $filename);
            $building->image = $filename;
        }
        
        $building->user_id = $request->user_id;
        $building->name = $request->name;
        $building->owner_name = $request->owner_name;
        $building->owner_contact_no = $request->owner_contact_no;
        $building->city_id = $request->city_id;
        $building->address = $request->address;
        $building->zip_code = $request->zip_code;
        $building->status = $request->status;
          if (!$request->id) {
            try {
                $global = Setting::first();
            } catch (\Exception $e) {
                $global = null;
            }
            $building->classified_limit_within_building = $global && isset($global->classified_limit_within_building) ? $global->classified_limit_within_building : 3;
            $building->classified_limit_all_building = $global && isset($global->classified_limit_all_building) ? $global->classified_limit_all_building : 1;
        }
        $building->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        $building = Building::where('id',$id)->withTrashed()->with([
            'user', 
            'city', 
            'blocks.flats', 
            'flats.block', 
            'flats.owner', 
            'flats.tanent'
        ])->first();
        if(!$building){
            return redirect()->route('building.index');
        }
        return view('admin.building.show',compact('building'));
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
        $building = Building::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $building->delete();
        }else{
            $building->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_building_status(Request $request)
    {
        $building = Building::where('id',$request->id)->withTrashed()->first();
        if($building->status == 'Active'){
            $building->status = 'Inactive';
        }else{
            $building->status = 'Active';
        }
        $building->save();
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function delete_building_user(Request $request)
    {
        $building_user = BuildingUser::where('id',$request->id)->withTrashed()->first();
        if($request->action == 'delete'){
            $building_user->delete();
        }else{
            $building_user->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
