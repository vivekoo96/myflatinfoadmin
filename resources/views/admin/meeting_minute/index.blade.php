@extends('layouts.admin')

@section('title') Meeting Minutes @endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Meeting Minutes</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Meeting Minutes</li>
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
      {{-- Left: Add New --}}
      <div class="col-md-4">
        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title">Add Meeting Minutes</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('meeting-minute.store') }}">
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
                <input type="text" name="title" id="minuteTitle" class="form-control"
                  placeholder="e.g. AGM Meeting – March 2026"
                  maxlength="150"
                  value="{{ old('title') }}" required>
              </div>
              <div class="form-group">
                <label>Description / Minutes <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="6"
                  placeholder="Enter the full meeting minutes here..." required>{{ old('description') }}</textarea>
              </div>
              <div class="form-group">
                <label>Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="datetime" class="form-control" id="minuteDateTime"
                  value="{{ old('datetime', '') }}" required>
              </div>
              <div class="alert alert-info py-2 small mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                Meeting minutes <strong>cannot be edited or deleted</strong> after saving.
              </div>
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-save mr-1"></i> Save Meeting Minutes
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Right: List --}}
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">All Meeting Minutes</h3>
            <span class="badge badge-secondary ml-2">{{ $minutes->count() }} records</span>
          </div>
          <div class="card-body p-3 border-bottom bg-light">
            <form method="GET" action="{{ route('meeting-minute.index') }}" class="form-row">
              <div class="col-md-5 mb-2">
                <input type="text" name="search" class="form-control" placeholder="Search title or description..." value="{{ request('search') }}">
              </div>
              <div class="col-md-3 mb-2">
                <input type="date" name="from_date" class="form-control" placeholder="From Date" value="{{ request('from_date') }}">
              </div>
              <div class="col-md-3 mb-2">
                <input type="date" name="to_date" class="form-control" placeholder="To Date" value="{{ request('to_date') }}">
              </div>
              <div class="col-md-1 mb-2">
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i></button>
              </div>
            </form>
          </div>
          <div class="card-body p-0">
            @if($minutes->isEmpty())
              <div class="text-center text-muted py-5">
                <i class="fa fa-file-alt fa-2x mb-2"></i>
                <p>No meeting minutes yet.</p>
              </div>
            @else
              <div class="list-group list-group-flush" id="minutesList">
                @foreach($minutes as $minute)
                  <div class="list-group-item" id="minute-{{ $minute->id }}">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1">
                        <h6 class="mb-1 font-weight-bold" style="word-break: break-word; overflow-wrap: break-word;">{{ $minute->title }}</h6>
                        <div class="text-muted small mb-2">
                          <i class="fa fa-calendar mr-1"></i>
                          <strong>Meeting:</strong> {{ \Carbon\Carbon::parse($minute->datetime)->format('d M Y, h:i A') }}
                          &nbsp;•&nbsp;
                          <i class="fa fa-user mr-1"></i>
                          {{ $minute->creator ? $minute->creator->name : '—' }}
                          <span class="badge badge-light border ml-1">{{ $minute->created_by_role }}</span>
                        </div>
                        {{-- Collapsed description --}}
                        <div class="minute-body {{ strlen($minute->description) > 200 ? 'collapsed-text' : '' }}"
                          id="body-{{ $minute->id }}"
                          style="word-break: break-word; overflow-wrap: break-word; {{ strlen($minute->description) > 200 ? 'max-height:80px;overflow:hidden;' : '' }}">
                          {!! nl2br(e($minute->description)) !!}
                        </div>
                        @if(strlen($minute->description) > 200)
                          <a href="#" class="small btn-toggle-minute" data-id="{{ $minute->id }}"
                            data-expanded="0">Read more</a>
                        @endif
                      </div>
                      <span class="badge badge-light border text-muted ml-3" style="white-space:nowrap;">
                        Read Only
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
@endsection

@section('script')
<script>
// Prevent future date/time selection
document.addEventListener('DOMContentLoaded', function() {
  const dateTimeInput = document.getElementById('minuteDateTime');
  const form = dateTimeInput.closest('form');
  const titleInput = document.getElementById('minuteTitle');
  const descriptionInput = document.querySelector('textarea[name="description"]');

  function updateMaxDateTime() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    dateTimeInput.max = `${year}-${month}-${day}T${hours}:${minutes}`;
  }

  // Update max dynamically on page load and on input focus/click
  updateMaxDateTime();
  dateTimeInput.addEventListener('focus', updateMaxDateTime);
  dateTimeInput.addEventListener('click', updateMaxDateTime);

  // Form submission validation
  if (form) {
    form.addEventListener('submit', function(e) {
      // 1. Check title for words without spaces (continuous words longer than 50 chars)
      const titleVal = titleInput.value.trim();
      const titleWords = titleVal.split(/\s+/);
      if (titleWords.some(word => word.length > 50)) {
        e.preventDefault();
        alert('Title contains a word that is too long (maximum 50 characters per word without space). Please use spaces between words.');
        titleInput.focus();
        return false;
      }

      // 2. Check description for words without spaces
      const descVal = descriptionInput.value.trim();
      const descWords = descVal.split(/\s+/);
      if (descWords.some(word => word.length > 50)) {
        e.preventDefault();
        alert('Description contains a word that is too long (maximum 50 characters per word without space). Please use spaces between words.');
        descriptionInput.focus();
        return false;
      }

      // 3. Time validation using actual current time at submission
      const selectedDateTime = dateTimeInput.value;
      if (!selectedDateTime) {
        return;
      }

      const selectedDate = new Date(selectedDateTime);
      const currentNow = new Date();

      if (selectedDate > currentNow) {
        e.preventDefault();
        alert('Please select a date and time in the past or present.');
        dateTimeInput.focus();
        return false;
      }
    });
  }
});

// Toggle read more/less for meeting minutes
$(document).on('click', '.btn-toggle-minute', function (e) {
  e.preventDefault();
  var id = $(this).data('id');
  var expanded = $(this).data('expanded') == 1;
  var body = $('#body-' + id);
  if (expanded) {
    body.css({ 'max-height': '80px', 'overflow': 'hidden' });
    $(this).text('Read more').data('expanded', 0);
  } else {
    body.css({ 'max-height': 'none', 'overflow': 'visible' });
    $(this).text('Show less').data('expanded', 1);
  }
});
</script>
@endsection
