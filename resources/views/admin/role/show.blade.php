@extends('layouts.admin')

@section('title')
    Country Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Country Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Country Details</li>
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
                  
                </div>
                <h3 class="profile-username text-center">{{$country->name}}</h3>

                <p class="text-muted text-center">{{$country->code}}</p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Name</b> <a class="float-right">{{$country->name}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Code</b> <a class="float-right">{{$country->code}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Members</b> <a class="float-right">{{$country->users->count()}}</a>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#basic" data-toggle="tab">Users</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="basic">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <td>Name</td>
                              <td>Email</td>
                              <td>Phone</td>
                              <td>Gender</td>
                          </tr>
                      </thead>
                      <tbody>
                        @forelse($country->users as $user)
                        <tr>
                          <td>{{$user->name}}</th>
                          <td>{{$user->email}}</th>
                          <td>{{$user->phone}}</th>
                          <td>{{$user->gender}}</th>
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
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

@section('script')
<script>
    $(document).ready(function(){
        
    });
</script>
@endsection

@endsection
