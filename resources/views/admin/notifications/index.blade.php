@extends('layouts.admin')

@section('title')
    Notifications List
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
            <h1>Notifications</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Notifications</li>
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
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab">All Notifications</a></li>
                  <li class="nav-item"><a class="nav-link" href="#recieved" data-toggle="tab">Recieved Notifications</a></li>
                  <li class="nav-item"><a class="nav-link" href="#sent" data-toggle="tab">Sent Notifications </a></li>
                  <li class="nav-item"><a class="nav-link" href="#send" data-toggle="tab">Send New Notification</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="all">
                      <div class="col-12">
                        <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                              <thead>
                              <tr>
                                <th>S No</th>
                                <th>To</th>
                                <th>From</th>
                                <th>Title</th>
                                <th>Body</th>
                                <th>Status</th>
                                <th>Admin Read</th>
                                <th>Action</th>
                              </tr>
                              </thead>
                              <tbody class="test">
                                <?php $i = 0; ?>
                              @forelse($notifications as $notification)
                              <?php $i++; ?>
                              <tr>
                                <td>{{$notification->id}}</td>
                                <td>{{$notification->user->name}}</td>
                                <td><a href="{{url('show-'.$notification->from_user->role.'/'.$notification->from_id)}}">{{$notification->from_user->name}}</a></td>
                                <td>{{$notification->title}}</td>
                                <td>{{$notification->body}}</td>
                                <td>{{$notification->status}}</td>
                                <td>{{$notification->admin_read}}</td>
                                <td>
                                    <a href="{{route('notification.show',$notification->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                                </td>
                              </tr>
                              @empty
                              @endforelse
                              </tbody>
                            </table>
                        </div>
                        
                      </div>
                  </div>
                  <div class="tab-pane" id="recieved">
                      <div class="col-12">
                        <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                              <thead>
                              <tr>
                                <th>S No</th>
                                <th>To</th>
                                <th>From</th>
                                <th>Title</th>
                                <th>Body</th>
                                <th>Status</th>
                                <th>Admin Read</th>
                                <th>Action</th>
                              </tr>
                              </thead>
                              <tbody class="test">
                                <?php $i = 0; ?>
                              @forelse($recieved_notifications as $notification)
                              <?php $i++; ?>
                              <tr>
                                <td>{{$i}}</td>
                                <td>{{$notification->user->name}}</td>
                                <td><a href="{{url('show-'.$notification->from_user->role.'/'.$notification->from_id)}}">{{$notification->from_user->name}}</a></td>
                                <td>{{$notification->title}}</td>
                                <td>{{$notification->body}}</td>
                                <td>{{$notification->status}}</td>
                                <td>{{$notification->admin_read}}</td>
                                <td>
                                    <a href="{{route('notification.show',$notification->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                                </td>
                              </tr>
                              @empty
                              @endforelse
                              </tbody>
                            </table>
                        </div>
                        
                      </div>
                  </div>
                  
                  <div class="tab-pane" id="sent">
                      <div class="col-12">
                        <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-striped table-responsive">
                              <thead>
                              <tr>
                                <th>S No</th>
                                <th>To</th>
                                <th>From</th>
                                <th>Title</th>
                                <th>Body</th>
                                <th>Status</th>
                                <th>Admin Read</th>
                                <th>Action</th>
                              </tr>
                              </thead>
                              <tbody class="test">
                                <?php $i = 0; ?>
                              @forelse($sent_notifications as $notification)
                              <?php $i++; ?>
                              <tr>
                                <td>{{$i}}</td>
                                <td><a href="{{url('show-'.$notification->user->role.'/'.$notification->user_id)}}">{{$notification->user->name}}</a></td>
                                <td>{{$notification->from_user->name}}</td>
                                <td>{{$notification->title}}</td>
                                <td>{{$notification->body}}</td>
                                <td>{{$notification->status}}</td>
                                <td>{{$notification->admin_read}}</td>
                                <td>
                                    <a href="{{route('notification.show',$notification->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                                </td>
                              </tr>
                              @empty
                              @endforelse
                              </tbody>
                            </table>
                        </div>
                        
                      </div>
                  </div>
                  
                  <div class="tab-pane" id="send">
                      <div class="col-12">
                        <form action="{{route('notification.store')}}" method="post">
                              @csrf
                          <div class="modal-body">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Body</label>
                                <textarea name="body" class="form-control" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Send To</label>
                                <select name="to" class="form-control" required>
                                    <option value="customer">Customer</option>
                                    <option value="vendor">Vendor</option>
                                </select>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <input type="submit" class="btn btn-primary" value="Send">
                          </div>
                        </form>
                      </div>
                  </div>
                  
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
    
    //
});
</script>

@endsection
@endsection