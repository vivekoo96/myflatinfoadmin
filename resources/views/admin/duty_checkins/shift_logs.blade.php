@extends('layouts.admin')

@section('title')
    Shift Logs
@endsection

@section('content')
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
            <h1>Shift Logs</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Shift Logs</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <!-- Tabs -->
            <div class="card card-primary card-outline card-outline-tabs">
              <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.index') }}">Duty Patrol Logs</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" href="{{ route('duty-checkin.shift-logs') }}">Shift Logs</a>
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
                  <!-- Filter Card -->
                  <div class="mb-4">
                    <form action="{{ route('duty-checkin.shift-logs') }}" method="get" class="form-inline">
                      <div class="form-group mr-2 mb-2">
                        <label for="guard_filter" class="mr-2">Guard:</label>
                        <select id="guard_filter" name="guard_user_id" class="form-control">
                          <option value="">All Guards</option>
                          @foreach($guards as $guard)
                          <option value="{{ $guard->id }}" {{ request('guard_user_id') == $guard->id ? 'selected' : '' }}>
                            {{ $guard->name ?? ($guard->first_name ?? '') . ' ' . ($guard->last_name ?? '') }}
                          </option>
                          @endforeach
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="gate_filter" class="mr-2">Gate:</label>
                        <select id="gate_filter" name="gate_id" class="form-control">
                          <option value="">All Gates</option>
                          @foreach($gates as $gate)
                          <option value="{{ $gate->id }}" {{ request('gate_id') == $gate->id ? 'selected' : '' }}>
                            {{ $gate->name }}
                          </option>
                          @endforeach
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="shift_filter" class="mr-2">Shift:</label>
                        <select id="shift_filter" name="building_shift_id" class="form-control">
                          <option value="">All Shifts</option>
                          @foreach($shifts as $shift)
                          <option value="{{ $shift->id }}" {{ request('building_shift_id') == $shift->id ? 'selected' : '' }}>
                            {{ $shift->name }}
                          </option>
                          @endforeach
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="status_filter" class="mr-2">Status:</label>
                        <select id="status_filter" name="status" class="form-control">
                          <option value="">All Status</option>
                          <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                          <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                          <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                          <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                          <option value="handover_pending" {{ request('status') == 'handover_pending' ? 'selected' : '' }}>Handover Pending</option>
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="date_filter" class="mr-2">Date:</label>
                        <input type="date" id="date_filter" name="date" class="form-control" value="{{ request('date') }}">
                      </div>

                      <button type="submit" class="btn btn-success mb-2">Filter</button>
                      <a href="{{ route('duty-checkin.shift-logs') }}" class="btn btn-secondary mb-2 ml-2">Clear</a>
                    </form>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th>Date</th>
                        <th>Guard</th>
                        <th>Gate</th>
                        <th>Shift</th>
                        <th>Check-in</th>
                        <th>Check-out / Handover</th>
                        <th>Status</th>
                      </tr>
                      </thead>
                      <tbody>
                      @forelse($logs as $log)
                      <tr>
                        <td>{{ $log->shift_date->format('Y-m-d') }}</td>
                        <td>{{ $log->guardUser->name ?? ($log->guardUser->first_name ?? '') . ' ' . ($log->guardUser->last_name ?? '') }}</td>
                        <td>{{ $log->gate->name ?? 'N/A' }}</td>
                        <td>{{ $log->buildingShift->name ?? 'N/A' }}<br><small>{{ $log->buildingShift->start_time ?? '' }} - {{ $log->buildingShift->end_time ?? '' }}</small></td>
                        <td>
                          {{ $log->checked_in_at ? $log->checked_in_at->format('H:i:s') : '-' }}
                          @if($log->late_minutes > 0)
                              <br><small class="text-danger">{{ $log->late_minutes }}m late</small>
                          @endif
                        </td>
                        <td>
                          {{ $log->checked_out_at ? $log->checked_out_at->format('H:i:s') : '-' }}
                          @if($log->handoverConfirmedBy)
                              <br><small class="text-muted">Confirmed by: {{ $log->handoverConfirmedBy->name ?? $log->handoverConfirmedBy->first_name }}</small>
                          @endif
                        </td>
                        <td>
                          <span class="badge badge-{{ $log->status_badge }}">{{ ucfirst(str_replace('_', ' ', $log->status)) }}</span>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="7" class="text-center">No shift records found</td>
                      </tr>
                      @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">
                    {{ $logs->appends(request()->query())->links() }}
                  </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>
@endsection
