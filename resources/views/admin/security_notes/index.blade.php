@extends('layouts.admin')

@section('title') Security Personal Notes @endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Security Personal Notes</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Security Notes</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- Filter Card --}}
    <div class="card card-primary card-outline mb-3">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filters</h3>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('security-notes.index') }}" class="form-inline">
          <div class="form-group mr-2 mb-2">
            <label for="guard_user_id" class="mr-2">Guard:</label>
            <select name="guard_user_id" id="guard_user_id" class="form-control">
              <option value="">All Guards</option>
              @foreach($guards as $guard)
                <option value="{{ $guard->id }}" {{ $filters['guard_user_id'] == $guard->id ? 'selected' : '' }}>
                  {{ $guard->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group mr-2 mb-2">
            <label for="gate_id" class="mr-2">Gate:</label>
            <select name="gate_id" id="gate_id" class="form-control">
              <option value="">All Gates</option>
              @foreach($gates as $gate)
                <option value="{{ $gate->id }}" {{ $filters['gate_id'] == $gate->id ? 'selected' : '' }}>
                  {{ $gate->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group mr-2 mb-2">
            <label for="from_date" class="mr-2">From Date:</label>
            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $filters['from_date'] }}">
          </div>

          <div class="form-group mr-2 mb-2">
            <label for="to_date" class="mr-2">To Date:</label>
            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $filters['to_date'] }}">
          </div>

          <div class="form-group mb-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="{{ route('security-notes.index') }}" class="btn btn-secondary ml-2">
              <i class="fas fa-redo mr-1"></i> Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    {{-- Results Card --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Security Notes</h3>
        <span class="badge badge-secondary ml-2">{{ $notes->total() }} records</span>
      </div>
      <div class="card-body p-0">
        @if($notes->isEmpty())
          <div class="text-center text-muted py-5">
            <i class="fa fa-file-alt fa-2x mb-2"></i>
            <p>No security notes found.</p>
          </div>
        @else
          <div class="table-responsive">
            <table id="notesTable" class="table table-bordered table-striped mb-0">
              <thead>
                <tr>
                  <th style="width: 50px;">#</th>
                  <th>Guard Name</th>
                  <th>Gate</th>
                  <th>Title</th>
                  <th>Note</th>
                  <th>Noted At</th>
                </tr>
              </thead>
              <tbody>
                @foreach($notes as $i => $note)
                <tr>
                  <td>{{ $notes->firstItem() + $i }}</td>
                  <td>
                    <strong>{{ $note->guard ? $note->guard->name : '—' }}</strong>
                  </td>
                  <td>
                    @if($note->gate)
                      <span class="badge badge-info">{{ $note->gate->name }}</span>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
                  </td>
                  <td>{{ $note->title }}</td>
                  <td>{{ Str::limit($note->body, 80) }}</td>
                  <td>{{ $note->noted_at->format('d M Y, h:i A') }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
      @if(!$notes->isEmpty())
      <div class="card-footer clearfix">
        {{ $notes->links() }}
      </div>
      @endif
    </div>

  </div>
</section>
@endsection
