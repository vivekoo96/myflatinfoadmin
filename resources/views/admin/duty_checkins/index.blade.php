@extends('layouts.admin')

@section('title')
    Duty Check-Ins
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
            <h1>Duty Check-Ins</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Duty Check-Ins</li>
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

            <!-- Tabs -->
            <div class="card card-primary card-outline card-outline-tabs">
              <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" href="{{ route('duty-checkin.index') }}">Duty Patrol Logs</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.shift-logs') }}">Shift Logs</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.handovers') }}">Handovers</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.attendance-report') }}">Attendance Report</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">

            <!-- Settings Card -->
            @if(Auth::user()->role == 'BA')
            <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog"></i> Settings</h3>
              </div>
              <div class="card-body">
                <form action="{{ route('duty-checkin.interval') }}" method="post" class="form-inline">
                  @csrf
                  <div class="form-group mr-2">
                    <label for="interval">Check-In Interval (minutes):</label>
                    <input type="number" id="interval" name="duty_checkin_interval_minutes"
                           class="form-control mx-2" min="5" max="480"
                           value="{{ $building->duty_checkin_interval_minutes ?? 30 }}" required>
                  </div>
                  <button type="submit" class="btn btn-primary">Save</button>
                </form>
              </div>
            </div>
            @endif

            <!-- Filter Card -->
            <div class="card card-secondary card-outline">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filters</h3>
              </div>
              <div class="card-body">
                <form action="{{ route('duty-checkin.index') }}" method="get" class="form-inline">
                  <div class="form-group mr-2 mb-2">
                    <label for="guard_filter">Guard:</label>
                    <select id="guard_filter" name="guard_user_id" class="form-control mx-2">
                      <option value="">All Guards</option>
                      @foreach($guards as $guard)
                      <option value="{{ $guard->id }}" {{ request('guard_user_id') == $guard->id ? 'selected' : '' }}>
                        {{ $guard->name ?? ($guard->first_name ?? '') . ' ' . ($guard->last_name ?? '') }}
                      </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="form-group mr-2 mb-2">
                    <label for="gate_filter">Gate:</label>
                    <select id="gate_filter" name="gate_id" class="form-control mx-2">
                      <option value="">All Gates</option>
                      @foreach($gates as $gate)
                      <option value="{{ $gate->id }}" {{ request('gate_id') == $gate->id ? 'selected' : '' }}>
                        {{ $gate->name }}
                      </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="form-group mr-2 mb-2">
                    <label for="shift_filter">Shift:</label>
                    <select id="shift_filter" name="building_shift_id" class="form-control mx-2">
                      <option value="">All Shifts</option>
                      @foreach($shifts as $shift)
                      <option value="{{ $shift->id }}" {{ request('building_shift_id') == $shift->id ? 'selected' : '' }}>
                        {{ $shift->name }}
                      </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="form-group mr-2 mb-2">
                    <label for="status_filter">Status:</label>
                    <select id="status_filter" name="status" class="form-control mx-2">
                      <option value="">All Status</option>
                      <option value="on_time" {{ request('status') == 'on_time' ? 'selected' : '' }}>On Time</option>
                      <option value="delayed" {{ request('status') == 'delayed' ? 'selected' : '' }}>Delayed</option>
                      <option value="missed" {{ request('status') == 'missed' ? 'selected' : '' }}>Missed</option>
                    </select>
                  </div>

                  <div class="form-group mr-2 mb-2">
                    <label for="date_filter">Date:</label>
                    <input type="date" id="date_filter" name="date" class="form-control mx-2"
                           value="{{ request('date') }}">
                  </div>

                  <button type="submit" class="btn btn-success mb-2">Filter</button>
                  <a href="{{ route('duty-checkin.index') }}" class="btn btn-secondary mb-2 ml-2">Clear</a>
                </form>
              </div>
            </div>

            <!-- Results Card -->
            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="fas fa-history"></i> Check-In History</h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Guard Name</th>
                    <th>Gate</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Scheduled At</th>
                    <th>Checked In At</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                  @forelse($checkins as $checkin)
                  <?php $i++; ?>
                  <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $checkin->guardUser ? ($checkin->guardUser->name ?? ($checkin->guardUser->first_name ?? '') . ' ' . ($checkin->guardUser->last_name ?? '')) : 'N/A' }}</td>
                    <td>{{ $checkin->gate->name ?? 'N/A' }}</td>
                    <td>{{ $checkin->buildingShift->name ?? 'N/A' }}</td>
                    <td>
                      @if($checkin->status == 'on_time')
                        <span class="badge badge-success">On Time</span>
                      @elseif($checkin->status == 'delayed')
                        <span class="badge badge-warning">Delayed</span>
                      @else
                        <span class="badge badge-danger">Missed</span>
                      @endif
                    </td>
                    <td>{{ $checkin->scheduled_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $checkin->checked_in_at->format('Y-m-d H:i') }}</td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="7" class="text-center">No check-in records found</td>
                  </tr>
                  @endforelse
                  </tbody>
                </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                  {{ $checkins->links() }}
                </div>
              </div>
              </div> <!-- /.card-body (tabs) -->
            </div> <!-- /.card (tabs) -->
          </div>
        </div>
      </div>
    </section>
@endsection
