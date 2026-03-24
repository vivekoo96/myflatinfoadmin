@extends('layouts.admin')

<style>
    .fw-bold {
        font-weight: 600 !important;
    }
    
    .permission-group {
        border: 1px solid #ddd;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    
    .permission-group label {
        color: #0d6efd; /* Bootstrap primary blue */
    }

</style>

@section('title')
    Custom Roles
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
            <h1>Custom Roles</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Custom Roles</li>
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
                @if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker")
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Department</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Building</th>
                    <th>Name</th>
                    <!--<th>Slug</th>-->
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->custom_roles as $role)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$role->building->name}}</td>
                    <td>{{$role->name}}</td>
                    {{-- <td>{{$role->slug}}</td> --}}
                    <td>
                      <a href="{{url('department',$role->slug)}}" class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      @if(Auth::User()->role == 'BA')
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$role->id}}" data-name="{{$role->name}}" data-slug="{{$role->slug}}" 
                       data-building_id="{{$role->building_id}}"><i class="fa fa-edit"></i></button>
                      @if($role->deleted_at)
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$role->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                      @else
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$role->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                      @endif
                      @endif
                    </td>

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
        <h5 class="modal-title" id="exampleModalLabel">Add Department</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('role.store')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <!--<div class="form-group">-->
          <!--  <label for="name" class="col-form-label">Building:</label>-->
          <!--  <select name="building_id" id="building_id" class="form-control" required>-->
          <!--      <option value="{{$building->id}}">{{$building->name}}</option>-->
          <!--  </select>-->
          <!--</div>-->
          <div class="form-group">
            <label for="name" class="col-form-label">Name:</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Name" required>
          </div>
          <div class="form-group" style="display: none;">
            <label for="name" class="col-form-label">Slug:</label>
            <input type="hidden" name="slug" id="slug" class="form-control" placeholder="Slug">
          </div>
          {{-- Feature Permissions Header --}}
            <div class="mb-3">
                <label class="fw-bold d-block">Feature Permissions:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="permission-all">
                    <label class="form-check-label fw-bold" for="permission-all">Select All</label>
                </div>
            </div>
            
            {{-- Permission Groups --}}
            @foreach($permissions as $group => $items)
                <div class="border p-3 mb-3 rounded">
                    {{-- Group Header --}}
                    <div class="form-check mb-2">
                        <input class="form-check-input permission-group-checkbox" 
                               type="checkbox" 
                               id="group-{{ \Illuminate\Support\Str::slug($group) }}">
                        <label class="form-check-label fw-bold text-primary" for="group-{{ \Illuminate\Support\Str::slug($group) }}">
                            {{ $group }}
                        </label>
                    </div>
            
                    {{-- Group Permissions --}}
                    <div class="ms-4">
                        @foreach($items as $permission)
                            <div class="form-check">
                                <input class="form-check-input permission-checkbox permission-group-{{ \Illuminate\Support\Str::slug($group) }}" 
                                       type="checkbox" 
                                       name="permissions[]" 
                                       value="{{ $permission->id }}" 
                                       id="permission-{{ $permission->id }}"
                                       {{ in_array($permission->id, old('permissions', $building->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <label class="form-check-label" for="permission-{{ $permission->id }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach


          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="type" id="type" value="custom">
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
      var url = "{{route('role.destroy','')}}";
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

    // Auto-generate slug from name
    $('#name').on('input', function() {
        var name = $(this).val();
        var slug = name.toLowerCase()
                      .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                      .replace(/\s+/g, '-')         // Replace spaces with hyphens
                      .replace(/-+/g, '-')          // Replace multiple hyphens with single
                      .trim('-');                   // Remove leading/trailing hyphens
        $('#slug').val(slug);
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      
      // Clear fields first
      $('#name').val('');
      $('#slug').val('');
      $('.modal-title').text('Add New Department');
      // Uncheck all permissions by default
      $('.permission-checkbox').prop('checked', false);
      
      // If editing, populate fields
      if(edit_id) {
        $('.modal-title').text('Update Department');
        $('#name').val(button.data('name'));
        $('#slug').val(button.data('slug'));
        
        // Fetch the role's existing permissions using AJAX
        $.ajax({
            url: "{{ url('get-role-permissions') }}/" + edit_id, // Create a route to fetch permissions
            type: "GET",
            success: function(response) {
                if (response.permissions) {
                    // Uncheck all first
                    $('input[name="permissions[]"]').prop('checked', false);

                    // Loop through and check the permissions
                    response.permissions.forEach(function(permission_id) {
                        $('input[name="permissions[]"][value="' + permission_id + '"]').prop('checked', true);
                    });
                }
            }
        });
      }
      
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-role-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    // Master Select All
    $('#permission-all').on('change', function () {
        $('input.permission-checkbox, input.permission-group-checkbox').prop('checked', this.checked);
    });
    
    // Group Select/Deselect
    $('.permission-group-checkbox').on('change', function () {
        let groupClass = '.permission-' + $(this).attr('id');
        $('input' + groupClass).prop('checked', this.checked);
    });
    
    // Auto-check group/master when individual is toggled
    $('.permission-checkbox').on('change', function () {
        let group = $(this).attr('class').match(/permission-group-[\w-]+/);
        if (group) {
            let groupSelector = '.' + group[0];
            let groupCheckbox = $('#group-' + group[0].replace('permission-group-', ''));
            let allChecked = $(groupSelector).length === $(groupSelector + ':checked').length;
            groupCheckbox.prop('checked', allChecked);
        }
        $('#permission-all').prop('checked', $('input.permission-checkbox').length === $('input.permission-checkbox:checked').length);
    });


  });
</script>
@endsection

@endsection


