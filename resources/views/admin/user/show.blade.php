@extends('layouts.admin')

@section('title')
    User Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
             <h1>{{ ($role_name && $role_name !== 'User') ? 'Other User Details' : 'User Details' }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">User Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center mb-3">
                  <img class="profile-user-img img-fluid img-circle" src="{{$customer->photo}}" alt="User profile picture" style="width:100px;height:100px;object-fit:cover;">
                </div>
                <h3 class="profile-username text-center mb-3">{{$customer->name}}</h3>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped mb-0">
                    <tbody>
                      <tr>
                        <td><b>Role</b></td>
                        <td colspan="3">
                          {{ $role_name }}
                        </td>
                        {{-- <td><b>Status</b></td> --}}
                        {{-- <td>
                          @if(Auth::User()->role == 'BA')
                            <input type="checkbox" name="my-checkbox" class="status" data-id="{{$customer->id}}" data-bootstrap-switch data-on-text="Active" 
                            data-off-text="Inactive" {{$customer->status == 'Active' ? 'checked' : ''}}>
                          @else
                            {{$customer->status}}
                          @endif
                        </td> --}}
                      </tr>
                      <tr>
                        <td><b>Email</b></td>
                        <td>{{$customer->email}}</td>
                        <td><b>Phone</b></td>
                        <td>{{$customer->phone}}</td>
                      </tr>
                      <tr>
                        <td><b>Gender</b></td>
                        <td>{{$customer->gender}}</td>
                        <td><b>Departments</b></td>
                        <td colspan="3">
                          @php
                            $filteredDepartments = $customer->departments->filter(function($department) {
                              return $department->role && $department->role->name !== 'User' && $department->building_id == Auth::user()->building_id;
                            });
                          @endphp
                          @forelse($filteredDepartments as $department)
                            {{$department->role->name}}@if(!$loop->last), @endif
                          @empty
                            No departments assigned
                          @endforelse
                        </td>
                      </tr>
                      
                    
                      <tr>
                        <td><b>Created at</b></td>
                        <td>{{$customer->created_at}}</td>
                        <td><b>Updated at</b></td>
                        <td>{{$customer->updated_at}}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
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
