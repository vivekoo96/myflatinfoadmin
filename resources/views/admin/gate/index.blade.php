@extends('layouts.admin')


@section('title')
    Gate List
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
            <h1>Gate List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Gates</li>
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
                @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'security'))
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Gate</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <!-- Filter Section -->
                <div class="row mb-3">
                  <div class="col-md-3">
                    <label for="blockFilter">Filter by Block:</label>
                    <select id="blockFilter" class="form-control">
                      <option value="">All Blocks</option>
                      @forelse($building->blocks()->where('status','Active')->get() as $block)
                      <option value="{{$block->name}}">{{$block->name}}</option>
                      @empty
                      @endforelse
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label for="statusFilter">Filter by Status:</label>
                    <select id="statusFilter" class="form-control">
                      <option value="">All Status</option>
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="button" id="clearFilters" class="btn btn-secondary btn-block">Clear All Filters</button>
                  </div>
                </div>
                
                <div class="table-responsive">
                <table id="gatesTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Building</th>
                    <th>Block Name</th>
                    <th>Gate Name</th>
                    <th>Guards</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->gates as $gate)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$gate->building->name}}</td>
                    <td>{{$gate->block->name}}</td>
                    <td>{{$gate->name}}</td>
                    <td>{{$gate->guards->count()}}</td>
                    <td>{{$gate->status}}</td>
                    <td>
                      <a href="{{route('gate.show',$gate->id)}}"   class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                       @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'security'))
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$gate->id}}" data-building_id="{{$gate->building_id}}" 
                      data-block_id="{{$gate->block_id}}" data-name="{{$gate->name}}" data-status="{{$gate->status}}"><i class="fa fa-edit"></i></button>
                      @if($gate->deleted_at)
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$gate->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                      @else
                      <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$gate->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Gate</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('gate.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error">
            @if($errors->any())
              <div class="alert alert-danger">
                {{ $errors->first() }}
              </div>
              <script>
                $(document).ready(function(){
                  $('#addModal').modal('show');
                });
              </script>
            @endif
          </div>
          
          <!--<div class="form-group">-->
          <!--  <label for="name" class="col-form-label">Building:</label>-->
          <!--  <select name="building_id" id="building_id" class="form-control" required>-->
          <!--      <option value="{{$building->id}}">{{$building->name}}</option>-->
          <!--  </select>-->
          <!--</div>-->
          
          <div class="form-group">
            <label for="name" class="col-form-label">Block:</label>
            <select name="block_id" id="block_id" class="form-control" required>
                @forelse($building->blocks()->where('status','Active')->get() as $block)
                <option value="{{$block->id}}">{{$block->name}}</option>
                @empty
                @endforelse
            </select>
          </div>
          
          <div class="form-group">
            <label for="name" class="col-form-label">Gate Name:</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="Gate Name" required>
          </div>

          
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
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
    
    // Fix DataTables search functionality after initialization
    setTimeout(function() {
        // Remove input validation from DataTables search input
        $('.dataTables_filter input').each(function() {
            var $input = $(this);
            // Remove all event handlers that might interfere
            $input.off();
            // Remove attributes that might limit input
            $input.removeAttr('maxlength').removeAttr('pattern');
            
            console.log('DataTables search input cleaned');
        });
    }, 500);
    
    // Prevent multiple form submissions
    var isSubmitting = false;
    
    $('.add-form').on('submit', function(e) {
      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');
      
      // Prevent multiple submissions
      if (isSubmitting) {
        e.preventDefault();
        return false;
      }
      
      // Mark as submitting
      isSubmitting = true;
      
      // Disable submit button and show loading state
      $submitBtn.prop('disabled', true);
      var originalText = $submitBtn.text();
      $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
      
      // Re-enable after 5 seconds as fallback (in case of network issues)
      setTimeout(function() {
        isSubmitting = false;
        $submitBtn.prop('disabled', false);
        $submitBtn.text(originalText);
      }, 5000);
    });
    
    // Reset submission flag when modal is closed
    $('#addModal').on('hidden.bs.modal', function() {
      isSubmitting = false;
      var $submitBtn = $(this).find('button[type="submit"]');
      $submitBtn.prop('disabled', false);
      $submitBtn.text('Save');
    });
    
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
      var $deleteBtn = $(this);
      
      // Prevent multiple delete requests
      if ($deleteBtn.prop('disabled')) {
        return false;
      }
      
      // Disable button and show loading state
      $deleteBtn.prop('disabled', true);
      var originalText = $deleteBtn.text();
      $deleteBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
      
      var url = "{{route('gate.destroy','')}}";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'action':action},
        success: function(data)
        {
          window.location.reload();
        },
        error: function() {
          // Re-enable button on error
          $deleteBtn.prop('disabled', false);
          $deleteBtn.text(originalText);
          alert('An error occurred. Please try again.');
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#name').val(button.data('name'));
      $('#building_id').val(button.data('building_id'));
      $('#block_id').val(button.data('block_id'));
      $('#status').val(button.data('status'));
      $('.modal-title').text('Add New Gate');
      if(edit_id){
          $('.modal-title').text('Update Gate');
      }
      
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-gate-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

    // Initialize DataTable with proper checks and delay
    setTimeout(function() {
      // Destroy any existing DataTable instance but preserve the HTML
      if ($.fn.DataTable.isDataTable('#gatesTable')) {
        $('#gatesTable').DataTable().destroy();
      }
      
      var table = $('#gatesTable').DataTable({
      "responsive": true,
      "lengthChange": false,
      "autoWidth": false,
      "searching": true,
      "ordering": true,
      "info": true,
      "paging": true,
      "pageLength": 25,
      "dom": 'Bfrtip',
      "buttons": [
        {
            extend: 'csvHtml5',
            exportOptions: {
                columns: ':visible'
            }
        },
        {
            extend: 'excelHtml5',
            exportOptions: {
                columns: ':visible'
            },
            customize: function (xlsx) {
                var sheet = xlsx.xl.worksheets['sheet1.xml'];
    
                // Make first row (headers) bold
                $('row c[r^="A1"], row c[r^="B1"], row c[r^="C1"]', sheet).attr('s', '2');
    
                // Set column width
                var cols = '<cols>';
                cols += '<col min="1" max="1" width="25"/>';
                cols += '<col min="2" max="2" width="20"/>';
                cols += '<col min="3" max="3" width="30"/>';
                cols += '</cols>';
                sheet.childNodes[0].insertBefore($.parseXML(cols).firstChild, sheet.getElementsByTagName('sheetData')[0]);
            }
        },
        {
            extend: 'pdfHtml5',
            exportOptions: {
                columns: ':visible'
            }
        },
        'colvis'
      ],
      "language": {
        "search": "Search:",
        "lengthMenu": "Show _MENU_ gates per page",
        "info": "Showing _START_ to _END_ of _TOTAL_ gates",
        "infoEmpty": "No gates found",
        "infoFiltered": "(filtered from _MAX_ total gates)",
        "paginate": {
          "first": "First",
          "last": "Last",
          "next": "Next",
          "previous": "Previous"
        }
      },
      "columnDefs": [
        { "orderable": false, "targets": [6] }, // Disable sorting for Action column
        { "searchable": false, "targets": [0, 6] } // Disable search for S No and Action columns
      ]
    });

    // Custom search function for multiple filters
    $.fn.dataTable.ext.search.push(
      function(settings, data, dataIndex) {
        var blockFilter = $('#blockFilter').val();
        var statusFilter = $('#statusFilter').val();
        
        var blockName = data[2]; // Block Name column (index 2)
        var status = data[5]; // Status column (index 5)
        
        // Check block filter
        var blockMatch = (blockFilter === '' || blockName === blockFilter);
        
        // Check status filter
        var statusMatch = (statusFilter === '' || status === statusFilter);
        
        // Return true only if all filters match
        return blockMatch && statusMatch;
      }
    );

      // Filter event handlers
      $('#blockFilter, #statusFilter').on('change', function() {
        table.draw();
      });

      // Enhanced search functionality for combined block and gate names
      setTimeout(function() {
        $('.dataTables_filter input').on('keyup', function(e) {
          var searchTerm = $(this).val().toLowerCase();
          var table = $('#gatesTable').DataTable();
          
          // Use DataTables built-in search with case-insensitive matching
          table.search(searchTerm, true, false, true).draw();
          
          console.log('Enhanced DataTables search for:', searchTerm);
        });
        
        console.log('Enhanced search functionality added to DataTables');
      }, 100);

      // Clear all filters
      $('#clearFilters').on('click', function() {
        $('#blockFilter').val('');
        $('#statusFilter').val('');
        table.search('').columns().search('').draw();
      });
      
    }, 500); // 500ms delay to ensure any auto-initialization completes first

  });
</script>
@endsection

@endsection

