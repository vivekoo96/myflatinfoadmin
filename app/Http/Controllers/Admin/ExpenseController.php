<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\Event;
use App\Models\Essential;
use App\Models\Facility;

use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{

    public function index()
    {
        $building = Auth::User()->building;
        return view('admin.expenses.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            'ename' => 'string|max:255',
            'reason' => 'required',
            'amount' => 'required',
            'date' => 'required',
            'image' => 'nullable|image'
        ];
        if($request->reciptdata){
            
        }
        
        $expense = new Expense();
            if($request->reciptdata){
                $msg = 'Receipt added successfully';
            }else{
                 $msg = 'Expense added successfully';
            }
        if ($request->id) {
            $expense = Expense::find($request->id);
            if($request->reciptdata){
               $msg = 'Receipt updated Susccessfully';
            }else{
                 $msg = 'Expense updated successfully';
            }
            
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error',$validation->errors()->first());
        }
        $user = Auth::User();
        $expense->user_id = $user->id;
        $expense->building_id = $user->building_id;
        $expense->ename = $request->ename;
        $expense->model = $request->model;
        $expense->model_id = $request->model_id;
        $expense->payment_type = $request->payment_type;
        $expense->reason = $request->reason;
        $expense->type = $request->type;
        $expense->date = $request->date;
        $expense->amount = $request->amount;
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($expense->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/expenses/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $expense->image = $filename;
        // }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($expense->image_filename)) {
                $file_path = public_path('images/' . $expense->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'expenses/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/expenses/'), $filename);
            $expense->image = $filename;
        }
        $expense->save();
        $transaction = new Transaction();
        $transaction->building_id = $user->building_id;
        $transaction->user_id = $user->id;
        $transaction->model = $request->model;
        $transaction->model_id = $request->model_id;
        $transaction->date = $request->date;
        $transaction->type = $request->type;
        $transaction->payment_type = $request->payment_type;
        $transaction->amount = $request->amount;
        $transaction->reciept_no = 'EXPS'.rand(10000000,99999999);
        $transaction->desc = $request->reason;
        $transaction->status = 'Success';
        $transaction->save();
        
        $expense->transaction_id = $transaction->id;
        $expense->save();
    
        return redirect()->back()->with('success',$msg);
    }

    public function show($id)
    {
        //
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
        //
    }

    public function get_model_data(Request $request)
    {
        $model = $request->model;
        $model_id = $request->model_id;
        $user = Auth::User();
        if($model == 'Event'){
            $events = Event::where('building_id',$user->building_id)->get();
            return view('partials.events',compact('events','model_id'));
        }
        if($model == 'Essential'){
            $essentials = Essential::where('building_id',$user->building_id)->get();
            return view('partials.essentials',compact('essentials','model_id'));
        }
        if($model == 'Facility'){
            $facilities = Facility::where('building_id',$user->building_id)->get();
            return view('partials.facilities',compact('facilities','model_id'));
        }
    }
}
