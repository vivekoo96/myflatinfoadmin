@extends('layouts.admin')

@section('title')
    Gate Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Gate Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Gate Details</li>
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
                <h3 class="profile-username text-center">{{$gate->name}}</h3>
                </div>
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Building</b> <a class="float-right">{{$gate->building->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Block</b> <a class="float-right">{{$gate->block->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Flats</b> <a class="float-right">{{$gate->guards->count()}}</a>
                  </li>
                  
              {{--    <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        @if(Auth::User()->role == 'BA')
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$gate->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$gate->status == 'Active' ? 'checked' : ''}}>
                        @else
                        {{$gate->status}}
                        @endif
                    </a>
                  </li> --}}
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
                  <li class="nav-item"><a class="nav-link active" href="#flats" data-toggle="tab">Guards</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="flats">
                      @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security'))
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Guard</button>-->
                      @endif
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Image</th>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Phone</th>
                              <th>Gender</th>
                              <th>Shift</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        @forelse($gate->guards as $guard)
                        <?php $i++ ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td><img src="{{$guard->user->photo}}" style="width:40px"></td>
                          <td>{{$guard->user->name}}</td>
                          <td>{{$guard->user->email}}</td>
                          <td>{{$guard->user->phone}}</td>
                          <td>{{$guard->user->gender}}</td>
                          <td>{{$guard->shift}}</td>
                          <td>{{$guard->status}}</td>
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
      var url = "{{route('guard.destroy','')}}";
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
    


  $('.status').bootstrapSwitch('state');
  $('.status').on('switchChange.bootstrapSwitch', function (event, state) {
    var id = $(this).data('id');
    var status = state ? 'Active' : 'Inactive';
    $.ajax({
      url: "{{ url('update-gate-status') }}",
      type: "POST",
      data: {
        _token: token,
        id: id,
        status: status
      },
      success: function (data) {
        if (data.success) {
          // Optionally show a success message
        } else {
          alert(data.message || 'Failed to update status.');
        }
      },
      error: function (xhr) {
        if (xhr.status === 401) {
          alert('You are not logged in. Please login and try again.');
          window.location.reload();
        } else if (xhr.status === 403) {
          alert('You do not have permission to perform this action.');
        } else {
          alert('An error occurred while updating status.');
        }
      }
    });
  });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#user_email').val(button.data('user_email'));
      $('#user_name').val(button.data('user_name'));
      $('#user_id').val(button.data('user_id'));
      $('#building_id').val(button.data('building_id'));
      $('#block_id').val(button.data('block_id'));
      $('#gate_id').val(button.data('gate_id'));
      $('#shift').val(button.data('shift'));
      $('#status').val(button.data('status'));
      $('.modal-title').text('Add New Payment');
      if(edit_id){
          $('.modal-title').text('Update Payment');
      }
    });
    

        
    $('.add-form').on('submit', function (event) {
      if ($('#user_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getUserData').on('click', function () {
      var email = $('#user_email').val().trim();
      if (email === '') {
        $('.error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: { _token:token,email: email },
        success: function (response) {
          if (response.success) {
            $('#user_name').val(response.data.name);
            $('#user_id').val(response.data.id);
          } else {
            $('.error').text('User not found.');
            $('#user_name').val('');
          }
        },
        error: function () {
          $('.error').text('Error fetching user data.');
          $('#user_name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



