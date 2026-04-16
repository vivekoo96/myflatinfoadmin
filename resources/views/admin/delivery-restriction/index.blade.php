@extends('layouts.admin')

@section('title')
    Delivery Entry Restriction
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Delivery Entry Restriction</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Delivery Entry Restriction</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-8">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Configure Delivery Entry Times</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session()->get('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session()->get('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <form action="{{ route('delivery-restriction.update') }}" method="POST">
              @csrf
              @method('PUT')

              <div class="form-group">
                <label for="delivery_entry_start_time">Delivery Entry Start Time</label>
                <input type="time"
                       class="form-control @error('delivery_entry_start_time') is-invalid @enderror"
                       id="delivery_entry_start_time"
                       name="delivery_entry_start_time"
                       value="{{ $building->delivery_entry_start_time ?? '08:00' }}"
                       required>
                <small class="form-text text-muted">Delivery entry allowed from this time (e.g., 08:00)</small>
                @error('delivery_entry_start_time')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="delivery_entry_end_time">Delivery Entry End Time</label>
                <input type="time"
                       class="form-control @error('delivery_entry_end_time') is-invalid @enderror"
                       id="delivery_entry_end_time"
                       name="delivery_entry_end_time"
                       value="{{ $building->delivery_entry_end_time ?? '21:00' }}"
                       required>
                <small class="form-text text-muted">Delivery entry allowed until this time (e.g., 21:00)</small>
                @error('delivery_entry_end_time')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>

              <hr>

              <div class="alert alert-info">
                <strong>Note:</strong> Deliveries will only be allowed between the configured times. After the end time, the "Allow Entry" option will not be displayed in the mobile app.
              </div>

              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
              </button>
              <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
              </a>
            </form>
          </div>
          <!-- /.card-body -->
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">Current Settings</h3>
          </div>
          <div class="card-body">
            <p><strong>Start Time:</strong> {{ $building->delivery_entry_start_time ?? '08:00' }}</p>
            <p><strong>End Time:</strong> {{ $building->delivery_entry_end_time ?? '21:00' }}</p>

            <hr>

            <h5>Entry Window</h5>
            <p>Deliveries allowed from <strong>{{ $building->delivery_entry_start_time ?? '08:00' }}</strong> to <strong>{{ $building->delivery_entry_end_time ?? '21:00' }}</strong></p>

            <p class="text-muted small">Outside this window, delivery personnel will not be able to request entry access.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
