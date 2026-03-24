@extends('layouts.admin')

@section('title')
    Taxes
@endsection

@section('content')
<!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Taxes</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Taxes</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-header text-danger">Make sure this is goverment issue !!</div>
                        <div class="card-body">
                            <form action="{{url('update-taxes')}}" method="post" class="tax-form">
                                @csrf
                                <div class="form-group">
                                    <label>SGST</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control sgst" name="sgst" value="{{$setting->sgst}}" min="0" max="30" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Cancellation Check Amount</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="cancellation_check_amount" value="{{$setting->cancellation_check_amount}}" minlength="1" maxlength="4" 
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>CGST</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control cgst" name="cgst" value="{{$setting->cgst}}" min="0" max="30" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Refundable Deposit</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="admin_charge" value="{{$setting->admin_charge}}" minlength="1" maxlength="7" 
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" class="btn bg-gradient-primary btn-flat" value="Save Changes">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


@section('script')


@endsection

@endsection