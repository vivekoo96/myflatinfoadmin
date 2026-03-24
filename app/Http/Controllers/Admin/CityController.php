<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{

    public function index()
    {
        $cities = City::orderBy('id','asc')->withTrashed()->get();
        return view('admin.city.index',compact('cities'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:cities,name',
        ];
    
        $msg = 'City Added';
        $city = new City();
    
        if ($request->id) {
            $city = City::withTrashed()->find($request->id);
            $rules['name'] .= ',' . $city->id;
            $msg = 'City Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
    
        $city->name = $request->name;
        $city->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        $city = City::where('id',$id)->withTrashed()->first();
        if(!$city){
            return redirect()->route('city.index');
        }
        return view('admin.city.show',compact('city'));
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
        $city = City::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $city->delete();
        }else{
            $city->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
