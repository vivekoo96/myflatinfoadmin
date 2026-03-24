@extends('layouts.admin')

@section('title')
    Notifications Details
@endsection

@section('content')
<style>
    .right{float:right !important;}
</style>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Notification Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Notification Details</li>
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
            <div class="card">
              <div class="card-header p-2">
                    Notification Details <a href="{{route('notification.index')}}" class="btn btn-sm btn-primary right">Back</a>
              </div><!-- /.card-header -->
              
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-bordered">
                      <tbody>
                          <tr>
                              <th>From</th>
                              <td>{{$notification->from_user->name}}</td>
                              <th>To</th>
                              <td>{{$notification->user->name}}</td>
                          </tr>
                          <tr>
                              <th>Title</th>
                              <td colspan="3">{{$notification->title}}</td>
                          </tr>
                          <tr>
                              <th>Body</th>
                              <td colspan="3">{{$notification->body}}</td>
                          </tr>
                          <tr>
                              <th>Status</th>
                              <td>{{$notification->status}}</td>
                              <th>Admin Read</th>
                              <td>{{$notification->admin_read}}</td>
                          </tr>
                          <tr>
                              <th>Time</th>
                              <td>{{$notification->created_at}}</td>
                          </tr>
                      </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
             
@endsection