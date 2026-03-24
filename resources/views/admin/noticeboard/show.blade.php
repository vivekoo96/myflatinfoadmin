@extends('layouts.admin')

@section('title')
    Building Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Building Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Building Details</li>
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
                       src="{{$building->image}}"
                       alt="User profile picture">
                </div>
                <h3 class="profile-username text-center">{{$building->name}}</h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Owner</b> <a class="float-right">{{$building->user->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>City</b> <a class="float-right">{{$building->city->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Zip</b> <a class="float-right">{{$building->city->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Address</b><a class="float-right">{{$building->address}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Members</b> <a class="float-right">{{$building->users->count()}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$building->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$building->status == 'Active' ? 'checked' : ''}}>
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
                <div class="">
                    @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session()->get('error') }}
                    </div>
                    @endif
                    @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                    @endif
                </div>
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#users" data-toggle="tab">Users</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="users">
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Building User</button>
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>User</th>
                              <th>Block</th>
                              <th>Flat No</th>
                              <th>Rent Type</th>
                              <th>Rent Amount</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse($building->users as $b_user)
                        <tr>
                          <td>{{$b_user->user->name}}</td>
                          <td>{{$b_user->block}}</td>
                          <td>{{$b_user->flat_no}}</td>
                          <td>{{$b_user->rent_type}}</td>
                          <td>{{$b_user->rent}}</td>
                          <td>{{$b_user->status}}</td>
                          <td>
                      <a href="{{url('building-user',$b_user->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$b_user->id}}" data-email="{{$b_user->user->email}}" data-name="{{$b_user->user->name}}" 
                      data-block="{{$b_user->block}}" data-flat_no="{{$b_user->flat_no}}" data-rent="{{$b_user->rent}}" data-status="{{$b_user->status}}"><i class="fa fa-edit"></i></button>
                      @if($building->deleted_at)
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$b_user->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                      @else
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$b_user->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                      @endif
                    </td>
                        </tr>
                        @empty
                        @endforelse
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
    
<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New Building User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-building-user')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="form-group">
            <label for="email" class="col-form-label">Customer Email:</label>
            <div class="input-group">
              <input type="email" name="email" class="form-control" id="email" maxlength="40" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getUserData">Get User Data</button>
              </div>
            </div>
          </div>
          <div class="error text-danger"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">Customer Name:</label>
            <input type="text" name="name" class="form-control" id="name" disabled required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Block:</label>
            <input type="text" name="block" id="block" class="form-control" placeholder="Block" required>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Flat No:</label>
            <input type="text" name="flat_no" class="form-control" id="flat_no" placeholder="Flat No" required />
          </div>
          
          <div class="form-group">
            <label for="rent_type" class="col-form-label">Rent Type:</label>
            <select name="rent_type" class="form-control" required>
              <option value="Daily">Daily</option>
              <option value="Weekly">Weekly</option>
              <option value="Monthly">Monthly</option>
              <option value="Yearly">Yearly</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="rent" class="col-form-label">Rent Amount:</label>
            <input type="text" name="rent" id="rent" class="form-control" placeholder="Rent Amount" minlength="1" maxlength="7"
                          onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Living Status:</label>
            <select name="status" class="form-control">
              <option value="Staying">Staying</option>
              <option value="Left">Left</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="building_id" id="building_id" value="{{$building->id}}">
          <input type="hidden" name="user_id" id="user_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" id="delete-button">Confirm Delete</button>
      </div>
    </div>
  </div>
</div>

@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      $('.modal-title').text('Are you sure ?');
      if(action == 'delete'){
          $('#delete-button').addClass('btn-danger');
          $('#delete-button').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item..');
      }else{
          $('#delete-button').addClass('btn-success');
          $('#delete-button').text('Confirm Restore');
          $('.text').text('You are going to restore this item..');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "{{url('delete-building-user')}}";
      $.ajax({
        url : url,
        type: "POST",
        data : {'_token':token,'id':id,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#email').val(button.data('email'));
      $('#name').val(button.data('name'));
      $('#block').val(button.data('block'));
      $('#flat_no').val(button.data('flat_no'));
      $('#rent_type').val(button.data('rent_type'));
      $('#rent').val(button.data('rent'));
      $('.modal-title').text('Add New Building User');
      if(edit_id){
          $('.modal-title').text('Update Building User');
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-building-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      if ($('#name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getUserData').on('click', function () {
      var email = $('#email').val().trim();
      if (email === '') {
        $('.error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'GET',
        data: { email: email },
        success: function (response) {
          if (response.success) {
            $('#name').val(response.data.name);
            $('#user_id').val(response.data.id);
          } else {
            $('.error').text('User not found.');
            $('#name').val('');
          }
        },
        error: function () {
          $('.error').text('Error fetching user data.');
          $('#name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



