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
                    <b>Owner</b> <a class="float-right">{{$building->user ? $building->user->name : 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>City</b> <a class="float-right">{{$building->city ? $building->city->name : 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Zip</b> <a class="float-right">{{$building->zip_code ?? 'N/A'}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Address</b><a class="float-right">{{$building->address}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Blocks</b> <a class="float-right">{{$building->blocks ? $building->blocks->count() : 0}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Flats</b> <a class="float-right">{{$building->flats ? $building->flats->count() : 0}}</a>
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
                  <li class="nav-item"><a class="nav-link active" href="#blocks" data-toggle="tab">Blocks</a></li>
                  <li class="nav-item"><a class="nav-link" href="#flats" data-toggle="tab">Flats</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="blocks">
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Block</button>
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                      <thead>
                          <tr>
                              <th>Name</th>
                              <th class="d-none d-sm-table-cell">Flats</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse($building->blocks as $block)
                        <tr>
                          <td>{{$block->name}}</td>
                          <td class="d-none d-sm-table-cell">{{$block->flats ? $block->flats->count() : 0}}</td>
                          <td>{{$block->status}}</td>
                          <td>
                      <a href="{{route('block.show',$block->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$block->id}}" data-status="{{$block->status}}"
                      data-building_id="{{$block->building_id}}"><i class="fa fa-edit"></i></button>
                      @if($block->deleted_at)
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$block->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                      @else
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$block->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
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
                  
                  <div class="tab-pane" id="flats">
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Block</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                      <thead>
                          <tr>
                              <th>Block</th>
                              <th>Flat</th>
                              <th class="d-none d-md-table-cell">Owner</th>
                              <th class="d-none d-lg-table-cell">Tenant</th>
                              <th class="d-none d-sm-table-cell">Area</th>
                              <th class="d-none d-md-table-cell">Max Members</th>
                              <th class="d-none d-lg-table-cell">Family Members</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse($building->flats as $flat)
                        <tr>
                          <td>{{$flat->block ? $flat->block->name : 'N/A'}}</td>
                          <td>{{$flat->name}}</td>
                          <td class="d-none d-md-table-cell">{{$flat->owner ? $flat->owner->name : 'N/A'}}</td>
                          <td class="d-none d-lg-table-cell">{{$flat->tanent ? $flat->tanent->name : 'N/A'}}</td>
                          <td class="d-none d-sm-table-cell">{{$flat->area}}</td>
                          <td class="d-none d-md-table-cell">{{$flat->max_members}}</td>
                          <td class="d-none d-lg-table-cell">
                            @if($flat->family_members)
                              @if(is_array(json_decode($flat->family_members, true)))
                                {{count(json_decode($flat->family_members, true))}}
                              @elseif(is_numeric($flat->family_members))
                                {{$flat->family_members}}
                              @else
                                {{$flat->family_members}}
                              @endif
                            @else
                              0
                            @endif
                          </td>
                          <td>{{$flat->status}}</td>
                          <td>
                      <a href="{{route('flat.show',$flat->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Block</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('block.store')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="error text-danger"></div>
          <div class="form-group">
            <label for="building_id" class="col-form-label">Building:</label>
            <select name="building_id" class="form-control" id="building_id" required>
              <option value="{{$building->id}}">{{$building->name}}</option>
            </select>
          </div>
          <div class="form-group">
            <label for="email" class="col-form-label">Block Name:</label>
            <input type="text" name="name" class="form-control" id="name" required>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control">
              <option value="Active">Active</option>
              <option value="Pending">ending</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
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
      var url = "{{route('block.destroy','')}}";
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
      $('#name').val(button.data('name'));
      $('#building_id').val(button.data('building_id'));
      $('#status').val(button.data('status'));
      $('.modal-title').text('Add New Block');
      if(edit_id){
          $('.modal-title').text('Update Block');
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
        
    

  });
</script>
@endsection

@endsection



