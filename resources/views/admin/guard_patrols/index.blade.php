@extends('layouts.admin')

@section('title')
    Patrol Logs
@endsection

@section('content')
    <!-- Content Header -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                @if(session()->has('error'))
                <div class="alert alert-danger">{{ session()->get('error') }}</div>
                @endif
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
            </div>
          <div class="col-sm-6">
            <h1>Patrol Logs</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Patrol Logs</li>
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
            <!-- Filter Card -->
            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-filter"></i> Filters</h3>
              </div>
              <form method="GET" action="{{ route('guard-patrol.index') }}" class="form-horizontal">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Guard:</label>
                        <select name="guard_user_id" class="form-control">
                          <option value="">-- All Guards --</option>
                          @foreach($guards as $id => $user)
                            <option value="{{ $id }}" {{ ($filters['guard_user_id'] ?? '') == $id ? 'selected' : '' }}>
                              {{ $user->name ?? ($user->first_name ?? '') . ' ' . ($user->last_name ?? '') }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Location:</label>
                        <select name="patrol_location_id" class="form-control">
                          <option value="">-- All Locations --</option>
                          @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ ($filters['patrol_location_id'] ?? '') == $location->id ? 'selected' : '' }}>
                              {{ $location->display_name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Shift:</label>
                        <select name="shift" class="form-control">
                          <option value="">-- All Shifts --</option>
                          @foreach($shifts as $s)
                            <option value="{{ $s->name }}" {{ ($filters['shift'] ?? '') == $s->name ? 'selected' : '' }}>{{ $s->name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Type:</label>
                        <select name="checkin_type" class="form-control">
                          <option value="">-- All Types --</option>
                          <option value="photo" {{ ($filters['checkin_type'] ?? '') == 'photo' ? 'selected' : '' }}>Photo</option>
                          <option value="qr" {{ ($filters['checkin_type'] ?? '') == 'qr' ? 'selected' : '' }}>QR</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-info">Filter</button>
                  <a href="{{ route('guard-patrol.index') }}" class="btn btn-secondary">Reset</a>
                </div>
              </form>
            </div>

            <!-- Results Card -->
            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-list"></i> Patrol Records (Total: {{ $patrols->total() }})</h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Guard Name</th>
                    <th>Location</th>
                    <th>Log Type</th>
                    <th>Shift</th>
                    <th>Check Method</th>
                    <th>Checked In At</th>
                    <th>Photo</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = $patrols->firstItem() - 1; ?>
                  @forelse($patrols as $patrol)
                  <?php $i++; ?>
                  <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $patrol->guardUser ? ($patrol->guardUser->name ?? ($patrol->guardUser->first_name ?? '') . ' ' . ($patrol->guardUser->last_name ?? '')) : 'N/A' }}</td>
                    <td>
                      {{ $patrol->patrolLocation ? $patrol->patrolLocation->name : 'N/A' }}
                      @if($patrol->patrolLocation && $patrol->patrolLocation->gate)
                        <br><small class="text-muted">Route: {{ $patrol->patrolLocation->gate->name }}</small>
                      @endif
                    </td>
                    <td>
                      @if(($patrol->log_type ?? '') == 'task')
                        <span class="badge badge-info">Task</span>
                      @else
                        <span class="badge badge-secondary">General</span>
                      @endif
                    </td>
                    <td>{{ $patrol->shift ?? 'N/A' }}</td>
                    <td>
                      @if($patrol->checkin_type == 'photo')
                        <span class="badge badge-primary">Photo</span>
                      @else
                        <span class="badge badge-success">QR</span>
                      @endif
                    </td>
                    <td>{{ $patrol->checked_in_at ? $patrol->checked_in_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                    <td>
                      @if($patrol->photo_url)
                        <a href="{{ asset('images/'.$patrol->photo_url) }}" target="_blank">
                          <img src="{{ asset('images/'.$patrol->photo_url) }}" style="width:50px;height:50px;object-fit:cover;">
                        </a>
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr><td colspan="7" class="text-center">No patrol records found</td></tr>
                  @endforelse
                  </tbody>
                </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer clearfix">
                  {{ $patrols->links() }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

@endsection
