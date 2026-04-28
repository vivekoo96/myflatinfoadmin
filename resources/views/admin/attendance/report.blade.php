@extends('layouts.admin')

@section('title')
    Monthly Attendance Report
@endsection

@section('style')
<style>
    .table-custom {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    .table-custom th, .table-custom td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    .table-custom thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }
</style>
@endsection

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
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
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Monthly Summary: <strong>{{ \Carbon\Carbon::create()->month($month)->format('F') }}, {{ $year }}</strong></h3>
                <div class="card-tools">
                   <form action="{{ route('admin.attendance.report') }}" method="GET" class="form-inline">
                        <select name="month" class="form-control form-control-sm mr-2">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endfor
                        </select>
                        <select name="year" class="form-control form-control-sm mr-2">
                            @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                   </form>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                @php
                                    $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
                                @endphp
                                @for($i=1; $i<=$daysInMonth; $i++)
                                    <th class="text-center" style="min-width: 30px;">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $staff)
                            <tr>
                                <td nowrap><strong>{{ $staff->name }}</strong><br><small>{{ $staff->type }}</small></td>
                                @php $presentCount = 0; @endphp
                                @for($i=1; $i<=$daysInMonth; $i++)
                                    @php
                                        $currentDate = \Carbon\Carbon::create($year, $month, $i)->format('Y-m-d');
                                        $log = $staff->attendanceLogs->where('date', $currentDate)->first();
                                        $status = $log ? $log->status : '-';
                                        if($status == 'Present') $presentCount++;
                                    @endphp
                                    <td class="text-center">
                                        @if($status == 'Present')
                                            <span class="text-success" title="Present"><i class="fa fa-check"></i></span>
                                        @elseif($status == 'Absent')
                                            <span class="text-danger" title="Absent"><i class="fa fa-times"></i></span>
                                        @elseif($status == 'On Leave')
                                            <span class="text-warning" title="On Leave">L</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-center font-weight-bold">{{ $presentCount }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $daysInMonth + 2 }}" class="text-center">No staff found</td>
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
