@extends('layouts.admin')

@section('title')
    Previous Notifications
@endsection

@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Previous Notifications</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('notification.index') }}">Send Notification</a></li>
          <li class="breadcrumb-item active">Previous Notifications</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <div class="card" style="border-top: 3px solid #3C5795;">
      <div class="card-header" style="background-color: #3C5795; color: #fff;">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Previous Notifications</h3>
        <div class="card-tools">
          <a href="{{ route('notification.index') }}" class="btn btn-sm" style="background-color:#fff; color:#3C5795;">
            <i class="fas fa-paper-plane mr-1"></i> Send New
          </a>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="notifTable" class="table table-bordered table-striped mb-0" data-order='[[5,"desc"]]'>
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Description</th>
                <th>Image</th>
                <th>Sent To</th>
                <th>Sent At</th>
              </tr>
            </thead>
            <tbody>
              @forelse($sentNotifications as $i => $n)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $n->title }}</td>
                <td>{{ Str::limit($n->body, 80) }}</td>
                <td>
                  @if($n->image)
                    <a href="{{ asset('storage/' . $n->image) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                      <i class="fas fa-image"></i> View Image
                    </a>
                  @else
                    <span class="text-muted small">No image</span>
                  @endif
                </td>
                <td>
                  @php
                    $labels = [
                      'all_flat_users'   => '<span class="badge badge-primary">All Flat Users</span>',
                      'owners'           => '<span class="badge badge-info">Owners</span>',
                      'tenants'          => '<span class="badge badge-secondary">Tenants</span>',
                      'security'         => '<span class="badge badge-warning">Security</span>',
                      'issue_management' => '<span class="badge badge-dark">Issue Mgmt</span>',
                      'accounts'         => '<span class="badge badge-success">Accounts</span>',
                    ];
                    $roles = $n->target_roles ?? [];
                  @endphp
                  @foreach($roles as $role)
                    {!! $labels[$role] ?? '<span class="badge badge-light">'.$role.'</span>' !!}
                  @endforeach
                </td>
                <td>{{ $n->created_at->format('d M Y, h:i A') }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-3">No notifications sent yet.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

@endsection

