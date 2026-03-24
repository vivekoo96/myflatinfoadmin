<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Block;
use App\Models\Building;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

use \Auth;

class BlockController extends Controller
{

    public function index()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::User()->hasPermission('custom.information') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.block.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
    
        $rules = [
            'name' => [
                'required',
                'min:1',
                'max:20',
                Rule::unique('blocks')
                ->where(fn($q) => $q->where('building_id', Auth::user()->building_id))
                ->ignore($request->id),
            ],
            'status' => 'required|in:Active,Inactive',
        ];
    
        $validation = Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
    
        $msg = 'Block Added';
        $block = new Block();
    
        // If you're editing (update)
        if ($request->id) {
            $block = Block::withTrashed()->find($request->id);
            $msg = 'Block Updated';
        }
    
        $block->building_id = Auth::user()->building_id;
        $block->name = $request->name;
        $block->status = $request->status;
        $block->save();
    
        return redirect()->back()->with('success', $msg);
    }


    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.information'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $block = Block::where('id',$id)->withTrashed()->first();
        if(!$block){
            return redirect()->route('block.index');
        }
        return view('admin.block.show',compact('block'));
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
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $block = Block::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $block->status = 'Inactive';
            $block->save();
            $block->delete();
        }else{
            $block->status = 'Active';
            $block->save();
            $block->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
