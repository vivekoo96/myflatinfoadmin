@extends('layouts.admin')

@section('title')
    Parking Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Parking Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Parking Details</li>
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
                <h3 class="profile-username text-center">{{$parking->name}}</h3>
                </div>
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Building</b> <a class="float-right">{{$parking->building->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Block</b> <a class="float-right">{{$parking->block->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Flats</b> <a class="float-right">{{$parking->flats->count()}}</a>
                  </li>
                  
               {{--   <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        @if(Auth::User()->role == 'BA')
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$parking->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$parking->status == 'Active' ? 'checked' : ''}}>
                        @else
                        {{$parking->status}}
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
                      @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security'))
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Flat</button>
                      @endif
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Building</th>
                              <th>Block</th>
                              <th>Flat</th>
                              <th>Owner</th>
                              <th>Tenant</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        @forelse($parking->flats as $flat)
                        <?php $i++ ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td>{{$flat->building->name}}</td>
                          <td>{{$flat->block->name}}</td>
                          <td>{{$flat->name}}</td>
                          <td>{{$flat->owner ? $flat->owner->name : ''}}</td>
                          <td>{{$flat->tanent ? $flat->tanent->name : ''}}</td>
                          <td>
                            @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security'))
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$flat->pivot->id}}" 
                                 data-flat_id="{{$flat->id}}" data-flat_name="{{$flat->name}}"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$flat->pivot->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Flat</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-parking-flat')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="form-group">
            <label for="flat" class="col-form-label">Flat No:</label>
            <div class="input-group">
              <input type="text" name="flat" class="form-control" id="flat" maxlength="40" placeholder="Flat name or number" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getFlatData">Get Flat Data</button>
              </div>
            </div>
          </div>
          <div class="error text-danger"></div>
          <div class="form-group">
            <label for="flat" class="col-form-label">Flat Name:</label>
            <input type="text" name="flat_name" class="form-control" id="flat_name" disabled required>
          </div>

          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="parking_id" id="parking_id" value="{{$parking->id}}">
          <input type="hidden" name="flat_id" id="flat_id">
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
      $('.modal-title').text('Are you sure ?');
      $('#delete-button').text('Confirm Delete');
      $('.text').text('You are going to permanently delete this item..');
    });

    $(document).on('click','#delete-button',function(){
      var url = "{{url('delete-parking-flat','')}}";
      $.ajax({
        url : url,
        type: "POST",
        data : {'_token':token,'id':id},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-guard-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#flat_name').val(button.data('flat_name'));
      $('#flat').val(button.data('flat_name'));
      $('#flat_id').val(button.data('flat_id'));
      $('.modal-title').text('Add New Flat');
      if(edit_id){
          $('.modal-title').text('Update Flat');
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-event-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      if ($('#flat_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Flat Number not Found. Please check and Enter a Valid Flat Number.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getFlatData').on('click', function () {
      var flat = $('#flat').val().trim();
      if (flat === '') {
        $('.error').text('Flat Number not Found. Please check and Enter a Valid Flat Number.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-flat") }}', // Update with your actual route
        type: 'POST',
        data: { _token:token,flat: flat },
        success: function (response) {
          if (response.success) {
            $('#flat_name').val(response.data.name);
            $('#flat_id').val(response.data.id);
          } else {
            $('.error').text('Flat Number not Found. Please check and Enter a Valid Flat Number.');
            $('#flat_name').val('');
          }
        },
        error: function () {
          $('.error').text('Flat Number not Found. Please check and Enter a Valid Flat Number.');
          $('#flat_name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



