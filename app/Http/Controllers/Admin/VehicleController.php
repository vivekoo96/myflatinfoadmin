<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Building;
use App\Models\Vehicle;
use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{

    public function index()
    {
          if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" ||  Auth::user()->selectedRole->name == "Security" || Auth::User()->hasPermission('custom.vehicles') )
       
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.vehicle.index',compact('building'));
    }
    
    public function vehicle_inouts()
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.vehiclesinouts') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.vehicle.inouts',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.vehicles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $vehicle = Vehicle::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$vehicle){
            return redirect()->route('vehicle.index');
        }
        return view('admin.vehicle.show',compact('vehicle'));
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
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.vehicles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $vehicle = Vehicle::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $vehicle->delete();
        }else{
            $vehicle->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    public function update_vehicle_inout(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.vehiclesinouts') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|in:In,Out'
        ]);
        
        $vehicleInout = \App\Models\VehicleInout::findOrFail($request->id);
        $vehicleInout->update([
            'flat_id' => $request->flat_id,
            'vehicle_id' => $request->vehicle_id,
            'type' => $request->type
        ]);
        
        return redirect()->back()->with('success', 'Vehicle inout updated successfully!');
    }
    
    public function destroy_vehicle_inout(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.vehiclesinouts') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $vehicleInout = \App\Models\VehicleInout::withTrashed()->findOrFail($request->id);
        
        if($request->action == 'delete'){
            $vehicleInout->delete();
        }else{
            $vehicleInout->restore();
        }
        
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
