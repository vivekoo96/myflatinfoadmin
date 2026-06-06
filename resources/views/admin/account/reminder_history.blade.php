@extends('layouts.admin')

@section('title', 'Reminder History')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Pending-Bill Reminder History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{url('account/pending-bills')}}">Pending Bills</a></li>
                        <li class="breadcrumb-item active">Reminder History</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{url('account/reminder-history')}}" class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="flat_id">Flat</label>
                            <select name="flat_id" id="flat_id" class="form-control">
                                <option value="">All Flats</option>
                                @foreach($blocks as $block)
                                    @foreach($block->flats as $flat)
                                        <option value="{{$flat->id}}" {{ request('flat_id') == $flat->id ? 'selected' : '' }}>
                                            {{$block->name}} - {{$flat->name}}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="bill_type">Bill Type</label>
                            <select name="bill_type" id="bill_type" class="form-control">
                                <option value="">All</option>
                                <option value="maintenance" {{ request('bill_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="essential" {{ request('bill_type') == 'essential' ? 'selected' : '' }}>Essential</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="from_date">From</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="to_date">To</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                            <a href="{{url('account/reminder-history')}}" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Reminders Sent <span class="badge badge-secondary">{{ $logs->count() }}</span></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="reminderHistoryTable">
                            <thead>
                                <tr>
                                    <th>S No</th>
                                    <th>Flat</th>
                                    <th>Bill Type</th>
                                    <th>Recipients</th>
                                    <th>Sent By</th>
                                    <th>Sent On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0; ?>
                                @forelse($logs as $log)
                                <?php $i++; ?>
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ ($log->flat->block->name ?? '') }} - {{ $log->flat->name ?? $log->flat_id }}</td>
                                    <td>
                                        @if($log->bill_type == 'maintenance')
                                            <span class="badge badge-warning">Maintenance</span>
                                        @else
                                            <span class="badge badge-info">Essential</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->recipients_count }}</td>
                                    <td>{{ $log->sender->name ?? '-' }}</td>
                                    <td>{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No reminders have been sent yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
$(document).ready(function() {
    try {
        if ($.fn.DataTable && $('#reminderHistoryTable tbody tr').length > 0) {
            $('#reminderHistoryTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [[5, 'desc']]
            });
        }
    } catch (e) { console.log('DataTables init error:', e); }
});
</script>
@endsection
