<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parking;
use \Auth;
use \DB;
use Illuminate\Validation\Rule;


class ParkingController extends Controller
{

    public function index()
    {
        //  || Auth::User()->hasPermission('custom.vehicles') 
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') ||  Auth::User()->hasPermission('custom.information') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.parking.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA')
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'block_id'    => 'required|exists:blocks,id',
            'name'        => [
                'required',
                Rule::unique('parkings')
                    ->where(function ($query) use ($request) {
                        return $query->where('block_id', $request->block_id);
                    })
                    ->ignore($request->id)
            ],
            'status'      => 'required|in:Active,Inactive',
        ];
    
        $msg = 'Parking added successfully';
        $parking = new Parking();
    
        if ($request->id) {
            $parking = Parking::withTrashed()->find($request->id);
            $msg = 'Parking Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules, [
            'name.unique' => 'This name already exists in this block. Please choose a different name.'
        ]);

        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first())->withInput();
        }
        $parking->building_id = Auth::User()->building_id;
        $parking->block_id = $request->block_id;
        $parking->name = $request->name;
        $parking->status = $request->status;
        $parking->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.vehicles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $parking = Parking::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$parking){
            return redirect()->route('parking.index');
        }
        return view('admin.parking.show',compact('parking'));
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
        $parking = Parking::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $parking->status = 'Inactive';
            $parking->save();
            $parking->delete();
        }else{
            $parking->status = 'Active';
            $parking->save();
            $parking->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function get_parkings(Request $request)
    {
        $block_id = $request->block_id;
        $parking_id = $request->parking_id;
        $parkings = Parking::where('block_id',$block_id)->where('status','Active')->get();
        return view('partials.parkings',compact('parkings','parking_id'));
        
    }
}
