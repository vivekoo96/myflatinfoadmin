<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetController extends Controller
{
    /**
     * Display list of assets
     */
    public function index()
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $assets = Asset::where('building_id', $building->id)->withTrashed()->get();

        return view('admin.asset.index', compact('building', 'assets'));
    }

    /**
     * Display inventory stock view
     */
    public function inventory()
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $assets = Asset::where('building_id', $building->id)
            ->where('status', '!=', 'disposed')
            ->withTrashed()
            ->get();

        $lowStockCount = $assets->filter(fn($asset) => $asset->isLowStock())->count();

        return view('admin.asset.inventory', compact('building', 'assets', 'lowStockCount'));
    }

    /**
     * Create or Update asset (upsert pattern)
     */
    public function store(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $rules = [
            'name'                  => 'required|string|max:255',
            'category'              => 'required|string|max:100',
            'total_quantity'        => 'required|integer|min:1',
            'available_qty'         => 'required|integer|min:0',
            'used_qty'              => 'required|integer|min:0',
            'damaged_qty'           => 'required|integer|min:0',
            'low_stock_threshold'   => 'required|integer|min:1',
            'location'              => 'nullable|string|max:255',
            'purchase_date'         => 'nullable|date',
            'vendor_name'           => 'nullable|string|max:255',
            'vendor_contact'        => 'nullable|string|max:20',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $building = Auth::user()->building;

        if ($request->id) {
            $asset = Asset::where('id', $request->id)
                ->where('building_id', $building->id)
                ->withTrashed()
                ->first();

            if (!$asset) {
                return redirect()->back()->with('error', 'Asset not found');
            }
        } else {
            $asset = new Asset();
            $asset->building_id = $building->id;
        }

        $asset->name = $request->name;
        $asset->category = $request->category;
        $asset->total_quantity = $request->total_quantity;
        $asset->available_qty = $request->available_qty;
        $asset->used_qty = $request->used_qty;
        $asset->damaged_qty = $request->damaged_qty;
        $asset->low_stock_threshold = $request->low_stock_threshold;
        $asset->location = $request->location;
        $asset->purchase_date = $request->purchase_date;
        $asset->vendor_name = $request->vendor_name;
        $asset->vendor_contact = $request->vendor_contact;
        $asset->save();

        return redirect()->back()->with('success', 'Asset saved successfully');
    }

    /**
     * Update asset status (active / under_repair / disposed)
     */
    public function updateStatus(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $asset = Asset::where('id', $request->id)
            ->where('building_id', $building->id)
            ->first();

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        $asset->status = $request->status;
        $asset->save();

        return response()->json(['msg' => 'success'], 200);
    }

    /**
     * Update asset stock levels
     */
    public function updateStock(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $rules = [
            'id'            => 'required|integer',
            'add_qty'       => 'nullable|integer|min:0',
            'used_qty'      => 'nullable|integer|min:0',
            'damaged_qty'   => 'nullable|integer|min:0',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $building = Auth::user()->building;
        $asset = Asset::where('id', $request->id)
            ->where('building_id', $building->id)
            ->first();

        if (!$asset) {
            return redirect()->back()->with('error', 'Asset not found');
        }

        $add_qty = $request->add_qty ?? 0;
        $used_qty = $request->used_qty ?? 0;
        $damaged_qty = $request->damaged_qty ?? 0;

        $asset->available_qty = max(0, $asset->available_qty + $add_qty - $used_qty - $damaged_qty);
        $asset->used_qty = $asset->used_qty + $used_qty;
        $asset->damaged_qty = $asset->damaged_qty + $damaged_qty;
        $asset->save();

        return redirect()->back()->with('success', 'Stock updated successfully');
    }

    /**
     * Delete asset (soft delete)
     */
    public function destroy(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $asset = Asset::where('id', $request->id)
            ->where('building_id', $building->id)
            ->first();

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        $asset->delete();

        return response()->json(['msg' => 'success'], 200);
    }
}
