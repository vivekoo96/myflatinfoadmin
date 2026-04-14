@extends('layouts.admin')

@section('title') Meetings @endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Meetings</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Meetings</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
      </div>
    @endif

    <div class="row">

      {{-- Add Meeting Form --}}
      <div class="col-md-4">
        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title">Add Meeting</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('meeting.store') }}">
              @csrf
              @if($errors->any())
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                  </ul>
                </div>
              @endif
              <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                  placeholder="e.g. Monthly Society Meeting"
                  value="{{ old('title') }}" required>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4"
                  placeholder="Agenda or details...">{{ old('description') }}</textarea>
              </div>
              <div class="form-group">
                <label>Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="datetime" class="form-control" id="meetingDateTime"
                  value="{{ old('datetime') }}" required>
              </div>
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-save mr-1"></i> Save Meeting
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Meetings List --}}
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">All Meetings</h3>
            <span class="badge badge-secondary ml-2">{{ $meetings->count() }} records</span>
          </div>
          <div class="card-body p-0">
            @if($meetings->isEmpty())
              <div class="text-center text-muted py-5">
                <i class="fa fa-calendar fa-2x mb-2"></i>
                <p>No meetings found.</p>
              </div>
            @else
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Title</th>
                      <th>Date &amp; Time</th>
                      <th>Description</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($meetings as $i => $meeting)
                      <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="font-weight-bold">{{ $meeting->title }}</td>
                        <td style="white-space:nowrap;">
                          @if($meeting->datetime)
                            <i class="fa fa-calendar mr-1 text-muted"></i>
                            {{ \Carbon\Carbon::parse($meeting->datetime)->format('d M Y') }}
                            <br><i class="fa fa-clock mr-1 text-muted"></i>
                            {{ \Carbon\Carbon::parse($meeting->datetime)->format('h:i A') }}
                          @else
                            <span class="text-muted">—</span>
                          @endif
                        </td>
                        <td>{{ $meeting->description ? \Illuminate\Support\Str::limit($meeting->description, 80) : '—' }}</td>
                        <td>
                          <form method="POST"
                            action="{{ route('meeting.destroy', $meeting->id) }}"
                            onsubmit="return confirm('Delete this meeting?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                              <i class="fa fa-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const dateTimeInput = document.getElementById('meetingDateTime');
  const form = dateTimeInput.closest('form');

  // Get current date and time
  const now = new Date();

  // Format current datetime to ISO 8601 format (YYYY-MM-DDTHH:MM) for comparison
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const nowISO = `${year}-${month}-${day}T${hours}:${minutes}`;

  // Set maximum datetime to now (prevent future dates)
  dateTimeInput.max = nowISO;

  // Form submission validation - ensure datetime is not in the future
  if (form) {
    form.addEventListener('submit', function(e) {
      const selectedDateTime = dateTimeInput.value;

      if (!selectedDateTime) {
        return; // Browser validation will handle this
      }

      // Parse the selected datetime (format: YYYY-MM-DDTHH:MM)
      const selectedDate = new Date(selectedDateTime);

      if (selectedDate > now) {
        e.preventDefault();
        alert('Please select a date and time in the past or present.');
        return false;
      }
    });
  }
});
</script>
@endsection
