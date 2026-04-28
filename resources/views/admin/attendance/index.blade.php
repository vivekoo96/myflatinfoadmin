@extends('layouts.admin')

@section('title')
    Attendance Management
@endsection

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Attendance Management</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Attendance</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Attendance for: <strong>{{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</strong></h3>
                <div class="card-tools">
                   <form action="{{ route('admin.attendance.index') }}" method="GET" class="form-inline">
                        <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                        <button type="submit" class="btn btn-sm btn-primary">Go</button>
                   </form>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered no-dt">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Gate Entry</th>
                                <th>Gate Exit</th>
                                <th>Flat Check-ins</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $staff)
                            @php $log = $staff->attendanceLogs->first(); @endphp
                            <tr id="staff-row-{{ $staff->id }}">
                                <td>{{ $staff->staff_id }}</td>
                                <td>{{ $staff->name }}</td>
                                <td>{{ $staff->type }}</td>
                                <td>{{ $log && $log->entry_time ? \Carbon\Carbon::parse($log->entry_time)->format('h:i A') : '-' }}</td>
                                <td>{{ $log && $log->exit_time ? \Carbon\Carbon::parse($log->exit_time)->format('h:i A') : '-' }}</td>
                                <td>
                                    @if($log)
                                        <span class="badge badge-info">{{ $log->flatAttendances->count() }} flats</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <select class="form-control form-control-sm status-select" data-id="{{ $staff->id }}">
                                        <option value="Present" {{ ($log && $log->status == 'Present') ? 'selected' : '' }}>Present</option>
                                        <option value="Absent" {{ ($log && $log->status == 'Absent') ? 'selected' : '' }}>Absent</option>
                                        <option value="On Leave" {{ ($log && $log->status == 'On Leave') ? 'selected' : '' }}>On Leave</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success save-attendance" data-id="{{ $staff->id }}">Save</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No staff found</td>
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

@section('script')
<script>
    $(document).ready(function() {
        $('.save-attendance').on('click', function() {
            var staffId = $(this).data('id');
            var status = $('#staff-row-' + staffId + ' .status-select').val();
            var date = '{{ $date }}';

            $.ajax({
                url: '{{ route("admin.attendance.mark") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    staff_id: staffId,
                    status: status,
                    date: date
                },
                success: function(response) {
                    if(response.success) {
                        alert(response.message);
                    }
                }
            });
        });
    });
</script>
@endsection
