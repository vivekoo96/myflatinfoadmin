<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timing;
use App\Models\Facility;
use \Auth;
use Carbon\Carbon;

class TimingController extends Controller
{

    public function index()
    {
        $timings = Timing::where('building_id',Auth::User()->building_id)->orderBy('id','asc')->withTrashed()->get();
        return view('admin.timing.index',compact('timings'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            'facility_id' => 'required|exists:facilities,id',
            'timing_id' => 'nullable|exists:timings,id',
            'from_times' => 'required|array|min:1',
            'from_times.*' => 'required|date_format:H:i',
            'to_times' => 'required|array|min:1',
            'to_times.*' => 'required|date_format:H:i',
            'booking_option' => 'required|in:daily,slotwise,both',
            'booking_type' => 'required|in:Free,Paid',
            'price' => 'required_if:booking_type,Paid|nullable|integer|min:0',
            'cancellation_type' => 'nullable|in:Fixed,Percentage,Manual,N/A',
            'cancellation_value' => 'nullable|integer|min:0',
            'status' => 'required|in:Active,Inactive',
            'selected_dates' => 'required|string', // comma-separated
        ];
    
        $validator = \Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }
    
        $user = Auth::user();
        $building = $user->building;
    
        if (!$building) {
            return redirect()->back()->with('error', 'Invalid building.');
        }
    
        $facility = Facility::where('building_id', $building->id)->first();
        if (!$facility) {
            return redirect()->back()->with('error', 'Facility not found.');
        }
    
        $fromTimes = $request->from_times;
        $toTimes = $request->to_times;
        $selectedDates = array_map('trim', explode(',', $request->selected_dates));
        $facilityId = $request->facility_id;
    
        function timeToMinutes($time)
        {
            [$hours, $minutes] = explode(':', $time);
            return ((int)$hours) * 60 + (int)$minutes;
        }
    
        // 1. Check for overlaps within submitted slots
        for ($i = 0; $i < count($fromTimes); $i++) {
            $from_i = $fromTimes[$i];
            $to_i = $toTimes[$i];
    
            $from_i_min = timeToMinutes($from_i);
            $to_i_min = timeToMinutes($to_i);
    
            if ($to_i_min <= $from_i_min) {
                $to_i_min += 1440;
            }
    
            for ($j = $i + 1; $j < count($fromTimes); $j++) {
                $from_j = $fromTimes[$j];
                $to_j = $toTimes[$j];
    
                $from_j_min = timeToMinutes($from_j);
                $to_j_min = timeToMinutes($to_j);
    
                if ($to_j_min <= $from_j_min) {
                    $to_j_min += 1440;
                }
    
                if ($from_i_min < $to_j_min && $to_i_min > $from_j_min) {
                    return redirect()->back()->with('error', "Time slots $from_i - $to_i and $from_j - $to_j overlap.");
                }
            }
        }
    
        // 2. Check for overlaps with existing timings per date
        foreach ($selectedDates as $selectedDate) {
            $existingTimings = Timing::where('facility_id', $facilityId)
                ->whereJsonContains('dates', $selectedDate)
                ->when($request->timing_id, function ($query) use ($request) {
                    $query->where('id', '!=', $request->timing_id);
                })
                ->get();
    
            foreach ($fromTimes as $index => $from) {
                $to = $toTimes[$index];
                $from_min = timeToMinutes($from);
                $to_min = timeToMinutes($to);
                if ($to_min <= $from_min) {
                    $to_min += 1440;
                }
    
                foreach ($existingTimings as $existing) {
                    $existing_from_min = timeToMinutes($existing->from);
                    $existing_to_min = timeToMinutes($existing->to);
                    if ($existing_to_min <= $existing_from_min) {
                        $existing_to_min += 1440;
                    }
    
                    if ($from_min < $existing_to_min && $to_min > $existing_from_min) {
                        return redirect()->back()->with('error', "Time slot $from - $to on $selectedDate overlaps with existing slot $existing->from - $existing->to.");
                    }
                }
            }
        }
    
        // Save timings
        $msg = $request->timing_id ? 'Timing updated successfully' : 'Timing added successfully';
    
        if ($request->timing_id) {
            $timing = Timing::find($request->timing_id);
            if ($timing) {
                $timing->facility_id = $request->facility_id;
                $timing->dates = json_encode($selectedDates); // ✅ save as array
                $timing->from = $fromTimes[0];
                $timing->to = $toTimes[0];
                $timing->booking_option = $request->booking_option;
                $timing->booking_type = $request->booking_type;
                $timing->price = $request->booking_type === 'Paid' ? ($request->price ?? 0) : 0;
                $timing->cancellation_type = $request->cancellation_type;
                $timing->cancellation_value = $request->cancellation_value ?? 0;
                $timing->status = $request->status;
                $timing->save();
            }
    
            // Insert remaining timings if more than 1
            for ($i = 1; $i < count($fromTimes); $i++) {
                $newTiming = new Timing();
                $newTiming->facility_id = $request->facility_id;
                $newTiming->dates = json_encode($selectedDates); // ✅ save as array
                $newTiming->from = $fromTimes[$i];
                $newTiming->to = $toTimes[$i];
                $newTiming->booking_option = $request->booking_option;
                $newTiming->booking_type = $request->booking_type;
                $newTiming->price = $request->booking_type === 'Paid' ? ($request->price ?? 0) : 0;
                $newTiming->cancellation_type = $request->cancellation_type;
                $newTiming->cancellation_value = $request->cancellation_value ?? 0;
                $newTiming->status = $request->status;
                $newTiming->save();
            }
        } else {
            for ($i = 0; $i < count($fromTimes); $i++) {
                $timing = new Timing();
                $timing->facility_id = $request->facility_id;
                $timing->dates = json_encode($selectedDates); // ✅ save as array
                $timing->from = $fromTimes[$i];
                $timing->to = $toTimes[$i];
                $timing->booking_option = $request->booking_option;
                $timing->booking_type = $request->booking_type;
                $timing->price = $request->booking_type === 'Paid' ? ($request->price ?? 0) : 0;
                $timing->cancellation_type = $request->cancellation_type;
                $timing->cancellation_value = $request->cancellation_value ?? 0;
                $timing->status = $request->status;
                $timing->save();
            }
        }
    
        return redirect()->back()->with('success', $msg);
    }


    public function show($id)
    {
        $timing = Timing::where('id',$id)->withTrashed()->first();
        if(!$timing){
            return redirect()->route('timing.index');
        }
        return view('admin.timing.show',compact('timing'));
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
        $timing = Timing::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $timing->delete();
        }else{
            $timing->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
