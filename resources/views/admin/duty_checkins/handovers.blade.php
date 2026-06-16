@extends('layouts.admin')

@section('title')
    Handovers
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
            <h1>Guard Handovers</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Handovers</li>
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
                    <a class="nav-link active" href="{{ route('duty-checkin.handovers') }}">Handovers</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="{{ route('duty-checkin.attendance-report') }}">Attendance Report</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                  <!-- Filter Card -->
                  <div class="mb-4">
                    <form action="{{ route('duty-checkin.handovers') }}" method="get" class="form-inline">
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
                          <option value="pending_incoming" {{ request('status') == 'pending_incoming' ? 'selected' : '' }}>Pending Incoming</option>
                          <option value="pending_outgoing" {{ request('status') == 'pending_outgoing' ? 'selected' : '' }}>Pending Outgoing</option>
                          <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                      </div>

                      <div class="form-group mr-2 mb-2">
                        <label for="date_filter" class="mr-2">Date:</label>
                        <input type="date" id="date_filter" name="date" class="form-control" value="{{ request('date') }}">
                      </div>

                      <button type="submit" class="btn btn-success mb-2">Filter</button>
                      <a href="{{ route('duty-checkin.handovers') }}" class="btn btn-secondary mb-2 ml-2">Clear</a>
                    </form>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                      <tr>
                        <th>Date</th>
                        <th>Gate</th>
                        <th>Shift</th>
                        <th>Outgoing Guard</th>
                        <th>Incoming Guard</th>
                        <th>Arrival / Confirmations</th>
                        <th>Status</th>
                      </tr>
                      </thead>
                      <tbody>
                      @forelse($handovers as $handover)
                      <tr>
                        <td>{{ $handover->shift_date->format('Y-m-d') }}</td>
                        <td>{{ $handover->gate->name ?? 'N/A' }}</td>
                        <td>{{ $handover->buildingShift->name ?? 'N/A' }}<br><small>{{ $handover->buildingShift->start_time ?? '' }} - {{ $handover->buildingShift->end_time ?? '' }}</small></td>
                        <td>{{ $handover->outgoingGuard->name ?? ($handover->outgoingGuard->first_name ?? 'N/A') }}</td>
                        <td>{{ $handover->incomingGuard->name ?? ($handover->incomingGuard->first_name ?? 'N/A') }}</td>
                        <td>
                          <strong>Arrived:</strong> {{ $handover->incoming_arrived_at ? $handover->incoming_arrived_at->format('H:i:s') : '-' }}
                          @if($handover->late_minutes > 0)
                              <span class="text-danger">({{ $handover->late_minutes }}m late)</span>
                          @endif
                          <br>
                          <strong>Incoming Conf:</strong> {{ $handover->incoming_confirmed_at ? $handover->incoming_confirmed_at->format('H:i:s') : '-' }}<br>
                          <strong>Outgoing Conf:</strong> {{ $handover->outgoing_confirmed_at ? $handover->outgoing_confirmed_at->format('H:i:s') : '-' }}
                        </td>
                        <td>
                          <span class="badge badge-{{ $handover->status_badge }}">{{ $handover->status_label }}</span>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="7" class="text-center">No handover records found</td>
                      </tr>
                      @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">
                    {{ $handovers->appends(request()->query())->links() }}
                  </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>
@endsection
