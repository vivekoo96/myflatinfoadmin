<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAmc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetAmcController extends Controller
{
    /**
     * Display list of AMCs
     */
    public function index()
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;

        // Get all active assets for the AMC dropdown
        $assets = Asset::where('building_id', $building->id)
            ->where('status', 'active')
            ->withTrashed()
            ->get();

        // Get all AMCs for this building
        $amcs = AssetAmc::where('building_id', $building->id)
            ->with('asset')
            ->orderBy('end_date')
            ->get();

        // Find AMCs due for renewal
        $dueSoon = $amcs->filter(fn($amc) => $amc->isDueForRenewal());

        return view('admin.asset.amc', compact('building', 'assets', 'amcs', 'dueSoon'));
    }

    /**
     * Create or Update AMC (upsert pattern)
     */
    public function store(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $rules = [
            'asset_id'          => 'required|integer|exists:assets,id',
            'provider_name'     => 'required|string|max:255',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'service_frequency' => 'required|in:Monthly,Quarterly,Half-Yearly,Yearly',
            'notes'             => 'nullable|string',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $building = Auth::user()->building;

        // Verify asset belongs to this building
        $asset = Asset::where('id', $request->asset_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$asset) {
            return redirect()->back()->with('error', 'Asset not found or access denied');
        }

        if ($request->id) {
            $amc = AssetAmc::where('id', $request->id)
                ->where('building_id', $building->id)
                ->first();

            if (!$amc) {
                return redirect()->back()->with('error', 'AMC not found');
            }
        } else {
            $amc = new AssetAmc();
            $amc->building_id = $building->id;
        }

        $amc->asset_id = $request->asset_id;
        $amc->provider_name = $request->provider_name;
        $amc->start_date = $request->start_date;
        $amc->end_date = $request->end_date;
        $amc->service_frequency = $request->service_frequency;
        $amc->notes = $request->notes;
        $amc->save();

        return redirect()->back()->with('success', 'AMC saved successfully');
    }

    /**
     * Delete AMC
     */
    public function destroy(Request $request)
    {
        if (Auth::User()->role == 'BA') {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $amc = AssetAmc::where('id', $request->id)
            ->where('building_id', $building->id)
            ->first();

        if (!$amc) {
            return response()->json(['error' => 'AMC not found'], 404);
        }

        $amc->delete();

        return response()->json(['msg' => 'success'], 200);
    }
}
