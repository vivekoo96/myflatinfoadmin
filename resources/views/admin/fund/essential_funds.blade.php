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
              <li class="breadcrumb-item active">Essential</li>
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
                <h3 class="profile-username text-center">{{$building->name}}</h3>
                </div>
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <a href="{{url('society-fund/expenses')}}" class="" style="color:black">Expenses</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/maintenance')}}" class="" style="color:black">Maintenance Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/essential')}}" class="">Essential Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/event')}}" class="" style="color:black">Event Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/corpus')}}" class="" style="color:black">Corpus Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/reciepts')}}" class="" style="color:black">Reciepts</a>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          
          
          
          <div class="col-9">

            <div class="card">
              <div class="card-header">
                <!--<button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Maintenance</button>-->
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <tr>
                        <th>S No</th>
                        <th>Reason</th>
                        <th>Amount</th>
                        <th>Paid Amount</th>
                        <th>Dues Amount</th>
                        <th>Total</th>
                    </tr>
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
                    <td>{{ optional($essential->payments)->sum('paid_amount') ?? 0 }}</td>
                    <td>{{ optional($essential->payments)->sum('dues_amount') ?? 0 }}</td>
                    <td>{{ optional($essential->payments)->sum('paid_amount') ?? 0 + optional($essential->payments)->sum('dues_amount') ?? 0 }}</td>

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
          <div class="form-group">
            <label for="name" class="col-form-label">Building:</label>
            <select name="building_id" id="building_id" class="form-control" required>
                <option value="{{$building->id}}">{{$building->name}}</option>
            </select>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">From Date:</label>
            <input type="date" name="from_date" class="form-control" id="from_date" placeholder="From Date" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">To Date:</label>
            <input type="date" name="to_date" class="form-control" id="to_date" placeholder="To Date" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Occupied Maintenance Fee:</label>
            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Vacant Maintenance Fee:</label>
            <input type="number" name="vacant_amount" class="form-control" id="vacant_amount" placeholder="Amount" required>
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
            <label for="name" class="col-form-label">Payment Status:</label>
            <select name="status" id="status" class="form-control" required>
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
      $('#edit-id').val(button.data('id'));
      $('#from_date').val(button.data('from_date'));
      $('#to_date').val(button.data('to_date'));
      $('#amount').val(button.data('amount'));
      $('#due_date').val(button.data('due_date'));
      $('#late_fine_type').val(button.data('late_fine_type'));
      $('#late_fine_value').val(button.data('late_fine_value'));
      $('#status').val(button.data('status'));
    //   $('#building_id').val(button.data('building_id'));
      $('.modal-title').text('Add New Maintenance');
      if(edit_id){
          $('.modal-title').text('Update Maintenance');
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

  });
</script>
@endsection

@endsection


