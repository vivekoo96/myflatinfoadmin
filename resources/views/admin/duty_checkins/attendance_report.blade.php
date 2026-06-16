@extends('layouts.admin')

@section('title')
    Attendance Report
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
            <h1>Attendance Report</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Attendance Report</li>
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
                    <a class="nav-link" href="{{ route('duty-checkin.shift-logs') }}">Shift Logs</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.handovers') }}">Handovers</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link active" href="{{ route('duty-checkin.attendance-report') }}">Attendance Report</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                  <!-- Filter Card -->
                  <div class="mb-4">
                    <form action="{{ route('duty-checkin.attendance-report') }}" method="get" class="form-inline">
                      <div class="form-group mr-2 mb-2">
                        <label for="month_filter" class="mr-2">Month:</label>
                        <select id="month_filter" name="month" class="form-control">
                          @for($m = 1; $m <= 12; $m++)
                          <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                          </option>
                          @endfor
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="year_filter" class="mr-2">Year:</label>
                        <select id="year_filter" name="year" class="form-control">
                          @for($y = date('Y') - 2; $y <= date('Y'); $y++)
                          <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>
                            {{ $y }}
                          </option>
                          @endfor
                        </select>
                      </div>

                      <button type="submit" class="btn btn-success mb-2">Filter</button>
                    </form>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th>Guard</th>
                        <th>Present Shifts</th>
                        <th>Late Shifts</th>
                        <th>Absent Shifts</th>
                        <th>Total Late Minutes</th>
                      </tr>
                      </thead>
                      <tbody>
                      @forelse($summary as $guardId => $data)
                      <tr>
                        <td>{{ $data['name'] }}</td>
                        <td><span class="badge badge-success">{{ $data['present'] }}</span></td>
                        <td>
                          @if($data['late'] > 0)
                              <span class="badge badge-warning">{{ $data['late'] }}</span>
                          @else
                              <span class="badge badge-secondary">0</span>
                          @endif
                        </td>
                        <td>
                          @if($data['absent'] > 0)
                              <span class="badge badge-danger">{{ $data['absent'] }}</span>
                          @else
                              <span class="badge badge-secondary">0</span>
                          @endif
                        </td>
                        <td>
                          @if($data['total_late_minutes'] > 0)
                              <strong class="text-danger">{{ $data['total_late_minutes'] }} min</strong>
                          @else
                              <span class="text-muted">-</span>
                          @endif
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="5" class="text-center">No attendance data for this month</td>
                      </tr>
                      @endforelse
                      </tbody>
                    </table>
                  </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </section>
@endsection
