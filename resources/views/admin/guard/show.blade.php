@extends('layouts.admin')

@section('title')
    Customer Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Customer Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Customer Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle"
                       src="{{$customer->photo}}"
                       alt="User profile picture">
                </div>
                <h3 class="profile-username text-center">{{$customer->name}}</h3>

                <p class="text-muted text-center">{{$customer->role}}</p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Email</b> <a class="float-right">{{$customer->email}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Phone</b> <a class="float-right">{{$customer->phone}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Gender</b> <a class="float-right">{{$customer->gender}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Wallet</b> <a class="float-right">{{$customer->wallet}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$customer->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$customer->status == 'Active' ? 'checked' : ''}}>
                    </a>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic Info</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="basic">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr style="display:none;">
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Name</th>
                          <td>{{$customer->name}}</td>
                          <td>Role</th>
                          <td>{{$customer->role}}</td>
                        </tr>

                        <tr>
                          <td>Email</th>
                          <td>{{$customer->email}}</td>
                          <td>Phone</th>
                          <td>{{$customer->phone}}</td>
                        </tr>
                        <tr>
                          <td>Gender</th>
                          <td>{{$customer->gender}}</td>
                          <td>City</th>
                          <td>{{$customer->city->name}}</td>
                        </tr>
                        <tr>
                          <td>Address</th>
                          <td colspan="3">{{$customer->address}}</td>
                        </tr>
                        <tr>
                          <td>Created at</th>
                          <td>{{$customer->created_at}}</td>
                          <td>Updated at</th>
                          <td>{{$customer->updated_at}}</th>
                        </tr>
                        
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    
    


@section('script')
<script>
    $(document).ready(function(){
        var id = '';
        var action = '';
        var token = "{{csrf_token()}}";
        
        $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-user-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
    });
</script>
@endsection

@endsection
