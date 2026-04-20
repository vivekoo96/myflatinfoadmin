<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatrolLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;

class PatrolLocationController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'BA' && !Auth::user()->hasRole('security')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $locations = PatrolLocation::where('building_id', $building->id)
            ->withTrashed()
            ->orderBy('name')
            ->get();

        return view('admin.patrol_locations.index', compact('building', 'locations'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $rules = [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:Active,Inactive',
        ];

        $validation = \Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $building = Auth::user()->building;
        $msg = 'Patrol location added successfully';
        $location = new PatrolLocation();

        if ($request->id) {
            $location = PatrolLocation::withTrashed()->find($request->id);
            $msg = 'Patrol location updated';
        } else {
            // Generate unique QR string only on creation
            $location->qr_string = Str::random(32);
        }

        $location->building_id = $building->id;
        $location->name = $request->name;
        $location->description = $request->description;
        $location->status = $request->status;
        $location->save();

        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        $location = PatrolLocation::where('id', $id)
            ->where('building_id', Auth::user()->building_id)
            ->firstOrFail();

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'qr_string' => $location->qr_string,
        ]);
    }

    public function destroy($id, Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return response()->json(['msg' => 'error', 'detail' => 'Permission denied'], 403);
        }

        $location = PatrolLocation::where('id', $id)
            ->where('building_id', Auth::user()->building_id)
            ->firstOrFail();

        $location->delete();

        return response()->json(['msg' => 'success']);
    }
}
