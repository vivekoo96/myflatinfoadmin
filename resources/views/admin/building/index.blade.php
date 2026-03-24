@extends('layouts.admin')


@section('title')
    Building List
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
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
          <div class="col-sm-6">
            <h1>Building List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Buildings</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
              <div class="card-header">
                <!--<button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Building</button>-->
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Name</th>
                    <th>BA</th>
                    <th>Builder</th>
                    <th>Builder Contact</th>
                    <!-- <th>City</th> -->
                    <th>Flats</th>
                    <th>Users</th>
                    <th>Status</th>
                    <!--<th>Action</th>-->
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($buildings as $building)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$building->name}}</td>
                    <td>{{$building->user->name}}</td>
                    <td>{{$building->builder->name}}</td>
                    <td>{{$building->builder->phone}}</td>
                    
                    <td>{{$building->flats->count()}}</td>
                    <td>{{$building->users->count()}}</td>
                    <td>{{$building->status}}</td>
                    <!--<td>-->
                    <!--  <a href="{{route('buildings.show',$building->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>-->
                    <!--  <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$building->id}}" data-name="{{$building->name}}" data-status="{{$building->status}}"-->
                    <!--  data-city_id="{{$building->city_id}}" data-zip_code="{{$building->zip_code}}" data-address="{{$building->address}}" data-user_id="{{$building->user_id}}" -->
                    <!--  data-image="{{$building->image}}" data-owner_name="{{$building->owner_name}}" data-owner_contact_no="{{$building->owner_contact_no}}"><i class="fa fa-edit"></i></button>-->
                    <!--  @if($building->deleted_at)-->
                    <!--  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$building->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                    <!--  @else-->
                    <!--  <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$building->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                    <!--  @endif-->
                    <!--</td>-->

                  </tr>
                  @empty
                  @endforelse
                  </tbody>
                </table>
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New Building</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('buildings.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="name" class="col-form-label">Building Name:</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Building Name" minlength="4" maxlength="20" required>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Owner Name:</label>
            <input type="text" name="owner_name" id="owner_name" class="form-control" placeholder="Owner Name" minlength="4" maxlength="20" required>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Owner Contact Number:</label>
            <input type="text" name="owner_contact_no" id="owner_contact_no" class="form-control" placeholder="Owner Contact No" required>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">City:</label>
            <select name="city_id" id="city_id" class="form-control" required>
                @forelse($cities as $city)
                <option value="{{$city->id}}">{{$city->name}}</option>
                @empty
                @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Address:</label>
            <textarea name="address" id="address" class="form-control"></textarea>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Zip Code:</label>
            <input type="text" name="zip_code" id="zip_code" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Image: <img src="" id="image2" style="width:40px"></label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pending">Pending</option>
                <option value="Active">Active</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id" value="">
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
      $('.modal-title').text('Are you sure ?')
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
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
      var url = "{{route('buildings.destroy','')}}";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'action':action},
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
      $('#name').val(button.data('name'));
      $('#owner_name').val(button.data('owner_name'));
      $('#owner_contact_no').val(button.data('owner_contact_no'));
      $('#city_id').val(button.data('city_id'));
      $('#zip_code').val(button.data('zip_code'));
      $('#address').val(button.data('address'));
      $('#status').val(button.data('status'));
      $('#user_id').val(button.data('user_id'));
      $('#image2').attr('src',button.data('image'));
      $('.modal-title').text('Add New Building');
      $('#image').attr('required',true);
      if(edit_id){
          $('#image').attr('required',false);
          $('.modal-title').text('Update Building');
      }
      
    });
    
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

