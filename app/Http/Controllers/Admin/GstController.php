<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GstController extends Controller
{
    /**
     * Display GST billing report with filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Permission check
        if (!($user->role == 'BA' || $user->hasRole('accounts'))) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        // Base query for GST transactions
        $query = Transaction::where('building_id', $building->id)
            ->where('gst_amount', '>', 0)
            ->whereIn('model', ['Maintenance', 'Essential', 'Facility'])
            ->with(['user'])
            ->orderBy('date', 'desc');

        // Category filter
        if ($request->filled('category')) {
            $query->where('model', $request->category);
        }

        // Date range filters
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $transactions = $query->paginate(20)->appends($request->query());

        // Calculate summary totals with same filters
        $baseTotalsQuery = Transaction::where('building_id', $building->id)
            ->where('gst_amount', '>', 0)
            ->whereIn('model', ['Maintenance', 'Essential', 'Facility']);

        if ($request->filled('category')) {
            $baseTotalsQuery->where('model', $request->category);
        }
        if ($request->filled('from_date')) {
            $baseTotalsQuery->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $baseTotalsQuery->whereDate('date', '<=', $request->to_date);
        }

        $totalGst = $baseTotalsQuery->sum('gst_amount');
        $totalAmount = $baseTotalsQuery->sum('amount');
        $totalBase = $totalAmount - $totalGst;

        return view('admin.gst.index', compact(
            'transactions', 'totalBase', 'totalGst', 'totalAmount', 'building'
        ));
    }
}
