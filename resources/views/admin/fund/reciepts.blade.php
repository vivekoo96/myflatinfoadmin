@extends('layouts.admin')

@section('title')
    Reciepts
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Reciepts</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Reciepts</li>
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
                    <a href="{{url('society-fund/essential')}}" class="" style="color:black">Essential Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/event')}}" class="" style="color:black">Event Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/corpus')}}" class="" style="color:black">Corpus Funds</a>
                  </li>
                  <li class="list-group-item">
                    <a href="{{url('society-fund/reciepts')}}" class="">Reciepts</a>
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
              <div class="card-header">
                <!--<button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Expense</button>-->
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Description</th>
                    <th>Total</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Maintenance Funds</td>
                    <td>{{$maintenance}}</td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Event Funds</td>
                    <td>{{$event}}</td>
                  </tr>
                  <tr>
                    <td>3</td>
                    <td>Essential Funds</td>
                    <td>{{$essential}}</td>
                  </tr>
                  <tr>
                    <td>4</td>
                    <td>Corpus Fund</td>
                    <td>{{$corpus}}</td>
                  </tr>
                </tbody>
                <tfooter>
                  <tr>
                    <th colspan="2">Total Reciepts</th>
                    <th>{{$total_fund}}</th>
                  </tr>
                  <tr>
                    <th colspan="2">Total Expenses</th>
                    <th>{{$expense}}</th>
                  </tr>
                  <tr>
                    <th colspan="2">Remaining Fund</th>
                    <th>{{$remaining_fund}}</th>
                  </tr>
                </tfooter>
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
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->



<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Expense</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('expense.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <div class="form-group">
            <label for="name" class="col-form-label">Building:</label>
            <select name="building_id" id="building_id" class="form-control" id="building_id" required>
                <option value="{{$building->id}}">{{$building->name}}</option>
            </select>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Reason:</label>
            <textarea name="reason" id="reason" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Amount:</label>
            <input type="text" name="amount" class="form-control" id="amount" placeholder="Amount" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Bill Image:<image src="" id="image2"></image></label>
            <input type="file" name="image" class="form-control" id="image" accept="image/*" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">Date:</label>
            <input type="date" name="date" class="form-control" id="date" placeholder="Date" required>
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
      var url = "{{route('expense.destroy','')}}";
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
      $('#building_id').val(button.data('building_id'));
      $('#reason').val(button.data('reason'));
      $('#amount').val(button.data('amount'));
      $('#date').val(button.data('date'));
      $('#image2').attr('src',button.data('image'));
      $('.modal-title').text('Add New Expense');
      $('#image').attr('required',true);
      if(edit_id){
          $('#image').attr('required',false);
          $('.modal-title').text('Update Expense');
      }
    });

  });
</script>

<script src="{{asset('public/admin/plugins/summernote/summernote-bs4.min.js')}}"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>

@endsection


@endsection