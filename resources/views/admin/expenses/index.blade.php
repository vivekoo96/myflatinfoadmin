@extends('layouts.admin')

@section('title')
    Society Funds
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Society Funds</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Society Funds</li>
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
                    <a href="{{url('society-fund/expenses')}}" class="">Expenses</a>
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
                    <a href="{{url('society-fund/reciepts')}}" class="" style="color:black">Reciepts</a>
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
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Expense</button>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Bill/Reciept</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Model</th>
                    <th>Type</th>
                    <th>Date</th>
                    <!-- <th>Created at</th> -->
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->expenses as $expense)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td><img src="{{$expense->image}}" style="width:40px;"></td>
                    <td>{{$expense->reason}}</td>
                    <td>{{$expense->amount}}</td>
                    <td>{{$expense->model}}</td>
                    <td>{{$expense->payment_type}}</td>
                    <td>{{$expense->date}}</td>
                    <!-- <td>{{$expense->created_at}}</td> -->
                    <td>
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$expense->id}}" data-reason="{{$expense->reason}}" data-amount="{{$expense->amount}}"   
                      data-image="{{$expense->image}}" data-date="{{$expense->date}}"><i class="fa fa-edit"></i></button>
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
            <label for="name" class="col-form-label">Type:</label>
            <select name="model" id="model" class="form-control" id="model" required>
                <option value="Maintenance">Maintenance</option>
                <option value="Event">Event</option>
                <option value="Corpus">Corpus</option>
                <option value="Booking">Booking</option>
                <option value="Essential">Essential</option>
            </select>
          </div>
          <div class="model-id"></div>

          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                <option value="Inhand">From Inhand</option>
                <option value="InBank">From InBank</option>
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
            <label for="code" class="col-form-label">Bill Image:<image src="" id="image2" style="width:40px;"></image></label>
            <input type="file" name="image" class="form-control" id="image" accept="image/*">
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
      $('#reason').val(button.data('reason'));
      $('#amount').val(button.data('amount'));
      $('#date').val(button.data('date'));
      $('#image2').attr('src',button.data('image'));
      $('.modal-title').text('Add New Expense');
      // $('#image').attr('required',true);
      if(edit_id){
          // $('#image').attr('required',false);
          $('.modal-title').text('Update Expense');
      }
    });

    $(document).on('change','#model',function(){
      var model = $(this).val();
      $('.model-id').html('');
      if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "{{url('/get-model-data')}}",
          type: "post",
          data : {'_token':token,'model':model},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
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