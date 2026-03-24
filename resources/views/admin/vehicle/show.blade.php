@extends('layouts.admin')

@section('title')
    Vehicle Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Visitor Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Vehicle Details</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <p>{{optional($vehicle->flat)->name}}</p>
                </div>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Vehicle No</b> <a class="float-right">{{$vehicle->vehicle_no}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Vehicle Type</b> <a class="float-right">{{$vehicle->vehicle_type}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Ownership</b> <a class="float-right">{{$vehicle->ownership}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">{{$vehicle->status}}</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#inouts" data-toggle="tab">Inouts</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  
                  <div class="active tab-pane" id="inouts">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Type</th>
                              <th>Time</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        @forelse($vehicle->inouts as $inout)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</th>
                          <td>{{$inout->type}}</td>
                          <td>{{$inout->created_at}}</th>
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
      </div>
    </section>

@section('script')
<script>
  $(document).ready(function(){
      var token = "{{ csrf_token() }}";

  });
</script>
@endsection

@endsection
