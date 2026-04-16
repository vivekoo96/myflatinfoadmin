@extends('layouts.admin')

@section('title')
    Activity Detail
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{$activity->title}}</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item"><a href="{{route('activity.index')}}">Community Activities</a></li>
          <li class="breadcrumb-item active">{{$activity->title}}</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <!-- Activity Details -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Activity Details</h3>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <p><strong>Creator:</strong> {{$activity->creator->first_name}} {{$activity->creator->last_name}}</p>
                <p><strong>Email:</strong> {{$activity->creator->email}}</p>
                <p><strong>Status:</strong>
                  @if($activity->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($activity->status === 'approved')
                    <span class="badge badge-success">Approved</span>
                  @else
                    <span class="badge badge-danger">Rejected</span>
                  @endif
                </p>
              </div>
              <div class="col-md-6">
                <p><strong>Type:</strong> <span class="badge badge-info">{{ucfirst($activity->response_type)}}</span></p>
                <p><strong>Post Type:</strong> <span class="badge badge-warning">{{ucfirst($activity->post_type)}}</span></p>
                @if($activity->post_type === 'slot')
                  <p><strong>Slots:</strong> {{$activity->filledSlotsCount()}}/{{$activity->max_slots}}</p>
                @endif
              </div>
            </div>

            <hr>

            <div class="row mb-3">
              <div class="col-md-6">
                <p><strong>Activity Date & Time:</strong><br>{{$activity->activity_datetime->format('d M Y, h:i A')}}</p>
              </div>
              <div class="col-md-6">
                <p><strong>Created At:</strong><br>{{$activity->created_at->format('d M Y, h:i A')}}</p>
              </div>
            </div>

            <hr>

            <p><strong>Location:</strong></p>
            <p>{{$activity->location ?: 'No location specified'}}</p>

            <hr>

            <p><strong>Description:</strong></p>
            <p>{{$activity->description ?: 'No description provided'}}</p>
          </div>
        </div>
      </div>

      <!-- Actions & Responses -->
      <div class="col-md-4">
        <!-- Action Buttons -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Actions</h3>
          </div>
          <div class="card-body">
            @if($activity->status === 'pending')
              <form action="{{route('activity.approve', $activity->id)}}" method="POST" class="mb-2">
                @csrf
                <button type="submit" class="btn btn-block btn-success">
                  <i class="fas fa-check"></i> Approve Activity
                </button>
              </form>
              <form action="{{route('activity.reject', $activity->id)}}" method="POST" class="mb-2">
                @csrf
                <button type="submit" class="btn btn-block btn-warning">
                  <i class="fas fa-times"></i> Reject Activity
                </button>
              </form>
            @endif
            <form action="{{route('activity.delete', $activity->id)}}" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-block btn-danger" onclick="return confirm('Delete this activity?')">
                <i class="fas fa-trash"></i> Delete Activity
              </button>
            </form>
            <a href="{{route('activity.index')}}" class="btn btn-block btn-secondary mt-2">
              <i class="fas fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>

        <!-- Responses Summary -->
        <div class="card mt-3">
          <div class="card-header">
            <h3 class="card-title">Response Summary</h3>
          </div>
          <div class="card-body">
            <p><strong>Total Responses:</strong> {{$activity->responses->count()}}</p>
            @if($activity->responses->count() > 0)
              <div class="mb-3">
                @php
                  $responseOptions = [
                    'simple' => ['interested', 'not_interested'],
                    'detailed' => ['attending', 'maybe', 'not_attending'],
                  ];
                  $options = $responseOptions[$activity->response_type] ?? [];
                @endphp
                @foreach($options as $option)
                  @php
                    $count = $activity->responses->where('response', $option)->count();
                    $percentage = $activity->responses->count() > 0 ? round(($count / $activity->responses->count()) * 100, 1) : 0;
                  @endphp
                  <div class="mb-2">
                    <small><strong>{{ucfirst(str_replace('_', ' ', $option))}}:</strong> {{$count}} ({{$percentage}}%)</small>
                    <div class="progress" style="height: 5px;">
                      <div class="progress-bar" style="width: {{$percentage}}%;"></div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted"><small>No responses yet</small></p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
