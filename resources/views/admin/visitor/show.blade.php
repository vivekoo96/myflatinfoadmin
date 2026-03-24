@extends('layouts.admin')

@section('title')
    Visitor Details
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
              <li class="breadcrumb-item active">Visitor Details</li>
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
                 @if(!empty($visitor->head_photo))
                <img class="profile-user-img img-fluid img-circle"
                     src="{{ $visitor->head_photo }}"
                     alt="Issue picture">
            @else
                <span>No Image</span>
            @endif

                </div>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Members</b> <a class="float-right">{{$visitor->total_members}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">{{$visitor->status}}</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Basic Info</a></li>
                  <li class="nav-item"><a class="nav-link" href="#inouts" data-toggle="tab">Inouts</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="basic">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr style="display:none;">
                              <td></td>
                              <td></td>
                              <td></td>
                              <td></td>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Head Name</th>
                          <td>{{$visitor->head_name}}</td>
                          <td>Head Phone</th>
                          <td>{{$visitor->head_phone}}</td>
                        </tr>

                        <tr>
                          <td>Staying From</th>
                          <td>{{$visitor->stay_from}}</td>
                          <td>Staying To</th>
                          <td>{{$visitor->stay_to}}</td>
                        </tr>
                        <tr>
                          <td>Type</th>
                          <td>{{$visitor->type}}</td>
                          <td>Status</th>
                          <td>{{$visitor->status}}</td>
                        </tr>
                        <tr>
                          <td>Purpose</th>
                          <td colspan="3">{{$visitor->visiting_purpose}}</td>
                        </tr>
                        <tr>
                          <td>Created at</th>
                          <td>{{$visitor->created_at}}</td>
                          <td>Updated at</th>
                          <td>{{$visitor->updated_at}}</th>
                        </tr>
                        
                        <tr>
                        <td>Vehicles</td>
                        <td colspan="3">
                            @if($visitor->vehicles->isNotEmpty())
                                <ul class="mb-0 pl-3">
                                   
                                    @foreach($visitor->vehicles as $vehicle)
                                    
                                       <span class="d-block mb-1">
                                        <i class="fas fa-car text-secondary mr-2"></i>
                                        {{ $vehicle->vehicle_no }}
                                    </span>

                                    @endforeach
                                </ul>
                            @else
                                <span>No Vehicle</span>
                            @endif
                        </td>
                    </tr>

                        
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                  <div class="tab-pane" id="inouts">
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
                        @forelse($visitor->inouts as $inout)
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
