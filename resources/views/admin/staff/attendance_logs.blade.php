@extends('layouts.admin')

@section('title')
    Staff Attendance Logs
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><i class="fas fa-clipboard-list mr-2"></i>Staff Attendance Logs</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff</a></li>
          <li class="breadcrumb-item active">Attendance Logs</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- ─── FILTER FORM ────────────────────────────────────────────────── --}}
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Select Staff &amp; Date</h3>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('admin.staff.attendance-logs') }}" id="filterForm">
          <div class="row">

            {{-- Staff dropdown --}}
            <div class="col-md-5">
              <div class="form-group">
                <label for="staff_id"><strong>Staff Member</strong> <small class="text-muted">(all including inactive)</small></label>
                <select name="staff_id" id="staff_id" class="form-control select2" required>
                  <option value="">— Select Staff —</option>
                  @foreach($allStaff as $s)
                    <option value="{{ $s->id }}"
                      {{ $selectedStaffId == $s->id ? 'selected' : '' }}>
                      {{ $s->name }}
                      ({{ $s->staff_id }})
                      — {{ $s->type }}
                      @if($s->status === 'Inactive') [Inactive] @endif
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            {{-- Date picker --}}
            <div class="col-md-4">
              <div class="form-group">
                <label for="date"><strong>Date</strong></label>
                <input type="date" name="date" id="date" class="form-control"
                       value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}" required>
              </div>
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="form-group w-100">
                <button type="submit" class="btn btn-primary btn-block">
                  <i class="fas fa-search mr-1"></i> View Logs
                </button>
              </div>
            </div>

          </div>
        </form>
      </div>
    </div>

    {{-- ─── RESULTS ────────────────────────────────────────────────────── --}}
    @if($staff)

      {{-- Staff info banner --}}
      <div class="card card-info card-outline">
        <div class="card-body py-3">
          <div class="d-flex align-items-center">
            @if($staff->photo)
              <img src="{{ asset('public/' . $staff->photo) }}"
                   style="width:55px;height:55px;border-radius:50%;object-fit:cover;border:2px solid #17a2b8;"
                   class="mr-3">
            @else
              <div style="width:55px;height:55px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;" class="mr-3">
                <i class="fas fa-user fa-lg text-secondary"></i>
              </div>
            @endif
            <div>
              <h5 class="mb-0">{{ $staff->name }}
                <span class="badge badge-secondary ml-1">{{ $staff->staff_id }}</span>
                <span class="badge badge-info ml-1">{{ $staff->type }}</span>
                @if($staff->status === 'Inactive')
                  <span class="badge badge-danger ml-1">Inactive</span>
                @endif
              </h5>
              <small class="text-muted">Date: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y, l') }}</strong></small>
            </div>
          </div>
        </div>
      </div>

      {{-- ─── GATE CHECK-IN / OUT ──────────────────────────────────────── --}}
      <div class="card">
        <div class="card-header bg-dark text-white">
          <h3 class="card-title"><i class="fas fa-door-open mr-2"></i>Main Gate Entry / Exit</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th>Gate Check-In (Entry)</th>
                <th>Gate Check-Out (Exit)</th>
                <th>Total Time Inside</th>
                <th>Source</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @if($gateLog && $gateLog->entry_time)
                @php
                  $gateIn  = \Carbon\Carbon::parse($gateLog->entry_time);
                  $gateOut = $gateLog->exit_time ? \Carbon\Carbon::parse($gateLog->exit_time) : null;
                  $gateMins = $gateOut ? $gateIn->diffInMinutes($gateOut) : null;
                  $gateH = $gateMins !== null ? floor($gateMins / 60) : null;
                  $gateM = $gateMins !== null ? ($gateMins % 60) : null;
                  $gateDuration = $gateMins !== null ? ($gateH > 0 ? "{$gateH}h {$gateM}m" : "{$gateM}m") : null;
                @endphp
                <tr>
                  <td>
                    <span class="badge badge-success px-2 py-1">
                      <i class="fas fa-sign-in-alt mr-1"></i>{{ $gateIn->format('h:i A') }}
                    </span>
                  </td>
                  <td>
                    @if($gateOut)
                      <span class="badge badge-danger px-2 py-1">
                        <i class="fas fa-sign-out-alt mr-1"></i>{{ $gateOut->format('h:i A') }}
                      </span>
                    @else
                      <span class="badge badge-warning text-dark">Still Inside</span>
                    @endif
                  </td>
                  <td>{{ $gateDuration ?? '—' }}</td>
                  <td><span class="badge badge-secondary">{{ ucfirst($gateLog->source ?? 'gate') }}</span></td>
                  <td><span class="badge badge-{{ $gateLog->status === 'Present' ? 'success' : 'secondary' }}">{{ $gateLog->status }}</span></td>
                </tr>
              @else
                <tr>
                  <td colspan="5" class="text-center text-muted py-3">
                    <i class="fas fa-info-circle mr-1"></i>No gate check-in recorded for this date.
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>

      {{-- ─── FLAT SESSIONS ────────────────────────────────────────────── --}}
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h3 class="card-title">
            <i class="fas fa-building mr-2"></i>Flat-Level Check-In / Check-Out
            <span class="badge badge-light ml-2">{{ $flatSessions->count() }} flat(s)</span>
          </h3>
        </div>
        <div class="card-body p-0">
          @if($flatSessions->isNotEmpty())
            @foreach($flatSessions as $fs)
              <div class="p-3 border-bottom">
                {{-- Flat header --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <strong><i class="fas fa-home mr-1 text-primary"></i>
                      @if($fs['block']) {{ $fs['block'] }} - @endif
                      {{ $fs['flat_number'] }}
                    </strong>
                  </div>
                  <div>
                    <span class="badge badge-primary">Total: {{ $fs['total_duration'] }}</span>
                    <span class="badge badge-light text-dark">{{ $fs['sessions']->count() }} session(s)</span>
                  </div>
                </div>

                {{-- Sessions table --}}
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Check-In Time</th>
                      <th>Check-Out Time</th>
                      <th>Duration</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($fs['sessions'] as $si => $session)
                      <tr class="{{ $session['is_open'] ? 'table-warning' : '' }}">
                        <td>{{ $si + 1 }}</td>
                        <td>
                          @if($session['check_in_time'])
                            <span class="text-success font-weight-bold">
                              <i class="fas fa-arrow-right mr-1"></i>{{ $session['check_in_time'] }}
                            </span>
                          @else
                            <span class="text-muted">—</span>
                          @endif
                        </td>
                        <td>
                          @if($session['check_out_time'])
                            <span class="text-danger font-weight-bold">
                              <i class="fas fa-arrow-left mr-1"></i>{{ $session['check_out_time'] }}
                            </span>
                          @else
                            <span class="badge badge-warning text-dark">Still In Flat</span>
                          @endif
                        </td>
                        <td>{{ $session['duration'] ?? '—' }}</td>
                        <td>
                          @if($session['is_open'])
                            <span class="badge badge-warning text-dark">Active</span>
                          @elseif($session['check_out_time'])
                            <span class="badge badge-success">Completed</span>
                          @else
                            <span class="badge badge-secondary">Pending</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                  <tfoot class="bg-light">
                    <tr>
                      <td colspan="3" class="text-right font-weight-bold">Total Time at this Flat:</td>
                      <td colspan="2" class="font-weight-bold text-primary">{{ $fs['total_duration'] }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            @endforeach
          @else
            <div class="text-center text-muted py-4">
              <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
              No flat check-in/check-out records for this date.
            </div>
          @endif
        </div>
      </div>

    @elseif($selectedStaffId && !$staff)
      <div class="alert alert-warning">Staff member not found in this building.</div>
    @elseif(!$selectedStaffId)
      <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        Please select a staff member and date above to view attendance logs.
      </div>
    @endif

  </div>
</section>
@endsection

@section('scripts')
<script>
  // Init Select2 if available
  $(document).ready(function () {
    if (typeof $.fn.select2 !== 'undefined') {
      $('#staff_id').select2({ placeholder: '— Select Staff —', allowClear: true, width: '100%' });
    }
  });
</script>
@endsection
