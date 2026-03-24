@extends('layouts.admin')


@section('title')
    Maintenance List
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
            <h1>Maintenance List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Maintenance</li>
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
                   @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
           
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Maintenance</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Maintenance Type</th>
                    <th>Occupied Amount</th>
                    <th>Vacant Amount</th>
                    <th>Due Date</th>
                    <th>Late Fine Type</th>
                    <th>Late Fine Value</th>
                    <th>GST(%)</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->maintenances as $maintenance)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$maintenance->from_date}}</td>
                    <td>{{$maintenance->to_date}}</td>
                    <td>{{$maintenance->maintenance_type}}</td>
                    <td>{{$maintenance->amount}}</td>
                    <td>{{$maintenance->vacant_amount}}</td>
                    <td>{{$maintenance->due_date}}</td>
                    <td>{{$maintenance->late_fine_type}}</td>
                    <td>{{$maintenance->late_fine_value}}</td>
                    <td>{{$maintenance->gst}}</td>
                    <td>{{$maintenance->status}}</td>
                    <td>
                      <!--<a href="{{route('maintenance.show',$maintenance->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>-->
                       @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$maintenance->id}}" data-from_date="{{$maintenance->from_date}}" data-maintenance_type="{{$maintenance->maintenance_type}}" data-vacant_amount="{{$maintenance->vacant_amount}}"
                      data-to_date="{{$maintenance->to_date}}" data-building_id="{{$maintenance->building_id}}" data-amount="{{$maintenance->amount}}" data-status="{{$maintenance->status}}" data-gst="{{$maintenance->gst}}" 
                      data-due_date="{{$maintenance->due_date}}" data-late_fine_type="{{$maintenance->late_fine_type}}" data-late_fine_value="{{$maintenance->late_fine_value}}"><i class="fa fa-edit"></i></button>
                      @endif
                      <!--@if($maintenance->deleted_at)-->
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$maintenance->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                      <!--@else-->
                      <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$maintenance->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                      <!--@endif-->
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Maintenance</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('maintenance.store')}}" method="post" class="add-form" enctype="multipart/form-data">
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
            <label for="code" class="col-form-label">From Date:</label>
            <input type="date" name="from_date" class="form-control" id="from_date" placeholder="From Date" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">To Date:</label>
            <input type="date" name="to_date" class="form-control" id="to_date" placeholder="To Date" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Maintenance Type:</label>
            <select name="maintenance_type" id="maintenance_type" class="form-control" required>
                <option value="Areawise">Areawise</option>
                <option value="Custom">Custom</option>
            </select>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Occupied Maintenance Fee:</label>
            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" min="0" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Vacant Maintenance Fee:</label>
            <input type="number" name="vacant_amount" class="form-control" id="vacant_amount" min="0" placeholder="Amount" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Due Date:</label>
            <input type="date" name="due_date" class="form-control" id="due_date" placeholder="Due Date"  required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Late Fine Type:</label>
            <select name="late_fine_type" id="late_fine_type" class="form-control" required>
                <option value="Percentage">Percentage</option>
                <option value="Daily">Daily</option>
                <option value="Fixed">Fixed</option>
            </select>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Late Fine Value:</label>
            <input type="number" name="late_fine_value" class="form-control" id="late_fine_value" min="0" placeholder="Late Fine Value" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">GST(%):</label>
            <input type="number" name="gst" class="form-control" id="gst" placeholder="GST" min="0" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pending" selected>Pending</option>
                <option value="Active">Active</option>
               
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
      var url = "{{route('maintenance.destroy','')}}";
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
      var status = button.data('status');
      var today = new Date().toISOString().split('T')[0];
      
      $('#edit-id').val(button.data('id'));
      $('#from_date').val(button.data('from_date'));
      $('#to_date').val(button.data('to_date'));
      $('#amount').val(button.data('amount'));
      $('#vacant_amount').val(button.data('vacant_amount'));
      $('#due_date').val(button.data('due_date'));
      $('#maintenance_type').val(button.data('maintenance_type'));
      $('#late_fine_type').val(button.data('late_fine_type'));
      $('#late_fine_value').val(button.data('late_fine_value'));
      $('#gst').val(button.data('gst'));
      $('#status').val(button.data('status'));
      
    //   $('#building_id').val(button.data('building_id'));
      $('.modal-title').text('Add  Maintenance');
      
      // Get all form fields
      var formFields = $('#from_date, #to_date, #amount, #vacant_amount, #due_date, #maintenance_type, #late_fine_type, #late_fine_value, #gst, #status');
      
      if(edit_id){
          $('.modal-title').text('Update Maintenance');
          // For editing, remove min date restriction to allow past dates
          // Remove all min attributes for editing
          $('#from_date').removeAttr('min');
          $('#to_date').removeAttr('min');
          $('#due_date').removeAttr('min');
          
          // If status is Active, disable all fields
          if(status === 'Active') {
              formFields.prop('disabled', true);
              $('#save-button').prop('disabled', true).addClass('disabled');
          } else {
              formFields.prop('disabled', false);
              $('#save-button').prop('disabled', false).removeClass('disabled');
          }
      } else {
          // For new records, also remove all min restrictions
          $('#from_date').removeAttr('min');
          $('#to_date').removeAttr('min');
          $('#due_date').removeAttr('min');
          // Clear form fields for new record
          $('#from_date').val('');
          $('#to_date').val('');
          $('#amount').val('');
          $('#vacant_amount').val('');
          $('#due_date').val('');
          $('#maintenance_type').val('Areawise');
          $('#late_fine_type').val('Percentage');
          $('#late_fine_value').val('');
          $('#gst').val('');
        //   $('#status').val('Inactive');
           $('#status').val('Pending');
           
           // Enable all fields for new record
           formFields.prop('disabled', false);
           $('#save-button').prop('disabled', false).removeClass('disabled');
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-maintenance-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

        // Date validation
        // Remove all date restrictions - allow any date selection
        $('#from_date, #to_date, #due_date').on('change', function() {
            // No restrictions - users can select any date
        });

  });
</script>
@endsection

@endsection

