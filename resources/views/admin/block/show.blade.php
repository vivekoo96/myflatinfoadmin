@extends('layouts.admin')

@section('title')
    Block Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Block Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Block Details</li>
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
                <h3 class="profile-username text-center">{{$block->name}}</h3>
                </div>
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Building</b> <a class="float-right">{{$block->building->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Flats</b> <a class="float-right">{{$block->flats->count()}}</a>
                  </li>
                  
                  {{--<li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        @if(Auth::User()->role == 'BA')
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$block->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$block->status == 'Active' ? 'checked' : ''}}>
                        @else
                        {{$block->status}}
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
                  <li class="nav-item"><a class="nav-link active" href="#flats" data-toggle="tab">Flats</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="flats">
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Flat</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th>Owner</th>
                              <th>Tenant</th>
                              <th>Area</th>
                              <th>Max Members</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse($block->flats as $flat)
                        <tr>
                          <td>{{$flat->name}}</td>
                          <td>{{$flat->owner ? $flat->owner->name : 'N/A'}}</td>
                          <td>{{$flat->tanent ? $flat->tanent->name : 'N/A'}}</td>
                          <td>{{$flat->area}}</td>
                          <td>{{$flat->max_members}}</td>
                          <td>{{$flat->status}}</td>
                          <td>
                            <a href="{{route('flat.show',$flat->id)}}"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                            
                          <!--  <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$flat->id}}" data-name="{{$flat->name}}" data-status="{{$flat->status}}"-->
                          <!--  data-owner_name="{{$flat->owner ? $flat->owner->name : ''}}" data-tanent_name="{{$flat->tanent ? $flat->tanent->name : ''}}" data-owner_id="{{$flat->owner_id}}" data-tanent_id="{{$flat->tanent_id}}"-->
                          <!--  data-area="{{$flat->area}}" data-max_members="{{$flat->max_members}}"><i class="fa fa-edit"></i></button>-->
                          <!--  @if($flat->deleted_at)-->
                          <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$flat->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                          <!--@else-->
                          <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$flat->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                          <!--@endif-->
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Flat</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('flat.store')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="form-group">
            <label for="email" class="col-form-label">Owner Email:</label>
            <div class="input-group">
              <input type="email" name="owner_email" class="form-control" id="owner_email" maxlength="40" placeholder="Owner Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getOwnerData">Get Owner Data</button>
              </div>
            </div>
          </div>
          <div class="owner_error text-danger"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">Owner Name:</label>
            <input type="text" name="owner_name" class="form-control" id="owner_name" disabled required>
          </div>
          <div class="form-group">
            <label for="email" class="col-form-label">Tanent Email:</label>
            <div class="input-group">
              <input type="email" name="tanent_email" class="form-control" id="tanent_email" maxlength="40" placeholder="Tanent Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getTanentData">Get Tanent Data</button>
              </div>
            </div>
          </div>
          <div class="tanent_error text-danger"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">Tanent Name:</label>
            <input type="text" name="tanent_name" class="form-control" id="tanent_name" disabled required>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Flat Name or Number:</label>
            <input type="text" name="name" class="form-control" id="name" placeholder="Flat Name or No" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Flat Area:</label>
            <input type="text" name="area" class="form-control" id="area" placeholder="Flat Area" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Max Members:</label>
            <input type="number" name="max_members" class="form-control" id="max_members" placeholder="Max Members" required />
          </div>
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control">
              <option value="Active">Active</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="building_id" id="building_id" value="{{$block->building->id}}">
          <input type="hidden" name="block_id" id="block_id" value="{{$block->id}}">
          <input type="hidden" name="owner_id" id="owner_id" value="">
          <input type="hidden" name="tanent_id" id="tanent_id" value="">
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
      var url = "{{route('flat.destroy','')}}";
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
      $('#owner_name').val(button.data('owner_name'));
      $('#tanent_name').val(button.data('tanent_name'));
      $('#owner_email').val(button.data('owner_email'));
      $('#tanent_email').val(button.data('tanent_email'));
      $('#owner_id').val(button.data('owner_id'));
      $('#tanent_id').val(button.data('tanent_id'));
      $('#name').val(button.data('name'));
      $('#area').val(button.data('area'));
      $('#max_members').val(button.data('max_members'));
      $('.modal-title').text('Add New Flat');
      if(edit_id){
          $('.modal-title').text('Update Flat');
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
      if ($('#owner_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Owner Name is required. Please fetch owner data.');
      }
      if ($('#tanent_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Tanent Name is required. Please fetch tanent data.');
      }
    });
    
    // Fetch owner data when clicking "Get Owner Data"
    $('#getOwnerData').on('click', function () {
      var owner_email = $('#owner_email').val().trim();
      if (owner_email === '') {
        $('.owner_error').text('Please enter an email to fetch owner data.');
        return;
      }
      
      $('.owner_error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: {'_token':token, email: owner_email },
        success: function (response) {
          if (response.success) {
            $('#owner_name').val(response.data.name);
            $('#owner_id').val(response.data.id);
          } else {
            $('.owner_error').text('Owner not found.');
            $('#owner_name').val('');
          }
        },
        error: function () {
          $('.owner_error').text('Error fetching owner data.');
          $('#owner_name').val('');
        }
      });
    });
    
    // Fetch tanent data when clicking "Get Tanent Data"
    $('#getTanentData').on('click', function () {
      var tanent_email = $('#tanent_email').val().trim();
      if (tanent_email === '') {
        $('.tanent_error').text('Please enter an email to fetch tanent data.');
        return;
      }
      
      $('.tanent_error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: { '_token':token, email: tanent_email },
        success: function (response) {
          if (response.success) {
            $('#tanent_name').val(response.data.name);
            $('#tanent_id').val(response.data.id);
          } else {
            $('.tanent_error').text('Owner not found.');
            $('#tanent_name').val('');
          }
        },
        error: function () {
          $('.tanent_error').text('Error fetching tanent data.');
          $('#tanent_name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



