<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryRestrictionController extends Controller
{
    /**
     * Show delivery entry restriction settings
     */
    public function index()
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return redirect('permission-denied')->with('error', 'Building context not found.');
        }

        return view('admin.delivery-restriction.index', compact('building'));
    }

    /**
     * Update delivery entry restriction settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'delivery_entry_start_time' => 'required|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'delivery_entry_end_time' => 'required|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
        ]);

        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return redirect('permission-denied')->with('error', 'Building context not found.');
        }

        $building->delivery_entry_start_time = $request->delivery_entry_start_time;
        $building->delivery_entry_end_time = $request->delivery_entry_end_time;
        $building->save();

        return redirect()->back()->with('success', 'Delivery entry restrictions updated successfully');
    }
}
