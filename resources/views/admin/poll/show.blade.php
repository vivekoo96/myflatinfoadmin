@extends('layouts.admin')

@section('title')
    {{ $poll->title }} – Results
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{ $poll->title }}</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('poll.index') }}">Polls &amp; Surveys</a></li>
          <li class="breadcrumb-item active">Details</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    {{-- ── Poll Info Card ── --}}
    <div class="row">
      <div class="col-md-4">
        <div class="card card-primary card-outline">
          <div class="card-header">
            <h3 class="card-title">Poll Info</h3>
          </div>
          <div class="card-body">
            @php
              $displayStatus = $poll->display_status;
              $badgeClass = match($displayStatus) {
                'draft'         => 'badge-secondary',
                'active'        => 'badge-success',
                'expiring_soon' => 'badge-warning',
                'closed'        => 'badge-danger',
                'published'     => 'badge-primary',
                default         => 'badge-secondary',
              };
            @endphp

            <table class="table table-sm table-borderless">
              <tr><th width="40%">Type</th><td><span class="badge badge-info">{{ ucfirst($poll->type) }}</span></td></tr>
              <tr><th>Structure</th><td>{{ ucfirst($poll->structure) }}-Question</td></tr>
              <tr><th>Voting</th><td>{{ $poll->voting_type === 'flat_based' ? 'Flat Based' : 'User Based' }}</td></tr>
              <tr><th>Status</th><td><span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_',' ',$displayStatus)) }}</span></td></tr>
              <tr><th>Expiry</th><td>{{ $poll->expiry_date ? $poll->expiry_date->format('d M Y, h:i A') : 'No expiry' }}</td></tr>
              <tr><th>Total Voters</th><td><strong>{{ $poll->total_voters }}</strong></td></tr>
              <tr><th>Created By</th>
                <td>{{ $poll->creator ? $poll->creator->name : '—' }}<br>
                  <small class="text-muted">{{ $poll->created_by_role }}</small>
                </td>
              </tr>
              @if($poll->result_released_at)
                <tr><th>Released At</th><td>{{ $poll->result_released_at->format('d M Y, h:i A') }}</td></tr>
              @endif
            </table>

            @if($poll->description)
              <p class="text-muted">{{ $poll->description }}</p>
            @endif

            <div class="mt-3">
              {{-- Activate --}}
              @if($poll->status === 'draft' && !$poll->deleted_at)
                <button class="btn btn-success btn-sm btn-activate" data-id="{{ $poll->id }}">
                  <i class="fa fa-play"></i> Activate Poll
                </button>
              @endif

              {{-- Close --}}
              @if($poll->status === 'active' && !$poll->deleted_at)
                <button class="btn btn-danger btn-sm btn-close-poll" data-id="{{ $poll->id }}">
                  <i class="fa fa-stop"></i> Close Poll
                </button>
              @endif

              {{-- Release Results --}}
              @if($poll->status === 'closed' && $poll->created_by == Auth::id() && !$poll->deleted_at)
                <button class="btn btn-primary btn-sm btn-release" data-id="{{ $poll->id }}">
                  <i class="fa fa-unlock"></i> Release Results
                </button>
              @endif

              {{-- Delete --}}
              @if(!$poll->deleted_at)
                <button class="btn btn-secondary btn-sm btn-delete mt-1" data-id="{{ $poll->id }}" data-action="delete">
                  <i class="fa fa-trash"></i> Delete
                </button>
              @else
                <button class="btn btn-success btn-sm btn-delete mt-1" data-id="{{ $poll->id }}" data-action="restore">
                  <i class="fa fa-undo"></i> Restore
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- ── Results ── --}}
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              Voting Results
              @if($poll->status !== 'published')
                <small class="text-muted ml-2">(Not yet released to users)</small>
              @endif
            </h3>
          </div>
          <div class="card-body">

            @if(count($results) === 0)
              <p class="text-muted text-center">No questions found.</p>
            @else
              @foreach($results as $qResult)
                <div class="mb-4">
                  <h6 class="font-weight-bold">{{ $loop->iteration }}. {{ $qResult['question'] }}</h6>
                  <small class="text-muted">Total votes: {{ $qResult['total_votes'] }}</small>

                  @if($qResult['total_votes'] === 0)
                    <p class="text-muted mt-2">No votes yet.</p>
                  @else
                    @foreach($qResult['options'] as $opt)
                      <div class="mb-2">
                        <div class="d-flex justify-content-between">
                          <span>{{ $opt['text'] }}</span>
                          <span><strong>{{ $opt['votes'] }}</strong> votes ({{ $opt['percentage'] }}%)</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                          <div class="progress-bar bg-primary" role="progressbar"
                            style="width: {{ $opt['percentage'] }}%"
                            aria-valuenow="{{ $opt['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $opt['percentage'] }}%
                          </div>
                        </div>
                      </div>
                    @endforeach
                  @endif
                </div>

                @if(! $loop->last)
                  <hr>
                @endif
              @endforeach
            @endif

          </div>
        </div>
      </div>
    </div>

  </div>
</section>

{{-- Confirm Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmTitle">Confirm</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="confirmBody">Are you sure?</div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" id="btnConfirmAction">Confirm</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
$(function () {
  var pendingAction = null;

  function setConfirm(title, body, btnClass, action) {
    $('#confirmTitle').text(title);
    $('#confirmBody').text(body);
    $('#btnConfirmAction').removeClass().addClass('btn btn-sm ' + btnClass);
    pendingAction = action;
    $('#confirmModal').modal('show');
  }

  $('#btnConfirmAction').on('click', function () {
    if (pendingAction) pendingAction();
    $('#confirmModal').modal('hide');
  });

  $(document).on('click', '.btn-activate', function () {
    var id = $(this).data('id');
    setConfirm('Activate Poll', 'Activate this poll? It will go live for all users.', 'btn-success', function () {
      pollAction('/poll/' + id + '/activate', 'POST');
    });
  });

  $(document).on('click', '.btn-close-poll', function () {
    var id = $(this).data('id');
    setConfirm('Close Poll', 'Close this poll? No more votes will be accepted.', 'btn-danger', function () {
      pollAction('/poll/' + id + '/close', 'POST');
    });
  });

  $(document).on('click', '.btn-release', function () {
    var id = $(this).data('id');
    setConfirm('Release Results', 'Release results to all users?', 'btn-primary', function () {
      pollAction('/poll/' + id + '/release-results', 'POST');
    });
  });

  $(document).on('click', '.btn-delete', function () {
    var id     = $(this).data('id');
    var action = $(this).data('action');
    var isRestore = action === 'restore';
    setConfirm(
      isRestore ? 'Restore Poll' : 'Delete Poll',
      isRestore ? 'Restore this poll?' : 'Delete this poll?',
      isRestore ? 'btn-success' : 'btn-danger',
      function () { pollAction('/poll/' + id, 'DELETE', { action: action }); }
    );
  });

  function pollAction(url, method, extra) {
    var data = $.extend({ _token: '{{ csrf_token() }}' }, extra || {});
    if (method === 'DELETE') { data._method = 'DELETE'; method = 'POST'; }
    $.ajax({
      url: url, method: method, data: data,
      success: function () {
        if (url.includes('release') || url.includes('activate') || url.includes('close')) {
          location.reload();
        } else {
          window.location.href = '{{ route("poll.index") }}';
        }
      },
      error: function (xhr) {
        alert(xhr.responseJSON ? xhr.responseJSON.error : 'Action failed.');
      }
    });
  }
});
</script>
@endsection
