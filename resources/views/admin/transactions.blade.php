@extends('layouts.admin')

@section('title')
    Transactions
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Transactions</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Transactions</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
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
                <div class="card-body">
                  <form method="GET" action="{{ url('transactions') }}">
                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="model" class="col-form-label">Type:</label>
                        <select name="model" id="model" class="form-control" required>
                            <option value="All" {{ request('model') == 'All' ? 'selected' : ''}}>All</option>
                            <option value="Maintenance" {{ request('model') == 'Maintenance' ? 'selected' : ''}}>Maintenance</option>
                            <option value="Event" {{ request('model') == 'Event' ? 'selected' : ''}}>Event</option>
                            <option value="Corpus" {{ request('model') == 'Corpus' ? 'selected' : ''}}>Corpus</option>
                            <option value="Facility" {{ request('model') == 'Facility' ? 'selected' : ''}}>Facility</option>
                            <option value="Essential" {{ request('model') == 'Essential' ? 'selected' : ''}}>Essential</option>
                        </select>
                      </div>
                      <div class="form-group col-md-6">
                        <div class="model-id"></div>
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}" max="{{ \Carbon\Carbon::now()->toDateString() }}">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}" max="{{ \Carbon\Carbon::now()->toDateString() }}">
                        </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-3">
                        <label>&nbsp;</label>
                        <a href="{{ url('account/statement/income-and-expenditure') }}" class="btn btn-secondary btn-block mt-2">Reset</a>
                      </div>
                      <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Filter</button>
                      </div>
                    </div>
                    </form>
                </div>
            </div>



            <div class="card">
              <div class="card-header">
                
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <!-- <th>S No</th> -->
                    <th>Date</th>
                    <th>Order Id</th>
                    <th>Model</th>
                    <th>Model Id</th>
                    <th>Type</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Desc</th>
                    <th>Reciept</th>
                    <th>Status</th>
                  </tr>
                  </thead>
                  <tbody>
                      
                    
                    <?php $i = 0; ?>
                  @forelse($transactions as $transaction)
                  <?php $i++; ?>
                  <tr>
                    <!-- <td>{{$i}}</td> -->
                    <td>{{ $transaction->date}}</td>
                    <td>{{$transaction->order_id}}</td>
                    <td>{{$transaction->model}}</td>
                    <td>{{$transaction->model_id}}</td>
                    <td>{{$transaction->type}}</td>
                    <td>{{$transaction->payment_type}}</td>
                    <td>{{$transaction->amount}}</td>
                    <td>{{$transaction->desc}}</td>
                    <td>{{$transaction->reciept_no}}</td>
                    <td>{{$transaction->status}}</td>
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



@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    var model = "{{ request('model') }}";
    var model_id = "{{ request('model_id') }}";
    
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

    if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "{{url('/get-model-data')}}",
          type: "post",
          data : {'_token':token,'model':model,'model_id':model_id},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      }

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