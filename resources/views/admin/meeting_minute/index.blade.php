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
                <input type="text" name="title" class="form-control"
                  placeholder="e.g. AGM Meeting – March 2026"
                  value="{{ old('title') }}" required>
              </div>
              <div class="form-group">
                <label>Description / Minutes <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="8"
                  placeholder="Enter the full meeting minutes here..." required>{{ old('description') }}</textarea>
              </div>
              <div class="alert alert-info py-2 small mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                Date &amp; time are recorded automatically. Meeting minutes <strong>cannot be edited or deleted</strong> after saving.
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
                        <h6 class="mb-1 font-weight-bold">{{ $minute->title }}</h6>
                        <div class="text-muted small mb-2">
                          <i class="fa fa-clock mr-1"></i>
                          {{ $minute->created_at->format('d M Y, h:i A') }}
                          &nbsp;•&nbsp;
                          <i class="fa fa-user mr-1"></i>
                          {{ $minute->creator ? $minute->creator->name : '—' }}
                          <span class="badge badge-light border ml-1">{{ $minute->created_by_role }}</span>
                        </div>
                        {{-- Collapsed description --}}
                        <div class="minute-body {{ strlen($minute->description) > 200 ? 'collapsed-text' : '' }}"
                          id="body-{{ $minute->id }}"
                          style="{{ strlen($minute->description) > 200 ? 'max-height:80px;overflow:hidden;' : '' }}">
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
