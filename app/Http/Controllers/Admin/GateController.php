<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gate;
use Illuminate\Validation\Rule;
use \Auth;

class GateController extends Controller
{



    public function index()
    {
        
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" ||  Auth::user()->selectedRole->name == "Security" || Auth::User()->hasPermission('custom.information') )
      
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.gate.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            // 'building_id' => 'required|exists:buildings,id',
            'block_id' => 'required|exists:blocks,id',
            'name' => [
                'required',
                Rule::unique('gates')
                    ->where(function ($query) use ($request) {
                        return $query->where('block_id', $request->block_id);
                    })
                    ->ignore($request->id), // ignore current flat in case of edit
            ],
            'status' => 'required|in:Active,Inactive',
        ];
    
        $msg = 'Gate added successfully';
        $gate = new Gate();
    
        if ($request->id) {
            $gate = Gate::withTrashed()->find($request->id);
            $msg = 'Gate Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first())->withInput();
        }
        $gate->building_id = Auth::User()->building_id;
        $gate->block_id = $request->block_id;
        $gate->name = $request->name;
        $gate->status = $request->status;
        $gate->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.information') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $gate = Gate::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$gate){
            return redirect()->route('gate.index');
        }
        return view('admin.gate.show',compact('gate'));
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
        $gate = Gate::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $gate->status = 'Inactive';
            $gate->save();
            $gate->delete();
        }else{
            $gate->status = 'Active';
            $gate->save();
            $gate->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function get_gates(Request $request)
    {
        $block_id = $request->block_id;
        $gate_id = $request->gate_id;
        $gates = Gate::where('block_id',$block_id)->where('status','Active')->get();
        return view('partials.gates',compact('gates','gate_id'));
        
    }

    public function update_gate_status(Request $request)
    {
        if (!\Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }
        // Only allow BA, president, security, or custom.information permission
        $user = \Auth::user();
        if (!($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('security') || $user->hasPermission('custom.information'))) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }
        $gate = Gate::find($request->id);
        if (!$gate) {
            return response()->json(['success' => false, 'message' => 'Gate not found.'], 404);
        }
        $gate->status = $request->status;
        $gate->save();
        return response()->json(['success' => true, 'status' => $gate->status]);
    }
}
