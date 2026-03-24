<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Block;
use App\Models\Building;
use App\Models\Flat;
use App\Models\Transaction;
use App\Models\FlatParking;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Validation\Rule;
use \Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Mail;
use App\Mail\FlatRegistered;

class FlatController extends Controller
{

    public function index()
    {
       
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Accounts"|| Auth::User()->hasPermission('custom.information'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        $blocks = Block::where('building_id', Auth::User()->building_id)->get();
        return view('admin.flat.index',compact('building', 'blocks'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'block_id' => 'required|exists:blocks,id',
            'name' => [
            'required',
                Rule::unique('flats')
                    ->where(function ($query) use ($request) {
                        return $query->where('block_id', $request->block_id);
                    })
                    ->ignore($request->id), // ignore current flat in case of edit
            ],
            'area' => 'required|min:0',
            'corpus_fund' => 'required|min:0',
            'owner_id' => 'nullable|exists:users,id',
            'tanent_id' => 'nullable|exists:users,id',
            'status' => 'required|in:Inactive,Active',
            'sold_out' => 'required|in:Yes,No',
            'living_status' => 'required|in:Vacant,Owner,Tanent',
        ];
    
        $msg = 'Flat Added';
        $flat = new Flat();
    
        if ($request->id) {
            $flat = Flat::withTrashed()->find($request->id);
            $msg = 'Flat Updated';
        }
    
        // Custom validation to ensure owner and tenant are not the same
        $validation = \Validator::make($request->all(), $rules);
        $validation->after(function ($validator) use ($request) {
            if (
                !empty($request->owner_id) &&
                !empty($request->tanent_id) &&
                $request->owner_id == $request->tanent_id
            ) {
                $validator->errors()->add('tanent_id', 'Owner and Tenant cannot be the same person.');
            }
        });
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first())->withInput();
        }
        // Only check limit for new flats, not updates
        if (!$request->id) {
            $created_counts = \App\Models\Flat::where('building_id', Auth::user()->building_id)->whereNull('deleted_at')->count();
            $flat_limit = Auth::user()->building->no_of_flats;
            if ($created_counts >= $flat_limit) {
                return redirect()->back()->with('error', 'No of flat limit is exceeded');
            }
        }
        $flat->building_id = Auth::User()->building_id;
        $flat->block_id = $request->block_id;
        $flat->name = $request->name;
        $flat->area = $request->area;
        $flat->corpus_fund = $request->corpus_fund;
        $flat->status = $request->status;
        $flat->sold_out = $request->sold_out;
        $flat->living_status = $request->living_status;
        if($request->sold_out == 'No'){
            $flat->owner_id = Null;
            $flat->tanent_id = Null;
        }
        if($request->sold_out == 'Yes' && $request->living_status == 'Owner'){
            $flat->owner_id = $request->owner_id;
            $flat->tanent_id = Null;
        }
        if($request->sold_out == 'Yes' && $request->living_status == 'Tanent'){
            $flat->owner_id = $request->owner_id;
            $flat->tanent_id = $request->tanent_id;
        }
        if($request->sold_out == 'Yes' && $request->living_status == 'Vacant'){
            $flat->owner_id = $request->owner_id;
            $flat->tanent_id = Null;
        }
        $flat->save();
        
        // Send email notifications to owner and tenant
        if ($flat->owner_id) {
            $owner = \App\Models\User::find($flat->owner_id);
            if ($owner && $owner->email) {
                try {
                    \Mail::to($owner->email)->send(new FlatRegistered($flat, $owner));
                } catch (\Exception $e) {
                    \Log::error('Failed to send flat registration email to owner: ' . $e->getMessage());
                }
            }
        }
        
        if ($flat->tanent_id) {
            $tenant = \App\Models\User::find($flat->tanent_id);
            if ($tenant && $tenant->email) {
                try {
                    \Mail::to($tenant->email)->send(new FlatRegistered($flat, $tenant));
                } catch (\Exception $e) {
                    \Log::error('Failed to send flat registration email to tenant: ' . $e->getMessage());
                }
            }
        }
        
        // Update user's flat_id for backward compatibility
      if ($flat->owner_id) {
            $owner = \App\Models\User::find($flat->owner_id);
            if ($owner) {
                $owner->flat_id = $flat->id;
                $owner->save();
                // Ensure building_users has an entry with building's default user role
                try {
                    $building = Building::find($flat->building_id);
                    $defaultUserRole = $this->getDefaultUserRole($building->id);  
                    $exists = BuildingUser::where('building_id', $flat->building_id)
                        ->where('user_id', $owner->id)
                        ->where('role_id', $defaultUserRole->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if (! $exists) {
                        $buildingUser = new BuildingUser();
                        $buildingUser->building_id = $flat->building_id;
                        $buildingUser->user_id = $owner->id;
                        $buildingUser->role_id = $defaultUserRole->id;
                        $buildingUser->status = 'Active';
                        $buildingUser->save();
                    }
                } catch (\Exception $e) {
                    // non-fatal: log and continue
                    try { \Log::error('Failed to ensure BuildingUser for owner', ['owner_id' => $owner->id, 'building_id' => $flat->building_id, 'error' => $e->getMessage()]); } catch (\Exception $ee) {}
                }
            }
        }
        
        if ($flat->tanent_id) {
            $tenant = \App\Models\User::find($flat->tanent_id);
            if ($tenant) {
                $tenant->flat_id = $flat->id;
                $tenant->save();
                // Ensure building_users has an entry with building's default user role
                try {
                    $building = Building::find($flat->building_id);
                   $defaultUserRole = $this->getDefaultUserRole($building->id);  
                    $roleId = $userRole ? $userRole->id : 55; // fallback to 55 if not found
                    $exists = BuildingUser::where('building_id', $flat->building_id)
                        ->where('user_id', $tenant->id)
                        ->where('role_id', $defaultUserRole->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if (! $exists) {
                        $buildingUser = new BuildingUser();
                        $buildingUser->building_id = $flat->building_id;
                        $buildingUser->user_id = $tenant->id;
                        $buildingUser->role_id =$defaultUserRole->id;
                        $buildingUser->status = 'Active';
                        $buildingUser->save();
                    }
                } catch (\Exception $e) {
                    // non-fatal: log and continue
                    try { \Log::error('Failed to ensure BuildingUser for tenant', ['tenant_id' => $tenant->id, 'building_id' => $flat->building_id, 'error' => $e->getMessage()]); } catch (\Exception $ee) {}
                }
            }
        }
    
    
        return redirect()->back()->with('success', $msg);
    }
    
    
      private function getDefaultUserRole($buildingId)
    {
        $defaultUserRole = \App\Models\Role::where('building_id', $buildingId)
            ->where('slug', 'user')
            ->first();
        
        if (!$defaultUserRole) {
            // Create default user role if it doesn't exist
            $defaultUserRole = new \App\Models\Role();
            $defaultUserRole->building_id = $buildingId;
            $defaultUserRole->name = 'User';
            $defaultUserRole->slug = 'user';
            $defaultUserRole->type = 'user';
            $defaultUserRole->save();
        }
        
        return $defaultUserRole;
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.information') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $flat = Flat::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$flat){
            return redirect()->route('flat.index');
        }
        
        // Load relationships for eager loading
        $flat->load(['owner', 'tanent', 'block', 'family_members', 'parcels', 'maintenance_payments.maintenance', 'essential_payments.essential']);
        
        // Get all bookings for this flat (owner and tenant)
        $bookings = collect();
        
        // Get owner's bookings if owner exists
        if ($flat->owner) {
            $ownerBookings = $flat->owner->bookings()->with(['facility', 'timing', 'user'])->get();
            $bookings = $bookings->merge($ownerBookings);
        }
        
        // Get tenant's bookings if tenant exists
        if ($flat->tanent) {
            $tenantBookings = $flat->tanent->bookings()->with(['facility', 'timing', 'user'])->get();
            $bookings = $bookings->merge($tenantBookings);
        }
        
        // Sort all bookings by date descending
        $bookings = $bookings->sortByDesc('date');
            
        return view('admin.flat.show',compact('flat','bookings'));
    }
    
    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }



    public function getFlatsByBuilding(Request $request)
    {
        $buildingId = session('current_building_id') ?? Auth::user()->building_id;
        // dd($buildingId);
        // dd
        // Check if email is provided in request
        // dd($request->all());
        if ($request->has('email') && !empty($request->email)) {
            $email = $request->email;
            // dd($email);
            // Find user by email
            $user = User::where('email', $email)->first();
            // dd($user);
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }
            
            // Check if user is in building users
           
            // Get flats assigned to this user as owner or tenant
            $flats = Flat::where('building_id', $buildingId)
                         ->where('status', 'Active')
                         ->where(function($query) use ($user) {
                             $query->where('owner_id', $user->id)
                                   ->orWhere('tanent_id', $user->id);
                         })
                         ->get();

            return response()->json(['success' => true, 'flats' => $flats, 'user' => $user->name]);
        }
        
        //  $flats = Flat::where('building_id', $buildingId)
        //              ->where('status', 'Active')
        //              ->select('id', 'name')
        //              ->orderBy('name')
        //              ->get();

        // return response()->json(['success' => true, 'flats' => $flats]);
    }
    public function destroy($id, Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $flat = Flat::where('id',$request->id)->withTrashed()->first();
        if($request->action == 'delete'){
            $flat->status = 'Inactive';
            $flat->save();
            $flat->delete();
        }else{
            $flat->status = 'Active';
            $flat->save();
            $flat->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_flat_status(Request $request)
    {
        if(Auth::User()->role == 'BA')
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $flat = Flat::where('id',$request->id)->withTrashed()->first();
        if($flat->status == 'Active'){
            $flat->status = 'Inactive';
        }else{
            $flat->status = 'Active';
        }
        $flat->save();
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function getFlatsByBlock(Request $request)
    {
        if (!$request->block_id) {
            return response()->json(['error' => 'Block ID is required'], 400);
        }

        $flats = Flat::where('block_id', $request->block_id)
                     ->where('status', 'Active')
                     ->select('id', 'name')
                     ->orderBy('name')
                     ->get();

        return response()->json(['flats' => $flats]);
    }

    public function getFlats($blockId)
    {
        $flats = Flat::where('block_id', $blockId)->get();
        return response()->json(['success' => true, 'flats' => $flats]);
    }

    public function get_flat_data(Request $request)
    {
        $flat_id = $request->flat_id;
        $flats = Flat::where('block_id', $request->block_id)->get();
        return view('partials.flats',compact('flats','flat_id'));

    }

    public function update_corpus_fund(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.corpusfund'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'id' => 'required|exists:flats,id',
            'corpus_fund' => 'required|min:0',
            'is_corpus_paid' => 'required',
            'corpus_paid_on' => 'required',
            'payment_type' => 'required',
        ];
    
    $msg = 'Corpus fund Updated';
    // Check if status is being toggled between Paid and Unpaid
    $old_flat = Flat::withTrashed()->find($request->id);
    // dd($old_flat);
    $old_status = $old_flat ? $old_flat->is_corpus_paid : null;

        $flat = Flat::withTrashed()->find($request->id);
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        // dd($request->all());

        $flat->corpus_fund = $request->corpus_fund;
        $flat->is_corpus_paid = $request->is_corpus_paid;
        $flat->corpus_paid_on = $request->corpus_paid_on;
        $flat->corpus_payment_type = $request->payment_type;

        // Show error if trying to change from Paid to Unpaid
        if ($old_status === 'Yes' && $request->is_corpus_paid === 'No') {
            return redirect()->back()->with('error', 'Changing Corpus Fund status from Paid to Unpaid is not allowed.');
        }
        // dd($flat);
            // Set bill number from frontend or clear it
            if($request->is_corpus_paid == 'No') {
                $flat->corpus_paid_on = NULL;
                $flat->bill_no = NULL;
            } else {
                $flat->bill_no = $request->bill_no;
            }
        $flat->save();
        
        $transaction = Transaction::where('model','CorpusFund')->where('model_id',$flat->id)->first();
        if(!$transaction){
            $transaction = new Transaction();
        }
            $transaction->building_id = $flat->building_id;
            $transaction->user_id = $flat->owner_id;
            // $transaction->order_id = $order->order_id;
            $transaction->model = 'Corpus';
            $transaction->type = 'Credit';
            $transaction->payerrole_id = Auth::User()->id;
            $transaction->flat_id = $flat->id;
             $transaction->block_id = $flat->block->id;
            $transaction->payment_type = $request->payment_type;
            $transaction->date = $request->corpus_paid_on;
            $transaction->amount = $request->corpus_fund;
            $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
            $transaction->desc = 'Corpus Fund for '.$flat->name;
            $transaction->status = 'Success';
            $transaction->save();
        
        return redirect()->back()->with('success', $msg);
    }

    public function get_flat(Request $request) 
    {
        $flat = Flat::where('name', $request->flat)->where('building_id',Auth::User()->building_id)->where('status','Active')->first();
        if($flat) {
            return response()->json(['success' => true, 'data' => ['id' => $flat->id,'name' => $flat->name]]);
        }
        return response()->json(['success' => false, 'message' => 'Flat not found']);
    }

    public function store_parking_flat(Request $request) {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'flat_id' => 'required|exists:flats,id',
            'flat' => 'required|exists:flats,name',
            'parking_id' => 'required|exists:parkings,id',
            'id' => 'nullable|exists:flat_parkings,id',
        ];
    
        $msg = 'Parking added successfully';
        
    
        if ($request->id) {
            $flat_parking = FlatParking::find($request->id);
            $msg = 'Parking flat Updated';
        }else{
            $msg = 'Parking flat Updated';
            $flat_parking = FlatParking::where('parking_id',$request->parking_id)->where('flat_id',$request->flat_id)->first();
            if(!$flat_parking){
                $flat_parking = new FlatParking();
                $msg = 'Parking flat added successfully';
            }
        }
        
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $flat_parking->flat_id = $request->flat_id;
        $flat_parking->parking_id = $request->parking_id;
        $flat_parking->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function delete_parking_flat(Request $request) {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'id' => 'nullable|exists:flat_parkings,id',
        ];
        $msg = 'flat parking delete successfully';    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $flat_parking = FlatParking::find($request->id);
        $flat_parking->delete();
        return response()->json([
            'msg' => $msg
        ],200);
    }

    public function downloadSampleFlats()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=flats_upload_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Columns for CSV (with correct spelling for users)
        $columns = [
            'block_name', 
            'flat_name', 
            'area', 
            'corpus_fund', 
            'status', 
            'sold_out', 
            'living_status', 
            'owner_first_name',
            'owner_last_name',
            'owner_email',
            'owner_phone',
            'owner_password',
            'tenant_first_name',
            'tenant_last_name',
            'tenant_email',
            'tenant_phone',
            'tenant_password',
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Example rows with different scenarios
            
            // Add a comment row explaining block names
            // fputcsv($file, [
            //     '# IMPORTANT: Replace "Block A" and "Block B" with your actual block names from your building',
            //     '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''
            // ]);
            
            // Row 1: Flat with Owner only
            fputcsv($file, [
                'Block A',              // block_name - CHANGE THIS to your actual block name
                'A-101',               // flat_name
                '1200',                // area
                '50000',               // corpus_fund
                'Active',              // status
                'Yes',                 // sold_out
                'Owner',               // living_status
                'John',                // owner_first_name
                'Doe',                 // owner_last_name
                'john.doe@example.com', // owner_email
                '9876543210',          // owner_phone
                'Password@123',        // owner_password
                '',                    // tenant_first_name (empty for owner-only)
                '',                    // tenant_last_name (empty)
                '',                    // tenant_email (empty)
                '',                    // tenant_phone (empty)
                '',                    // tenant_password (empty)
            ]);
            
            // Row 2: Flat with Owner and Tenant
            fputcsv($file, [
                'Block A',              // block_name - CHANGE THIS to your actual block name
                'A-102',               // flat_name
                '1000',                // area
                '45000',               // corpus_fund
                'Active',              // status
                'Yes',                 // sold_out
                'Tenant',              // living_status
                'Mary',                // owner_first_name
                'Johnson',             // owner_last_name
                'mary.johnson@example.com', // owner_email
                '9876543211',          // owner_phone
                'Password@456',        // owner_password
                'David',               // tenant_first_name
                'Smith',               // tenant_last_name
                'david.smith@example.com', // tenant_email
                '9876501234',          // tenant_phone
                'Password@789',        // tenant_password
            ]);
            
            // Row 3: Vacant flat (no users)
            fputcsv($file, [
                'Block B',              // block_name - CHANGE THIS to your actual block name
                'B-101',               // flat_name
                '1500',                // area
                '60000',               // corpus_fund
                'Active',              // status
                'No',                  // sold_out
                'Vacant',              // living_status
                '',                    // owner_first_name (empty for vacant)
                '',                    // owner_last_name (empty)
                '',                    // owner_email (empty)
                '',                    // owner_phone (empty)
                '',                    // owner_password (empty)
                '',                    // tanent_first_name (empty)
                '',                    // tanent_last_name (empty)
                '',                    // tanent_email (empty)
                '',                    // tanent_phone (empty)
                '',                    // tanent_password (empty)
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
      public function bulkUploadFlats(Request $request)
    {
        if (Auth::user()->role != 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
    
        $request->validate([
            'bulk_file' => 'required|mimes:csv,txt,xls,xlsx|max:2048',
        ]);
    
        $file = $request->file('bulk_file'); // UploadedFile object
        $data = Excel::toArray([], $file);
    
        if (empty($data) || !isset($data[0])) {
            return redirect()->back()->with('error', 'Uploaded file is empty or invalid.');
        }
    
        $data = $data[0]; // first sheet
        $header = array_map('trim', $data[0]);
        
        // Debug: Log detailed header information
        Log::info('Raw header from CSV:', $data[0]);
        Log::info('Trimmed header:', $header);
        Log::info('Header count: ' . count($header));
        foreach ($header as $index => $headerField) {
            Log::info("Header[$index]: '$headerField' (length: " . strlen($headerField) . ", bytes: " . bin2hex($headerField) . ")");
        }
        
        unset($data[0]);
    
        $rows = [];
        foreach ($data as $index => $row) {
            if (empty(array_filter($row))) {
                continue; // skip empty rows
            }
            
            Log::info('Raw CSV row ' . ($index + 2) . ':', $row);
            
            $row = array_pad($row, count($header), null); // align row length with header
            Log::info('Padded row ' . ($index + 2) . ' (count: ' . count($row) . '):', $row);
            
            // Trim all cell values and fix phone numbers
            $rowBeforePhone = $row;
            $row = array_map(function($v) {
                // If value is numeric and longer than 8 digits, treat as string (for phone numbers)
                if (is_numeric($v) && strlen((string)(int)$v) >= 8) {
                    // Remove scientific notation if present
                    $v = number_format($v, 0, '', '');
                }
                return is_string($v) ? trim($v) : $v;
            }, $row);
            Log::info('Row after trim processing ' . ($index + 2) . ' (count: ' . count($row) . '):', $row);
            
            // Force phone columns to string and strip formatting
            $phoneFields = ['owner_phone', 'tanent_phone', 'tenant_phone'];
            foreach ($phoneFields as $phoneField) {
                $phoneIndex = array_search($phoneField, $header);
                if ($phoneIndex !== false && isset($row[$phoneIndex])) {
                    $val = $row[$phoneIndex];
                    if (!empty($val)) {
                        // Remove all non-digits and cast to string
                        $row[$phoneIndex] = preg_replace('/\D+/', '', (string)$val);
                    }
                }
            }
            Log::info('Row after phone processing ' . ($index + 2) . ' (count: ' . count($row) . '):', $row);
            
            $rowData = array_combine($header, $row);
            
            // Debug: Check if array_combine worked correctly
            if ($rowData === false) {
                Log::error('array_combine failed for row ' . ($index + 2));
                Log::error('Header count: ' . count($header) . ', Row count: ' . count($row));
                Log::error('Header:', $header);
                Log::error('Row:', $row);
                throw new \Exception("Row " . ($index + 2) . ": Failed to combine header and row data. Header count: " . count($header) . ", Row count: " . count($row));
            }
            
            Log::info('Combined row data ' . ($index + 2) . ':', $rowData);
            
            // Map correct spelling (tenant_*) to database spelling (tanent_*)
            $mappingFields = [
                'tenant_first_name' => 'tanent_first_name',
                'tenant_last_name' => 'tanent_last_name',
                'tenant_email' => 'tanent_email',
                'tenant_phone' => 'tanent_phone',
                'tenant_password' => 'tanent_password',
            ];
            // Accept both 'Tenant' and 'Tanent' for living_status
            if (isset($rowData['living_status']) && strtolower($rowData['living_status']) === 'tenant') {
                $rowData['living_status'] = 'Tanent';
            }
            foreach ($mappingFields as $correctKey => $dbKey) {
                if (isset($rowData[$correctKey])) {
                    $rowData[$dbKey] = $rowData[$correctKey];
                    unset($rowData[$correctKey]); // Remove the correct spelling key
                }
            }
            
            $rows[] = $rowData;
        }
    
        // Flat limit check
        $created_flats = \App\Models\Flat::where('building_id', Auth::user()->building_id)->count();
        $flat_limit = Auth::user()->building->no_of_flats;
        $uploading_flats = count($rows);
    
        if (($created_flats + $uploading_flats) > $flat_limit) {
            return redirect()->back()->with('error', 'No of flat limit is exceeded');
        }
    
        // User limit check (no_of_logins) - count through BuildingUser relationships
        // Count users with 'user' role (direct users)
        $userRole = Role::where('building_id', Auth::user()->building_id)
            ->where('slug', 'user')
            ->first();
        
        $created_users = 0;
        if ($userRole) {
            $created_users = BuildingUser::where('building_id', Auth::user()->building_id)
                ->where('role_id', $userRole->id)
                ->count();
        }
        $user_limit = Auth::user()->building->no_of_logins;
    
        // Count how many new users will be created
        $new_user_emails = [];
        foreach ($rows as $row) {
            if (!empty($row['owner_email'])) {
                $new_user_emails[] = strtolower($row['owner_email']);
            }
            if (!empty($row['tanent_email'])) {
                $new_user_emails[] = strtolower($row['tanent_email']);
            }
        }
        // Unique emails not already existing in DB
        $new_user_emails = array_unique($new_user_emails);
        $existing_user_emails = User::whereIn('email', $new_user_emails)->pluck('email')->map(fn($e) => strtolower($e))->toArray();
        $to_create_users = array_diff($new_user_emails, $existing_user_emails);
    
        if (($created_users + count($to_create_users)) > $user_limit) {
            return redirect()->back()->with('error', 'No of user limit (logins) is exceeded');
        }
        $mails = [];
        $results = [
            'flats_created' => 0,
            'users_created' => 0,
            'errors' => []
        ];

        // Get or create default user role once for the entire bulk upload
        $defaultUserRole = Role::where('building_id', Auth::user()->building_id)
            ->where('slug', 'user')
            ->first();
        
        if (!$defaultUserRole) {
            // Create default user role if it doesn't exist
            $defaultUserRole = new Role();
            $defaultUserRole->building_id = Auth::user()->building_id;
            $defaultUserRole->name = 'User';
            $defaultUserRole->slug = 'user';
            $defaultUserRole->type = 'user';
            $defaultUserRole->save();
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $rowData) {
                // Debug: Log the entire row data to see what we're working with
                Log::info('Processing row ' . ($index + 2) . ':', $rowData);
                
                $blockName = $rowData['block_name'] ?? '';
                Log::info('Block name before processing: "' . $blockName . '"');
                
                $blockName = mb_convert_encoding($blockName, 'UTF-8', 'UTF-8');
                $blockName = preg_replace('/[\x00-\x1F\x7F\xA0\x{200B}-\x{200D}\x{FEFF}]/u', '', $blockName);
                $blockName = preg_replace('/\s+/u', ' ', $blockName);
                $blockName = trim($blockName);
                
                Log::info('Block name after processing: "' . $blockName . '"');
                
                if ($blockName === '') {
                    // Check if block_name key exists in the header
                    if (!array_key_exists('block_name', $rowData)) {
                        throw new \Exception("Row " . ($index + 2) . ": 'block_name' column not found in CSV. Available columns: " . implode(', ', array_keys($rowData)));
                    }
                    
                    // Show the raw value from the CSV for debugging
                    $rawValue = $rowData['block_name'] ?? 'NULL';
                    throw new \Exception("Row " . ($index + 2) . ": Block name is required. Raw value: '" . $rawValue . "' (length: " . strlen($rawValue) . ")");
                }
                
                // Try to find block by name (case-insensitive, flexible matching)
                $block = Block::where('building_id', Auth::user()->building_id)
                    ->where(function($query) use ($blockName) {
                        $query->whereRaw("LOWER(TRIM(name)) = ?", [strtolower($blockName)])
                              ->orWhereRaw("LOWER(TRIM(name)) LIKE ?", ['%' . strtolower($blockName) . '%']);
                    })
                    ->first();

                if (!$block) {
                    // Get available blocks for better error message
                    $availableBlocks = Block::where('building_id', Auth::user()->building_id)
                        ->pluck('name')
                        ->toArray();
                    
                    $errorMsg = "Row " . ($index + 2) . ": Block '{$blockName}' not found. ";
                    $errorMsg .= "Available blocks: " . implode(', ', $availableBlocks);
                    
                    throw new \Exception($errorMsg);
                }
    
                // Validation rules
                $rules = [
                    'flat_name'       => [
                        'required',
                        Rule::unique('flats', 'name')->where(fn($q) => $q->where('block_id', $block->id)),
                    ],
                    'area'            => 'required|numeric|min:0',
                    'corpus_fund'     => 'required|numeric|min:0',
                    'status'          => 'required|in:Inactive,Active',
                    'sold_out'        => 'required|in:Yes,No',
                    'living_status'   => 'required|in:Vacant,Owner,Tanent',
                ];
    
                $validator = Validator::make($rowData, $rules);
                $validator->after(function ($v) use ($rowData, $index) {

                    // Rule 1: If sold_out == Yes → owner_email is required, living_status must be Owner, Tanent, or Vacant
                    if ($rowData['sold_out'] === 'Yes') {
                        if (empty($rowData['owner_email'])) {
                            $v->errors()->add('owner_email', "Row " . ($index + 2) . ": Owner email is required when sold_out = Yes.");
                        }
                        if (!in_array($rowData['living_status'], ['Owner', 'Tanent', 'Vacant'])) {
                            $v->errors()->add('living_status', "Row " . ($index + 2) . ": Living status must be Owner, Tanent, or Vacant when sold_out = Yes.");
                        }
                    }
            
                    // Rule 2: If sold_out == No → living_status must be Vacant
                    if ($rowData['sold_out'] === 'No') {
                        if ($rowData['living_status'] !== 'Vacant') {
                            $v->errors()->add('living_status', "Row " . ($index + 2) . ": Living status must be Vacant when sold_out = No.");
                        }
                    }
            
                    // Rule 3: If living_status == Tanent → both owner_email and tanent_email/phone are required
                    if ($rowData['living_status'] === 'Tanent') {
                        if (empty($rowData['owner_email'])) {
                            $v->errors()->add('owner_email', "Row " . ($index + 2) . ": Owner email is required when living_status = Tanent.");
                        }
                        if (empty($rowData['tanent_email']) && empty($rowData['tanent_phone'])) {
                            $v->errors()->add('tanent_email', "Row " . ($index + 2) . ": Either Tenant email or phone is required when living_status = Tanent.");
                        }
                    }
            
                    // Extra rule: Owner and Tenant cannot be the same
                    if (!empty($rowData['owner_email']) && !empty($rowData['tanent_email'])
                        && $rowData['owner_email'] == $rowData['tanent_email']) {
                        $v->errors()->add('tanent_email', "Row " . ($index + 2) . ": Owner and Tenant cannot be the same person.");
                    }
                });
    
                if ($validator->fails()) {
                    throw new \Exception("Row " . ($index + 2) . " failed validation: " . $validator->errors()->first());
                }
    
                // Create Flat
                $flat = new Flat();
                $flat->building_id   = Auth::user()->building_id;
                $flat->block_id      = $block->id;
                $flat->name          = $rowData['flat_name'];
                $flat->area          = $rowData['area'];
                $flat->corpus_fund   = $rowData['corpus_fund'];
                $flat->status        = $rowData['status'];
                $flat->sold_out      = $rowData['sold_out'];
                $flat->living_status = $rowData['living_status'];
    
                $ownerId  = null;
                $tenantId = null;
    
                if ($rowData['sold_out'] == 'Yes') {
                    // Owner
                    if (!empty($rowData['owner_email'])) {
                        $owner = User::where('email', $rowData['owner_email'])->first();
                        if (!$owner) {
                            $ownerRules = $this->userValidationRules();
                            $ownerData = [
                                'first_name' => $rowData['owner_first_name'] ?? null,
                                'last_name'  => $rowData['owner_last_name'] ?? null,
                                'email'      => $rowData['owner_email'] ?? null,
                                'phone'      => $rowData['owner_phone'] ?? null,
                                'password'   => $rowData['owner_password'] ?? null,
                            ];
                        
                            $ownerValidator = Validator::make($ownerData, $ownerRules);
                            if ($ownerValidator->fails()) {
                                throw new \Exception("Row " . ($index + 2) . " (Owner) failed validation: " . $ownerValidator->errors()->first());
                            }
                            $owner = new User();
                            $owner->first_name   = $rowData['owner_first_name'];
                            $owner->last_name    = $rowData['owner_last_name'];
                            $owner->email        = $rowData['owner_email'];
                            $owner->phone        = $rowData['owner_phone'];
                            $owner->password     = Hash::make($rowData['owner_password']);
                            $owner->role         = 'user';
                            $owner->created_type = 'direct';
                            $owner->created_by   = Auth::user()->building_id;
                            $owner->status       = 'Active';
                            $owner->save();
                            $results['users_created']++;
                            
                            $mails[] = [
                                'email' => $owner->email,
                                'name' => $owner->first_name . ' ' . $owner->last_name,
                                'password' => $rowData['owner_password'],
                            ];
                        }
                        
                        // Create or update BuildingUser relationship for owner
                        $buildingUserExists = BuildingUser::where('building_id', Auth::user()->building_id)
                            ->where('user_id', $owner->id)
                            ->whereNull('deleted_at')
                            ->exists();
                        
                        if (!$buildingUserExists) {
                            $buildingUser = new BuildingUser();
                            $buildingUser->building_id = Auth::user()->building_id;
                            $buildingUser->user_id = $owner->id;
                            $buildingUser->role_id = $defaultUserRole->id;
                            $buildingUser->save();
                        }
                        
                        $ownerId = $owner->id;
                    }
    
                    // Tenant
                    if ($rowData['living_status'] == 'Tanent' && !empty($rowData['tanent_email'])) {
                        $tenant = User::where('email', $rowData['tanent_email'])->first();
                        if (!$tenant) {
                            $tenantRules = $this->userValidationRules();
                            $tenantData = [
                                'first_name' => $rowData['tanent_first_name'] ?? null,
                                'last_name'  => $rowData['tanent_last_name'] ?? null,
                                'email'      => $rowData['tanent_email'] ?? null,
                                'phone'      => $rowData['tanent_phone'] ?? null,
                                'password'   => $rowData['tanent_password'] ?? null,
                            ];
                        
                            $tenantValidator = Validator::make($tenantData, $tenantRules);
                            if ($tenantValidator->fails()) {
                                throw new \Exception("Row " . ($index + 2) . " (Tenant) failed validation: " . $tenantValidator->errors()->first());
                            }
                            $tenant = new User();
                            $tenant->first_name   = $rowData['tanent_first_name'];
                            $tenant->last_name    = $rowData['tanent_last_name'];
                            $tenant->email        = $rowData['tanent_email'];
                            $tenant->phone        = $rowData['tanent_phone'];
                            $tenant->password     = Hash::make($rowData['tanent_password']);
                            $tenant->role         = 'user';
                            $tenant->created_type = 'direct';
                            $tenant->created_by   = Auth::user()->building_id;
                            $tenant->status       = 'Active';
                            $tenant->save();
                            $results['users_created']++;
                            
                            $mails[] = [
                                'email' => $tenant->email,
                                'name' => $tenant->first_name . ' ' . $tenant->last_name,
                                'password' => $rowData['tanent_password'],
                            ];
                        }
                        
                        // Create or update BuildingUser relationship for tenant
                        $buildingUserExists = BuildingUser::where('building_id', Auth::user()->building_id)
                            ->where('user_id', $tenant->id)
                            ->whereNull('deleted_at')
                            ->exists();
                        
                        if (!$buildingUserExists) {
                            $buildingUser = new BuildingUser();
                            $buildingUser->building_id = Auth::user()->building_id;
                            $buildingUser->user_id = $tenant->id;
                            $buildingUser->role_id = $defaultUserRole->id;
                            $buildingUser->save();
                        }
                        
                        $tenantId = $tenant->id;
                    }
                }
    
                // Set owner and tenant IDs based on living status
                if ($rowData['sold_out'] == 'No') {
                    // Vacant flat - no owner or tenant
                    $ownerId  = null;
                    $tenantId = null;
                } elseif ($rowData['living_status'] == 'Owner') {
                    // Owner living in the flat - owner assigned, no tenant
                    $tenantId = null;
                    // $ownerId already set above when creating/finding owner
                } elseif ($rowData['living_status'] == 'Tanent') {
                    // Tenant living in the flat - both owner and tenant assigned
                    // Both $ownerId and $tenantId already set above
                } elseif ($rowData['living_status'] == 'Vacant') {
                    // Sold but vacant - owner assigned but no tenant
                    $tenantId = null;
                    // $ownerId already set above when creating/finding owner
                }
    
                $flat->owner_id  = $ownerId;
                $flat->tanent_id = $tenantId;
                $flat->save();
                
                $results['flats_created']++;
            }
    
            DB::commit();
            
            // Get building logo for emails
            $logo = Auth::user()->building->logo ?? null;
            
            foreach ($mails as $m) {
                try {
                    // Create a user object for the email template
                    $userObj = (object) [
                        'name' => $m['name'],
                        'email' => $m['email']
                    ];
                    
                    Mail::send('email.password', ['user' => $userObj, 'password' => $m['password'], 'logo' => $logo], function ($message) use ($m) {
                        $message->to($m['email'], $m['name'])->subject('Your Account Credentials');
                    });
                } catch (\Exception $e) {
                    Log::error("Failed to send mail to {$m['email']}: ".$e->getMessage());
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk flat upload error', [
                'user_id' => Auth::id(),
                'building_id' => Auth::user()->building_id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    
        $message = "Bulk upload completed successfully! ";
        $message .= "Created {$results['flats_created']} flats";
        if ($results['users_created'] > 0) {
            $message .= " and {$results['users_created']} users";
        }
        $message .= ".";
        
        return redirect()->back()->with('success', $message);
    }
    
    // public function bulkUploadFlats(Request $request)
    // {
    //     if (Auth::User()->role != 'BA') {
    //         return redirect('permission-denied')->with('error', 'Permission denied!');
    //     }
    
    //     $request->validate([
    //         'bulk_file' => 'required|mimes:csv,txt,xls,xlsx|max:2048',
    //     ]);
    
    //     $file = $request->file('bulk_file'); // UploadedFile object
    //     $data = Excel::toArray([], $file);
    
    //     if (empty($data) || !isset($data[0])) {
    //         return redirect()->back()->with('error', 'Uploaded file is empty or invalid.');
    //     }
    
    //     $data = $data[0]; // first sheet
    //     $header = array_map('trim', $data[0]);
    //     unset($data[0]);
    
    //     $rows = [];
    //     foreach ($data as $index => $row) {
    //         if (empty(array_filter($row))) {
    //             continue; // skip empty rows
    //         }
    //         $row = array_pad($row, count($header), null); // align row length with header
    //         // Trim all cell values and fix phone numbers
    //         $row = array_map(function($v) {
    //             // If value is numeric and longer than 8 digits, treat as string (for phone numbers)
    //             if (is_numeric($v) && strlen((string)(int)$v) >= 8) {
    //                 // Remove scientific notation if present
    //                 $v = number_format($v, 0, '', '');
    //             }
    //             return is_string($v) ? trim($v) : $v;
    //         }, $row);
    //         // Force phone columns to string and strip formatting
    //         $phoneFields = ['owner_phone', 'tanent_phone', 'tenant_phone'];
    //         foreach ($phoneFields as $phoneField) {
    //             if (isset($row[$header ? array_search($phoneField, $header) : $phoneField])) {
    //                 $val = $row[$header ? array_search($phoneField, $header) : $phoneField];
    //                 if (!empty($val)) {
    //                     // Remove all non-digits and cast to string
    //                     $row[$header ? array_search($phoneField, $header) : $phoneField] = preg_replace('/\D+/', '', (string)$val);
    //                 }
    //             }
    //         }
            
    //         $rowData = array_combine($header, $row);
            
    //         // Map correct spelling (tenant_*) to database spelling (tanent_*)
    //         $mappingFields = [
    //             'tenant_first_name' => 'tanent_first_name',
    //             'tenant_last_name' => 'tanent_last_name',
    //             'tenant_email' => 'tanent_email',
    //             'tenant_phone' => 'tanent_phone',
    //             'tenant_password' => 'tanent_password',
    //         ];
            
    //         foreach ($mappingFields as $correctKey => $dbKey) {
    //             if (isset($rowData[$correctKey])) {
    //                 $rowData[$dbKey] = $rowData[$correctKey];
    //                 unset($rowData[$correctKey]); // Remove the correct spelling key
    //             }
    //         }
            
    //         $rows[] = $rowData;
    //     }
    
    //     // Flat limit check
    //     $created_flats = \App\Models\Flat::where('building_id', Auth::user()->building_id)->count();
    //     $flat_limit = Auth::user()->building->no_of_flats;
    //     $uploading_flats = count($rows);
    
    //     if (($created_flats + $uploading_flats) > $flat_limit) {
    //         return redirect()->back()->with('error', 'No of flat limit is exceeded');
    //     }
    
    //     // User limit check (no_of_logins) - count through BuildingUser relationships
    //     // Count users with 'user' role (direct users)
    //     $userRole = Role::where('building_id', Auth::user()->building_id)
    //         ->where('slug', 'user')
    //         ->first();
        
    //     $created_users = 0;
    //     if ($userRole) {
    //         $created_users = BuildingUser::where('building_id', Auth::user()->building_id)
    //             ->where('role_id', $userRole->id)
    //             ->count();
    //     }
    //     $user_limit = Auth::user()->building->no_of_logins;
    
    //     // Count how many new users will be created
    //     $new_user_emails = [];
    //     foreach ($rows as $row) {
    //         if (!empty($row['owner_email'])) {
    //             $new_user_emails[] = strtolower($row['owner_email']);
    //         }
    //         if (!empty($row['tanent_email'])) {
    //             $new_user_emails[] = strtolower($row['tanent_email']);
    //         }
    //     }
    //     // Unique emails not already existing in DB
    //     $new_user_emails = array_unique($new_user_emails);
    //     $existing_user_emails = User::whereIn('email', $new_user_emails)->pluck('email')->map(fn($e) => strtolower($e))->toArray();
    //     $to_create_users = array_diff($new_user_emails, $existing_user_emails);
    
    //     if (($created_users + count($to_create_users)) > $user_limit) {
    //         return redirect()->back()->with('error', 'No of user limit (logins) is exceeded');
    //     }
    //     $mails = [];
    //     $results = [
    //         'flats_created' => 0,
    //         'users_created' => 0,
    //         'errors' => []
    //     ];

    //     // Get or create default user role once for the entire bulk upload
    //     $defaultUserRole = Role::where('building_id', Auth::User()->building_id)
    //         ->where('slug', 'user')
    //         ->first();
        
    //     if (!$defaultUserRole) {
    //         // Create default user role if it doesn't exist
    //         $defaultUserRole = new Role();
    //         $defaultUserRole->building_id = Auth::User()->building_id;
    //         $defaultUserRole->name = 'User';
    //         $defaultUserRole->slug = 'user';
    //         $defaultUserRole->type = 'user';
    //         $defaultUserRole->save();
    //     }

    //     DB::beginTransaction();
    //     try {
    //         foreach ($rows as $index => $rowData) {
    //             $blockName = $rowData['block_name'] ?? '';
    //             $blockName = mb_convert_encoding($blockName, 'UTF-8', 'UTF-8');
    //             $blockName = preg_replace('/[\x00-\x1F\x7F\xA0\x{200B}-\x{200D}\x{FEFF}]/u', '', $blockName);
    //             $blockName = preg_replace('/\s+/u', ' ', $blockName);
    //             $blockName = trim($blockName);
                
    //             if ($blockName === '') {
    //                 throw new \Exception("Row " . ($index + 2) . ": Block name is required");
    //             }
                
    //             // Try to find block by name (case-insensitive, flexible matching)
    //             $block = Block::where('building_id', Auth::user()->building_id)
    //                 ->where(function($query) use ($blockName) {
    //                     $query->whereRaw("LOWER(TRIM(name)) = ?", [strtolower($blockName)])
    //                           ->orWhereRaw("LOWER(TRIM(name)) LIKE ?", ['%' . strtolower($blockName) . '%']);
    //                 })
    //                 ->first();

    //             if (!$block) {
    //                 // Get available blocks for better error message
    //                 $availableBlocks = Block::where('building_id', Auth::user()->building_id)
    //                     ->pluck('name')
    //                     ->toArray();
                    
    //                 $errorMsg = "Row " . ($index + 2) . ": Block '{$blockName}' not found. ";
    //                 $errorMsg .= "Available blocks: " . implode(', ', $availableBlocks);
                    
    //                 throw new \Exception($errorMsg);
    //             }
    
    //             // Validation rules
    //             $rules = [
    //                 'flat_name'       => [
    //                     'required',
    //                     Rule::unique('flats', 'name')->where(fn($q) => $q->where('block_id', $block->id)),
    //                 ],
    //                 'area'            => 'required|numeric|min:0',
    //                 'corpus_fund'     => 'required|numeric|min:0',
    //                 'status'          => 'required|in:Inactive,Active',
    //                 'sold_out'        => 'required|in:Yes,No',
    //                 'living_status'   => 'required|in:Vacant,Owner,Tanent',
    //             ];
    
    //             $validator = Validator::make($rowData, $rules);
    //             $validator->after(function ($v) use ($rowData, $index) {

    //                 // Rule 1: If sold_out == Yes → owner_email is required, living_status must be Owner, Tanent, or Vacant
    //                 if ($rowData['sold_out'] === 'Yes') {
    //                     if (empty($rowData['owner_email'])) {
    //                         $v->errors()->add('owner_email', "Row " . ($index + 2) . ": Owner email is required when sold_out = Yes.");
    //                     }
    //                     if (!in_array($rowData['living_status'], ['Owner', 'Tanent', 'Vacant'])) {
    //                         $v->errors()->add('living_status', "Row " . ($index + 2) . ": Living status must be Owner, Tanent, or Vacant when sold_out = Yes.");
    //                     }
    //                 }
            
    //                 // Rule 2: If sold_out == No → living_status must be Vacant
    //                 if ($rowData['sold_out'] === 'No') {
    //                     if ($rowData['living_status'] !== 'Vacant') {
    //                         $v->errors()->add('living_status', "Row " . ($index + 2) . ": Living status must be Vacant when sold_out = No.");
    //                     }
    //                 }
            
    //                 // Rule 3: If living_status == Tanent → both owner_email and tanent_email/phone are required
    //                 if ($rowData['living_status'] === 'Tanent') {
    //                     if (empty($rowData['owner_email'])) {
    //                         $v->errors()->add('owner_email', "Row " . ($index + 2) . ": Owner email is required when living_status = Tanent.");
    //                     }
    //                     if (empty($rowData['tanent_email']) && empty($rowData['tanent_phone'])) {
    //                         $v->errors()->add('tanent_email', "Row " . ($index + 2) . ": Either Tenant email or phone is required when living_status = Tanent.");
    //                     }
    //                 }
            
    //                 // Extra rule: Owner and Tenant cannot be the same
    //                 if (!empty($rowData['owner_email']) && !empty($rowData['tanent_email'])
    //                     && $rowData['owner_email'] == $rowData['tanent_email']) {
    //                     $v->errors()->add('tanent_email', "Row " . ($index + 2) . ": Owner and Tenant cannot be the same person.");
    //                 }
    //             });
    
    //             if ($validator->fails()) {
    //                 throw new \Exception("Row " . ($index + 2) . " failed validation: " . $validator->errors()->first());
    //             }
    
    //             // Create Flat
    //             $flat = new Flat();
    //             $flat->building_id   = Auth::User()->building_id;
    //             $flat->block_id      = $block->id;
    //             $flat->name          = $rowData['flat_name'];
    //             $flat->area          = $rowData['area'];
    //             $flat->corpus_fund   = $rowData['corpus_fund'];
    //             $flat->status        = $rowData['status'];
    //             $flat->sold_out      = $rowData['sold_out'];
    //             $flat->living_status = $rowData['living_status'];
    
    //             $ownerId  = null;
    //             $tenantId = null;
    
    //             if ($rowData['sold_out'] == 'Yes') {
    //                 // Owner
    //                 if (!empty($rowData['owner_email'])) {
    //                     $owner = User::where('email', $rowData['owner_email'])->first();
    //                     if (!$owner) {
    //                         $ownerRules = $this->userValidationRules();
    //                         $ownerData = [
    //                             'first_name' => $rowData['owner_first_name'] ?? null,
    //                             'last_name'  => $rowData['owner_last_name'] ?? null,
    //                             'email'      => $rowData['owner_email'] ?? null,
    //                             'phone'      => $rowData['owner_phone'] ?? null,
    //                             'password'   => $rowData['owner_password'] ?? null,
    //                         ];
                        
    //                         $ownerValidator = Validator::make($ownerData, $ownerRules);
    //                         if ($ownerValidator->fails()) {
    //                             throw new \Exception("Row " . ($index + 2) . " (Owner) failed validation: " . $ownerValidator->errors()->first());
    //                         }
    //                         $owner = new User();
    //                         $owner->first_name   = $rowData['owner_first_name'];
    //                         $owner->last_name    = $rowData['owner_last_name'];
    //                         $owner->email        = $rowData['owner_email'];
    //                         $owner->phone        = $rowData['owner_phone'];
    //                         $owner->password     = Hash::make($rowData['owner_password']);
    //                         $owner->role         = 'user';
    //                         $owner->created_type = 'direct';
    //                         $owner->created_by   = Auth::User()->building_id;
    //                         $owner->status       = 'Active';
    //                         $owner->save();
    //                         $results['users_created']++;
                            
    //                         $mails[] = [
    //                             'email' => $owner->email,
    //                             'name' => $owner->first_name . ' ' . $owner->last_name,
    //                             'password' => $rowData['owner_password'],
    //                         ];
    //                     }
                        
    //                     // Create or update BuildingUser relationship for owner
    //                     $buildingUserExists = BuildingUser::where('building_id', Auth::User()->building_id)
    //                         ->where('user_id', $owner->id)
    //                         ->whereNull('deleted_at')
    //                         ->exists();
                        
    //                     if (!$buildingUserExists) {
    //                         $buildingUser = new BuildingUser();
    //                         $buildingUser->building_id = Auth::User()->building_id;
    //                         $buildingUser->user_id = $owner->id;
    //                         $buildingUser->role_id = $defaultUserRole->id;
    //                         $buildingUser->save();
    //                     }
                        
    //                     $ownerId = $owner->id;
    //                 }
    
    //                 // Tenant
    //                 if ($rowData['living_status'] == 'Tanent' && !empty($rowData['tanent_email'])) {
    //                     $tenant = User::where('email', $rowData['tanent_email'])->first();
    //                     if (!$tenant) {
    //                         $tenantRules = $this->userValidationRules();
    //                         $tenantData = [
    //                             'first_name' => $rowData['tanent_first_name'] ?? null,
    //                             'last_name'  => $rowData['tanent_last_name'] ?? null,
    //                             'email'      => $rowData['tanent_email'] ?? null,
    //                             'phone'      => $rowData['tanent_phone'] ?? null,
    //                             'password'   => $rowData['tanent_password'] ?? null,
    //                         ];
                        
    //                         $tenantValidator = Validator::make($tenantData, $tenantRules);
    //                         if ($tenantValidator->fails()) {
    //                             throw new \Exception("Row " . ($index + 2) . " (Tenant) failed validation: " . $tenantValidator->errors()->first());
    //                         }
    //                         $tenant = new User();
    //                         $tenant->first_name   = $rowData['tanent_first_name'];
    //                         $tenant->last_name    = $rowData['tanent_last_name'];
    //                         $tenant->email        = $rowData['tanent_email'];
    //                         $tenant->phone        = $rowData['tanent_phone'];
    //                         $tenant->password     = Hash::make($rowData['tanent_password']);
    //                         $tenant->role         = 'user';
    //                         $tenant->created_type = 'direct';
    //                         $tenant->created_by   = Auth::User()->building_id;
    //                         $tenant->status       = 'Active';
    //                         $tenant->save();
    //                         $results['users_created']++;
                            
    //                         $mails[] = [
    //                             'email' => $tenant->email,
    //                             'name' => $tenant->first_name . ' ' . $tenant->last_name,
    //                             'password' => $rowData['tanent_password'],
    //                         ];
    //                     }
                        
    //                     // Create or update BuildingUser relationship for tenant
    //                     $buildingUserExists = BuildingUser::where('building_id', Auth::User()->building_id)
    //                         ->where('user_id', $tenant->id)
    //                         ->whereNull('deleted_at')
    //                         ->exists();
                        
    //                     if (!$buildingUserExists) {
    //                         $buildingUser = new BuildingUser();
    //                         $buildingUser->building_id = Auth::User()->building_id;
    //                         $buildingUser->user_id = $tenant->id;
    //                         $buildingUser->role_id = $defaultUserRole->id;
    //                         $buildingUser->save();
    //                     }
                        
    //                     $tenantId = $tenant->id;
    //                 }
    //             }
    
    //             // Set owner and tenant IDs based on living status
    //             if ($rowData['sold_out'] == 'No') {
    //                 // Vacant flat - no owner or tenant
    //                 $ownerId  = null;
    //                 $tenantId = null;
    //             } elseif ($rowData['living_status'] == 'Owner') {
    //                 // Owner living in the flat - owner assigned, no tenant
    //                 $tenantId = null;
    //                 // $ownerId already set above when creating/finding owner
    //             } elseif ($rowData['living_status'] == 'Tanent') {
    //                 // Tenant living in the flat - both owner and tenant assigned
    //                 // Both $ownerId and $tenantId already set above
    //             } elseif ($rowData['living_status'] == 'Vacant') {
    //                 // Sold but vacant - owner assigned but no tenant
    //                 $tenantId = null;
    //                 // $ownerId already set above when creating/finding owner
    //             }
    
    //             $flat->owner_id  = $ownerId;
    //             $flat->tanent_id = $tenantId;
    //             $flat->save();
                
    //             $results['flats_created']++;
    //         }
    
    //         DB::commit();
    //         foreach ($mails as $m) {
    //             try {
    //                 Mail::send('email.password', ['user' => $m, 'password' => $m['password'], 'logo' => $logo], function ($message) use ($m) {
    //                     $message->to($m['email'], $m['name'])->subject('Your Account Credentials');
    //                 });
    //             } catch (\Exception $e) {
    //                 \Log::error("Failed to send mail to {$m['email']}: ".$e->getMessage());
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
    //     }
    
    //     $message = "Bulk upload completed successfully! ";
    //     $message .= "Created {$results['flats_created']} flats";
    //     if ($results['users_created'] > 0) {
    //         $message .= " and {$results['users_created']} users";
    //     }
    //     $message .= ".";
        
    //     return redirect()->back()->with('success', $message);
    // }
    
     protected function userValidationRules($userId = null)
    {
        return [
            'first_name' => 'required|max:30',
            'last_name'  => 'required|max:30',
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[a-zA-Z][a-zA-Z0-9._%+-]*@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'required',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('users', 'phone')->ignore($userId),
                function ($attribute, $value, $fail) {
                    $invalidNumbers = [
                        '0000000000', '1111111111', '2222222222',
                        '1234567890', '0987654321'
                    ];

                    if (in_array($value, $invalidNumbers)) {
                        $fail('Please enter a valid phone number.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',      // at least one lowercase
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[0-9]/',      // at least one digit
                'regex:/[@$!%*#?&]/', // at least one special char
            ],
        ];
    }



}
