@extends('layouts.admin')

@section('title')
    Community Activities
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Community Activities</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{route('admin')}}">Home</a></li>
          <li class="breadcrumb-item active">Community Activities</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Activity Approval Management</h3>
          </div>
          <div class="card-body">
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session()->get('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session()->get('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-3">
              <div class="col-md-8">
                <form method="GET" action="{{route('activity.index')}}" class="form-inline">
                  <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by title or description" value="{{$search}}">
                  </div>
                  <div class="form-group mr-2">
                    <select name="status" class="form-control">
                      <option value="pending" {{$status == 'pending' ? 'selected' : ''}}>Pending</option>
                      <option value="approved" {{$status == 'approved' ? 'selected' : ''}}>Approved</option>
                      <option value="rejected" {{$status == 'rejected' ? 'selected' : ''}}>Rejected</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary">Filter</button>
                </form>
              </div>
            </div>

            <!-- Activities Table -->
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Creator</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                    <th>Responses</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($activities as $activity)
                    <tr>
                      <td>{{$activity->id}}</td>
                      <td>{{$activity->title}}</td>
                      <td>
                        <small>{{$activity->creator->first_name}} {{$activity->creator->last_name}}</small><br>
                        <small class="text-muted">{{$activity->creator->email}}</small>
                      </td>
                      <td>
                        <span class="badge badge-info">{{ucfirst($activity->response_type)}}</span>
                        <span class="badge badge-warning">{{ucfirst($activity->post_type)}}</span>
                      </td>
                      <td>
                        <small>{{$activity->activity_datetime->format('d M Y, h:i A')}}</small>
                      </td>
                      <td>
                        <small>{{$activity->responses->count()}} response(s)</small>
                        @if($activity->post_type === 'slot')
                          <br><small class="text-muted">{{$activity->filledSlotsCount()}}/{{$activity->max_slots}} slots</small>
                        @endif
                      </td>
                      <td>
                        @if($activity->status === 'pending')
                          <span class="badge badge-warning">Pending</span>
                        @elseif($activity->status === 'approved')
                          <span class="badge badge-success">Approved</span>
                        @else
                          <span class="badge badge-danger">Rejected</span>
                        @endif
                      </td>
                      <td>
                        <a href="{{route('activity.show', $activity->id)}}" class="btn btn-sm btn-info" title="View">
                          <i class="fas fa-eye"></i>
                        </a>
                        @if($activity->status === 'pending')
                          <form action="{{route('activity.approve', $activity->id)}}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this activity?')">
                              <i class="fas fa-check"></i>
                            </button>
                          </form>
                          <form action="{{route('activity.reject', $activity->id)}}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning" title="Reject" onclick="return confirm('Reject this activity?')">
                              <i class="fas fa-times"></i>
                            </button>
                          </form>
                        @endif
                        <form action="{{route('activity.delete', $activity->id)}}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this activity?')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted">No activities found</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
              {{$activities->appends(request()->query())->links()}}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
