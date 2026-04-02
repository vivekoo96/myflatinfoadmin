@extends('layouts.admin')

@section('title')
    Polls & Surveys
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Polls &amp; Surveys</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Polls &amp; Surveys</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
      </div>
    @endif
    @if(session('success'))
      <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
      </div>
    @endif

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">All Polls &amp; Surveys</h3>
            <div class="card-tools">
              <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#createPollModal">
                <i class="fa fa-plus"></i> Create Poll / Survey
              </button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Structure</th>
                    <th>Voting Type</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th>Total Votes</th>
                    <th>Created By</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($polls as $i => $poll)
                    @php
                      $displayStatus = $poll->display_status;
                      $badgeClass = match($displayStatus) {
                        'draft'          => 'badge-secondary',
                        'active'         => 'badge-success',
                        'expiring_soon'  => 'badge-warning',
                        'closed'         => 'badge-danger',
                        'published'      => 'badge-primary',
                        default          => 'badge-secondary',
                      };
                    @endphp
                    <tr class="{{ $poll->deleted_at ? 'text-muted' : '' }}">
                      <td>{{ $i + 1 }}</td>
                      <td>
                        <strong>{{ $poll->title }}</strong>
                        @if($poll->description)
                          <br><small class="text-muted">{{ Str::limit($poll->description, 60) }}</small>
                        @endif
                        @if($poll->deleted_at)
                          <span class="badge badge-dark ml-1">Deleted</span>
                        @endif
                      </td>
                      <td><span class="badge badge-info">{{ ucfirst($poll->type) }}</span></td>
                      <td>{{ ucfirst($poll->structure) }}</td>
                      <td>
                        @if($poll->voting_type === 'flat_based')
                          <span class="badge badge-secondary">Flat Based</span>
                        @else
                          <span class="badge badge-secondary">User Based</span>
                        @endif
                      </td>
                      <td>
                        <span class="badge {{ $badgeClass }}">
                          {{ ucfirst(str_replace('_', ' ', $displayStatus)) }}
                        </span>
                      </td>
                      <td>
                        @if($poll->expiry_date)
                          {{ $poll->expiry_date->format('d M Y, h:i A') }}
                          @if(!$poll->deleted_at && in_array($poll->status, ['draft','active']))
                            <button class="btn btn-xs btn-outline-secondary ml-1 btn-edit-expiry"
                              data-id="{{ $poll->id }}"
                              data-expiry="{{ $poll->expiry_date->format('Y-m-d\TH:i') }}"
                              title="Edit expiry">
                              <i class="fa fa-edit"></i>
                            </button>
                          @endif
                        @else
                          <span class="text-muted">No expiry</span>
                          @if(!$poll->deleted_at && in_array($poll->status, ['draft','active']))
                            <button class="btn btn-xs btn-outline-secondary ml-1 btn-edit-expiry"
                              data-id="{{ $poll->id }}" data-expiry="" title="Set expiry">
                              <i class="fa fa-edit"></i>
                            </button>
                          @endif
                        @endif
                      </td>
                      <td>{{ $poll->total_voters }}</td>
                      <td>
                        <small>
                          {{ $poll->creator ? $poll->creator->name : '—' }}<br>
                          <span class="text-muted">{{ $poll->created_by_role }}</span>
                        </small>
                      </td>
                      <td style="white-space:nowrap;">
                        @if(!$poll->deleted_at)
                          {{-- VIEW --}}
                          <a href="{{ route('poll.show', $poll->id) }}"
                             class="btn btn-sm btn-warning mb-1" title="View / Results">
                            <i class="fa fa-eye"></i>
                          </a>

                          {{-- ACTIVATE (draft only) --}}
                          @if($poll->status === 'draft')
                            <button class="btn btn-sm btn-success mb-1 btn-activate"
                              data-id="{{ $poll->id }}" title="Activate Poll">
                              <i class="fa fa-play"></i>
                            </button>
                          @endif

                          {{-- CLOSE (active only) --}}
                          @if(in_array($poll->status, ['active']))
                            <button class="btn btn-sm btn-danger mb-1 btn-close-poll"
                              data-id="{{ $poll->id }}" title="Close Poll">
                              <i class="fa fa-stop"></i>
                            </button>
                          @endif

                          {{-- RELEASE RESULTS (closed, creator only) --}}
                          @if($poll->status === 'closed' && $poll->created_by == Auth::id())
                            <button class="btn btn-sm btn-primary mb-1 btn-release"
                              data-id="{{ $poll->id }}" title="Release Results">
                              <i class="fa fa-unlock"></i>
                            </button>
                          @endif

                          {{-- DELETE --}}
                          <button class="btn btn-sm btn-danger mb-1 btn-delete"
                            data-id="{{ $poll->id }}" data-action="delete" title="Delete">
                            <i class="fa fa-trash"></i>
                          </button>

                        @else
                          {{-- RESTORE --}}
                          <button class="btn btn-sm btn-success btn-delete"
                            data-id="{{ $poll->id }}" data-action="restore" title="Restore">
                            <i class="fa fa-undo"></i>
                          </button>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="10" class="text-center text-muted py-3">
                        No polls or surveys found.
                      </td>
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

{{-- ===== CREATE POLL MODAL ===== --}}
<div class="modal fade" id="createPollModal" tabindex="-1" role="dialog" aria-labelledby="createPollLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createPollLabel">Create Poll / Survey</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('poll.store') }}" id="createPollForm">
        @csrf
        <input type="hidden" name="status_action" id="statusAction" value="draft">

        <div class="modal-body">

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- Row 1: Title + Type --}}
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                  placeholder="e.g. Parking Bay Allocation Preference"
                  value="{{ old('title') }}" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control">
                  <option value="poll" {{ old('type') === 'survey' ? '' : 'selected' }}>Poll</option>
                  <option value="survey" {{ old('type') === 'survey' ? 'selected' : '' }}>Survey</option>
                </select>
              </div>
            </div>
          </div>

          {{-- Description --}}
          <div class="form-group">
            <label>Description <small class="text-muted">(optional)</small></label>
            <textarea name="description" class="form-control" rows="2"
              placeholder="Brief description visible to users...">{{ old('description') }}</textarea>
          </div>

          {{-- Row 2: Structure + Voting Type --}}
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Structure <span class="text-danger">*</span></label>
                <select name="structure" id="structureSelect" class="form-control"
                  onchange="toggleStructure()">
                  <option value="single">Single Question</option>
                  <option value="multiple">Multiple Questions</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Voting Type <span class="text-danger">*</span></label>
                <select name="voting_type" id="votingTypeSelect" class="form-control"
                  onchange="updateVotingHelper()">
                  <option value="user_based">User-based voting</option>
                  <option value="flat_based">Flat-based voting</option>
                </select>
                <small class="form-text text-muted" id="votingHelper">
                  Every registered user (owner &amp; tenant) gets one vote.
                </small>
              </div>
            </div>
          </div>

          {{-- Expiry Date --}}
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Expiry Date &amp; Time <small class="text-muted">(optional)</small></label>
                <input type="datetime-local" name="expiry_date" class="form-control"
                  value="{{ old('expiry_date') }}">
                <small class="form-text text-muted">Poll auto-closes after this date.</small>
              </div>
            </div>
          </div>

          <hr class="mt-1 mb-3">

          {{-- Questions --}}
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0 font-weight-bold">Questions</label>
            <button type="button" id="btnAddQuestion" class="btn btn-sm btn-outline-primary d-none"
              onclick="addQuestion()">
              <i class="fa fa-plus"></i> Add Question
            </button>
          </div>

          <div id="questionsContainer">
            {{-- Question 1 (static, always present) --}}
            <div class="card bg-light mb-3" data-question-index="0">
              <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong class="question-label text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Question 1</strong>
                </div>
                <div class="form-group mb-2">
                  <input type="text" name="questions[0][question]" class="form-control"
                    placeholder="Enter your question..." required>
                </div>
                <div class="options-list">
                  <div class="input-group mb-2 option-row">
                    <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option A" required>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-danger btn-remove-option" title="Remove option">
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                  <div class="input-group mb-2 option-row">
                    <input type="text" name="questions[0][options][]" class="form-control" placeholder="Option B" required>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-danger btn-remove-option" title="Remove option">
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-add-option">
                  <i class="fa fa-plus"></i> Add Option
                </button>
              </div>
            </div>
          </div>{{-- /questionsContainer --}}

        </div>{{-- /modal-body --}}

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-outline-primary"
            onclick="document.getElementById('statusAction').value='draft'">
            <i class="fa fa-save"></i> Save as Draft
          </button>
          <button type="submit" class="btn btn-primary"
            onclick="document.getElementById('statusAction').value='activate'">
            <i class="fa fa-play"></i> Create &amp; Activate
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

{{-- ===== EDIT EXPIRY MODAL ===== --}}
<div class="modal fade" id="editExpiryModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Expiry Date</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>New Expiry Date &amp; Time</label>
          <input type="datetime-local" id="newExpiryDate" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="btnSaveExpiry">Update</button>
      </div>
    </div>
  </div>
</div>

{{-- ===== CONFIRM ACTION MODAL ===== --}}
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
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

  // ── Structure toggle ────────────────────────────────────────
  window.toggleStructure = function () {
    if ($('#structureSelect').val() === 'multiple') {
      $('#btnAddQuestion').removeClass('d-none');
    } else {
      $('#btnAddQuestion').addClass('d-none');
      $('#questionsContainer [data-question-index]').not(':first').remove();
      reIndexQuestions();
    }
  };

  // ── Voting type helper text ─────────────────────────────────
  window.updateVotingHelper = function () {
    var val = $('#votingTypeSelect').val();
    $('#votingHelper').text(
      val === 'flat_based'
        ? 'One vote per flat — only the flat owner can vote.'
        : 'Every registered user (owner & tenant) gets one vote.'
    );
  };

  // ── Add option ──────────────────────────────────────────────
  $(document).on('click', '.btn-add-option', function () {
    var block    = $(this).closest('[data-question-index]');
    var qIdx     = block.data('question-index');
    var optList  = block.find('.options-list');
    var cnt      = optList.find('.option-row').length;
    var label    = 'ABCDEFGHIJ'[cnt] || (cnt + 1);
    optList.append(
      '<div class="input-group mb-2 option-row">' +
        '<input type="text" name="questions[' + qIdx + '][options][]" class="form-control" placeholder="Option ' + label + '">' +
        '<div class="input-group-append">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-option" title="Remove option">' +
            '<i class="fa fa-times"></i>' +
          '</button>' +
        '</div>' +
      '</div>'
    );
  });

  // ── Remove option ───────────────────────────────────────────
  $(document).on('click', '.btn-remove-option', function () {
    var optList = $(this).closest('[data-question-index]').find('.options-list');
    if (optList.find('.option-row').length > 2) {
      $(this).closest('.option-row').remove();
    } else {
      alert('Minimum 2 options required per question.');
    }
  });

  // ── Add question ────────────────────────────────────────────
  window.addQuestion = function () {
    var idx = $('#questionsContainer [data-question-index]').length;
    $('#questionsContainer').append(
      '<div class="card bg-light mb-3" data-question-index="' + idx + '">' +
        '<div class="card-body pb-2">' +
          '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<strong class="question-label text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Question ' + (idx + 1) + '</strong>' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-question">' +
              '<i class="fa fa-times"></i> Remove' +
            '</button>' +
          '</div>' +
          '<div class="form-group mb-2">' +
            '<input type="text" name="questions[' + idx + '][question]" class="form-control" placeholder="Enter your question..." required>' +
          '</div>' +
          '<div class="options-list">' +
            '<div class="input-group mb-2 option-row">' +
              '<input type="text" name="questions[' + idx + '][options][]" class="form-control" placeholder="Option A" required>' +
              '<div class="input-group-append"><button type="button" class="btn btn-outline-danger btn-remove-option"><i class="fa fa-times"></i></button></div>' +
            '</div>' +
            '<div class="input-group mb-2 option-row">' +
              '<input type="text" name="questions[' + idx + '][options][]" class="form-control" placeholder="Option B" required>' +
              '<div class="input-group-append"><button type="button" class="btn btn-outline-danger btn-remove-option"><i class="fa fa-times"></i></button></div>' +
            '</div>' +
          '</div>' +
          '<button type="button" class="btn btn-sm btn-outline-secondary btn-add-option">' +
            '<i class="fa fa-plus"></i> Add Option' +
          '</button>' +
        '</div>' +
      '</div>'
    );
  };

  // ── Remove question ─────────────────────────────────────────
  $(document).on('click', '.btn-remove-question', function () {
    if ($('#questionsContainer [data-question-index]').length > 1) {
      $(this).closest('[data-question-index]').remove();
      reIndexQuestions();
    } else {
      alert('At least one question is required.');
    }
  });

  function reIndexQuestions() {
    $('#questionsContainer [data-question-index]').each(function (i) {
      $(this).attr('data-question-index', i);
      $(this).find('.question-label').text('Question ' + (i + 1));
      $(this).find('input[name^="questions["]').each(function () {
        var n = $(this).attr('name');
        if (n) $(this).attr('name', n.replace(/questions\[\d+\]/, 'questions[' + i + ']'));
      });
    });
  }

  // ── Reset modal when opened ─────────────────────────────────
  $('#createPollModal').on('show.bs.modal', function () {
    document.getElementById('createPollForm').reset();
    $('#btnAddQuestion').addClass('d-none');
    $('#questionsContainer [data-question-index]').not(':first').remove();
    reIndexQuestions();
    updateVotingHelper();
  });

  // ── Edit Expiry ─────────────────────────────────────────────
  var currentExpiryPollId = null;

  $(document).on('click', '.btn-edit-expiry', function () {
    currentExpiryPollId = $(this).data('id');
    $('#newExpiryDate').val($(this).data('expiry') || '');
    $('#editExpiryModal').modal('show');
  });

  $('#btnSaveExpiry').on('click', function () {
    if (!currentExpiryPollId) return;
    var newExpiry = $('#newExpiryDate').val();
    if (!newExpiry) { alert('Please select a date.'); return; }
    $.ajax({
      url: '/poll/' + currentExpiryPollId + '/update-expiry',
      method: 'POST',
      data: { expiry_date: newExpiry, _token: '{{ csrf_token() }}' },
      success: function () { $('#editExpiryModal').modal('hide'); location.reload(); },
      error: function (xhr) {
        alert(xhr.responseJSON ? xhr.responseJSON.error : 'Failed to update expiry.');
      }
    });
  });

  // ── Confirm modal helpers ───────────────────────────────────
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

  // ── Poll action buttons ─────────────────────────────────────
  $(document).on('click', '.btn-activate', function () {
    var id = $(this).data('id');
    setConfirm('Activate Poll', 'Activate this poll? It will become live and users will be notified.', 'btn-success', function () {
      pollAction('/poll/' + id + '/activate', 'POST');
    });
  });

  $(document).on('click', '.btn-close-poll', function () {
    var id = $(this).data('id');
    setConfirm('Close Poll', 'Close this poll? Voting will stop. You can release results afterwards.', 'btn-danger', function () {
      pollAction('/poll/' + id + '/close', 'POST');
    });
  });

  $(document).on('click', '.btn-release', function () {
    var id = $(this).data('id');
    setConfirm('Release Results', 'Release results to all users? This action cannot be undone.', 'btn-primary', function () {
      pollAction('/poll/' + id + '/release-results', 'POST');
    });
  });

  $(document).on('click', '.btn-delete', function () {
    var id        = $(this).data('id');
    var isRestore = $(this).data('action') === 'restore';
    setConfirm(
      isRestore ? 'Restore Poll' : 'Delete Poll',
      isRestore ? 'Restore this poll and make it visible again?' : 'Delete this poll? It will be hidden from users.',
      isRestore ? 'btn-success' : 'btn-danger',
      function () { pollAction('/poll/' + id, 'DELETE', { action: isRestore ? 'restore' : 'delete' }); }
    );
  });

  function pollAction(url, method, extra) {
    var data = $.extend({ _token: '{{ csrf_token() }}' }, extra || {});
    if (method === 'DELETE') { data._method = 'DELETE'; method = 'POST'; }
    $.ajax({
      url: url, method: method, data: data,
      success: function () { location.reload(); },
      error: function (xhr) {
        alert(xhr.responseJSON ? xhr.responseJSON.error : 'Action failed.');
      }
    });
  }

});
</script>
@endsection
