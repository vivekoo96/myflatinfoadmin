@extends('layouts.admin')

@section('title')
    Guard Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Guard Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Guard Details</li>
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
                       src="{{$guard->user->photo ?? ''}}"
                       alt="User profile picture">
                </div>
                <h3 class="profile-username text-center">{{$guard->user->name ?? ($guard->user->first_name ?? '') . ' ' . ($guard->user->last_name ?? '')}}</h3>

                <p class="text-muted text-center">{{$guard->user->role ?? 'Guard'}}</p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Email</b> <a class="float-right">{{$guard->user->email ?? 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Phone</b> <a class="float-right">{{$guard->user->phone ?? 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Gender</b> <a class="float-right">{{$guard->user->gender ?? 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$guard->user->id}}" data-bootstrap-switch data-on-text="Active"
                        data-off-text="Inactive" {{$guard->user->status == 'Active' ? 'checked' : ''}}>
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
                          <td>{{$guard->user->name ?? ($guard->user->first_name ?? '') . ' ' . ($guard->user->last_name ?? '')}}</td>
                          <td>Shift</th>
                          <td>{{$guard->shift ?? 'N/A'}}</td>
                        </tr>

                        <tr>
                          <td>Email</th>
                          <td>{{$guard->user->email ?? 'N/A'}}</td>
                          <td>Phone</th>
                          <td>{{$guard->user->phone ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                          <td>Gender</th>
                          <td>{{$guard->user->gender ?? 'N/A'}}</td>
                          <td>Company</th>
                          <td>{{$guard->user->company_name ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                          <td>Building</th>
                          <td>{{$guard->building->name ?? 'N/A'}}</td>
                          <td>Block</th>
                          <td>{{$guard->block->name ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                          <td>Gate</th>
                          <td>{{$guard->gate->name ?? 'N/A'}}</td>
                          <td>Status</th>
                          <td>{{$guard->status ?? 'N/A'}}</td>
                        </tr>
                        <tr>
                          <td>ID Proof Type</th>
                          <td>{{$guard->id_proof_type ?? 'N/A'}}</td>
                          <td>ID Proof Number</th>
                          <td>{{$guard->id_proof_number ?? 'N/A'}}</td>
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
