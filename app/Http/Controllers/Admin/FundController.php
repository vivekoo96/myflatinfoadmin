<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Block;
use App\Models\Building;
use App\Models\Flat;
use App\Models\Payment;
use App\Models\MaintenancePayment;
use App\Models\EssentialPayment;
use App\Models\Expense;
use \Auth;

class FundController extends Controller
{

    public function society_fund()
    {
        $building = Auth::User()->building;
        return view('admin.fund.index',compact('building'));
    }
    
    public function get_expenses()
    {
        $building = Auth::User()->building;
        return view('partials.expenses',compact('building'));
    }
    public function get_maintenance_funds()
    {
        $building = Auth::User()->building;
        return view('admin.fund.maintenance_funds',compact('building'));
    }
    public function get_essential_funds()
    {
        $building = Auth::User()->building;
        return view('admin.fund.essential_funds',compact('building'));
    }
    public function get_event_funds()
    {
        $building = Auth::User()->building;
        return view('admin.fund.event_funds',compact('building'));
    }
    public function get_corpus_funds()
    {
        $building = Auth::User()->building;
        return view('admin.fund.corpus_funds',compact('building'));
    }
    public function get_reciepts()
    {
        $building = Auth::User()->building;
        $maintenance = MaintenancePayment::where('building_id',$building->id)->sum('paid_amount');
        $essential = EssentialPayment::where('building_id',$building->id)->sum('paid_amount');
        $event = Payment::where('building_id',$building->id)->sum('amount');
        $corpus = Flat::where('building_id',$building->id)->sum('corpus_fund');
        $expense = Expense::where('building_id',$building->id)->sum('amount');
        $total_fund = $maintenance + $essential + $event + $corpus;
        $remaining_fund = $total_fund - $expense;
        return view('admin.fund.reciepts',compact('building','maintenance','essential','event','corpus','expense','total_fund','remaining_fund'));
    }

    public function income_and_expenditure()
    {
        $building = Auth::User()->building;
        return view('admin.fund.index',compact('building'));
    }


    
}
