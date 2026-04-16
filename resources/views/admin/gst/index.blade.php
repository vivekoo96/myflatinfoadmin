@extends('layouts.admin')

@section('title')
    GST Billing
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>GST Billing</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">GST Billing</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">

    <!-- Filter Section -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Filters</h3>
          </div>
          <div class="card-body">
            <form method="GET" action="{{ route('gst.index') }}" class="form-inline">
              <div class="form-group mr-2">
                <label for="category" class="mr-2">Category:</label>
                <select name="category" id="category" class="form-control">
                  <option value="">All Categories</option>
                  <option value="Maintenance" {{ request('category') == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                  <option value="Essential" {{ request('category') == 'Essential' ? 'selected' : '' }}>Essentials</option>
                  <option value="Facility" {{ request('category') == 'Facility' ? 'selected' : '' }}>Bookings</option>
                </select>
              </div>
              <div class="form-group mr-2">
                <label for="from_date" class="mr-2">From Date:</label>
                <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
              </div>
              <div class="form-group mr-2">
                <label for="to_date" class="mr-2">To Date:</label>
                <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
              </div>
              <button type="submit" class="btn btn-primary">Filter</button>
              <a href="{{ route('gst.index') }}" class="btn btn-secondary ml-2">Reset</a>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon bg-info"><i class="fas fa-calculator"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Base Amount</span>
            <span class="info-box-number">₹{{ number_format($totalBase, 2) }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon bg-warning"><i class="fas fa-percent"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total GST Collected</span>
            <span class="info-box-number">₹{{ number_format($totalGst, 2) }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Amount Received</span>
            <span class="info-box-number">₹{{ number_format($totalAmount, 2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="row mt-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">GST Transactions</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th style="width: 5%">#</th>
                    <th>Date</th>
                    <th>User/Flat</th>
                    <th>Category</th>
                    <th style="width: 15%">Base Amount (₹)</th>
                    <th style="width: 15%">GST Amount (₹)</th>
                    <th style="width: 15%">Total (₹)</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($transactions as $transaction)
                    <tr>
                      <td>{{ $transaction->id }}</td>
                      <td>{{ \Carbon\Carbon::parse($transaction->date)->format('d M Y') }}</td>
                      <td>
                        <small>{{ $transaction->user->name ?? 'N/A' }}</small>
                        @if($transaction->flat_id)
                          <br><small class="text-muted">Flat: {{ $transaction->flat_id }}</small>
                        @endif
                      </td>
                      <td>
                        @if($transaction->model == 'Maintenance')
                          <span class="badge badge-primary">Maintenance</span>
                        @elseif($transaction->model == 'Essential')
                          <span class="badge badge-info">Essentials</span>
                        @else
                          <span class="badge badge-success">Bookings</span>
                        @endif
                      </td>
                      <td class="text-right">{{ number_format($transaction->amount - $transaction->gst_amount, 2) }}</td>
                      <td class="text-right">{{ number_format($transaction->gst_amount, 2) }}</td>
                      <td class="text-right"><strong>{{ number_format($transaction->amount, 2) }}</strong></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center text-muted">No GST transactions found</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
              {{ $transactions->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

@endsection
