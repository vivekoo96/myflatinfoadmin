@extends('layouts.admin')


@section('title')
    Essential List
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
            <h1>Essential List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Essentials</li>
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
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Essential</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Status</th>

                    <th>Action</th>
                   
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->essentials as $essential)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{$essential->reason}}</td>
                    <td>{{$essential->amount}}</td>
                    <td>{{$essential->status}}</td>
                    <td>
                       @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts') || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') )
                      <a href="{{route('essential.show',$essential->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      @endif
                      @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$essential->id}}" data-reason="{{$essential->reason}}"
                       data-building_id="{{$essential->building_id}}" data-amount="{{$essential->amount}}" data-due_date="{{$essential->due_date}}" data-late_fine_type="{{$essential->late_fine_type}}"
                       data-late_fine_value="{{$essential->late_fine_value}}" data-status="{{$essential->status}}" data-gst="{{$essential->gst}}" data-actived_by="{{$essential->actived_by}}"><i class="fa fa-edit"></i></button>
                      <!--@if($essential->deleted_at)-->
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$essential->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                      <!--@else-->
                      <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$essential->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                      <!--@endif-->
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Essential</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('essential.store')}}" method="post" class="add-form" enctype="multipart/form-data">
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
            <label for="code" class="col-form-label">Reason:</label>
            <textarea name="reason" class="form-control" id="reason" placeholder="Reason" required></textarea>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Amount:</label>
            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" min="0" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Due Date:</label>
            <input type="date" name="due_date" class="form-control" id="due_date" placeholder="Due Date" required>
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
            <input type="number" name="late_fine_value" class="form-control" id="late_fine_value" placeholder="Late Fine Value" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">GST(%):</label>
            <input type="number" name="gst" class="form-control" id="gst" placeholder="GST" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pending">Pending</option>
                <option value="Inactive">Inactive</option>
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
      var url = "{{route('essential.destroy','')}}";
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
      
      var activedBy = button.data('actived_by');
      
      $('.modal-title').text('Add New Essential');
      
      // Reset status options visibility
      $('#status option[value="Pending"]').show();
      
      if(edit_id){
          
          // Editing existing essential
          $('.modal-title').text('Update Essential');
          $('#edit-id').val(button.data('id'));
          $('#reason').val(button.data('reason'));
          $('#amount').val(button.data('amount'));
          $('#due_date').val(button.data('due_date'));
          $('#late_fine_type').val(button.data('late_fine_type'));
          $('#late_fine_value').val(button.data('late_fine_value'));
          $('#gst').val(button.data('gst'));
          $('#status').val(button.data('status'));
          
          // Hide Pending option when editing
          $('#status option[value="Pending"]').hide();
          
          // If editing and status is Active, make other fields readonly and preserve values
          var st = $('#status').val();
          if (st === 'Active' || st === 'Inactive') {
              var $form = $('#addModal').find('form');

              // Make text inputs and textareas readonly (so values are submitted)
              $form.find('input,textarea').not('input[type=hidden]').each(function(){
                if ($(this).attr('type') === 'file') return; // leave file inputs alone
                $(this).prop('readonly', true);
              });

              // Disable all selects except #status and preserve their values via hidden inputs
              $form.find('select').not('#status').each(function(){
                var $sel = $(this);
                var name = $sel.attr('name');
                var val = $sel.val();
                if (name) {
                  $form.find('input.helper-'+name).remove();
                  var $hidden = $('<input>').attr({type:'hidden', name: name, value: val}).addClass('helper-'+name);
                  $form.append($hidden);
                }
                $sel.prop('disabled', true);
              });

              // keep modal close enabled and Save enabled so status can be changed
              $('#addModal').find('button.close, .modal-footer .btn-secondary').prop('disabled', false);
              $('#save-button').prop('disabled', false);
          } else {
              // ensure enabled when not Active
              $('#addModal').find('input,textarea,select').not('input[type=hidden]').prop('disabled', false).prop('readonly', false);
              // remove any helper hidden inputs
              $('#addModal').find('input').filter(function(){ return $(this).attr('class') && $(this).attr('class').indexOf('helper-') !== -1; }).remove();
          }
        } else {
        // Adding new essential - clear all fields
        $('#edit-id').val('');
        $('#reason').val('');
        $('#amount').val('');
        $('#due_date').val('');
        $('#late_fine_type').val('Percentage');
        $('#late_fine_value').val('');
        $('#gst').val('');
        $('#status').val('Pending');
      }
      // When modal is hidden, re-enable all inputs to reset state and remove helper inputs
      $('#addModal').on('hidden.bs.modal.resetFields', function () {
        var $form = $(this).find('form');
        $form.find('input,textarea,select').not('input[type=hidden]').prop('disabled', false).prop('readonly', false);
        // remove helper hidden inputs added for disabled selects
        $form.find('input').filter(function(){ return $(this).attr('class') && $(this).attr('class').indexOf('helper-') !== -1; }).remove();
        $('#save-button').prop('disabled', false);
        $(this).off('hidden.bs.modal.resetFields');
      });
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-essential-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

    // Prevent multiple form submissions
    $('.add-form').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('#save-button');
        
        // Check if form is already being submitted
        if ($form.data('submitted') === true) {
            e.preventDefault();
            return false;
        }
        
        // Mark form as submitted and disable button
        $form.data('submitted', true);
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        // Re-enable form after 3 seconds (in case of errors)
        setTimeout(function() {
            $form.data('submitted', false);
            $submitBtn.prop('disabled', false);
            $submitBtn.html('Save');
        }, 3000);
    });

    // Reset form submission state when modal is opened (additional handler)
    $('#addModal').on('shown.bs.modal', function() {
        $('.add-form').data('submitted', false);
        $('#save-button').prop('disabled', false);
        $('#save-button').html('Save');
        $('.error').html(''); // Clear any previous errors
    });

  });
</script>
@endsection

@endsection


