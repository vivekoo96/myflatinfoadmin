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
    @if($errors->any())
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
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
                    <th>Created On</th>
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
                        @php
                          $votingLabels = [
                            'user_based' => 'User Based',
                            'owner_based' => 'Owner Based',
                            'tenant_based' => 'Tenant Based'
                          ];
                        @endphp
                        <span class="badge badge-secondary">
                          {{ $votingLabels[$poll->voting_type] ?? $poll->voting_type }}
                        </span>
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
                        @if($poll->created_at)
                          {{ $poll->created_at->format('d M Y, h:i A') }}
                        @else
                          —
                        @endif
                      </td>
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

                          {{-- EDIT (draft only) --}}
                          @if($poll->status === 'draft')
                            <button class="btn btn-sm btn-info mb-1 btn-edit-poll"
                              data-id="{{ $poll->id }}" title="Edit Draft">
                              <i class="fa fa-pencil-alt"></i>
                            </button>
                          @endif

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

                          {{-- RESTORE/REOPEN (closed or published) --}}
                          @if(in_array($poll->status, ['closed', 'published']))
                            <button class="btn btn-sm btn-success mb-1 btn-reopen"
                              data-id="{{ $poll->id }}" title="Restore">
                              <i class="fa fa-undo"></i>
                            </button>
                          @endif

                          {{-- RELEASE RESULTS (closed only) --}}
                          @if($poll->status === 'closed')
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
                      <td colspan="11" class="text-center text-muted py-3">
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

      <form method="POST" action="{{ route('poll.store') }}" id="createPollForm" autocomplete="off">
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
                  <option value="owner_based">Owner-based voting</option>
                  <option value="tenant_based">Tenant-based voting</option>
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

{{-- ===== EDIT POLL MODAL ===== --}}
<div class="modal fade" id="editPollModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Poll / Survey <small class="text-muted" id="editPollSubtitle"></small></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div id="editPollError" class="alert alert-danger d-none"></div>
        <div id="editPollLoader" class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
        <div id="editPollForm" class="d-none">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" id="editTitle" class="form-control" placeholder="Poll title" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Type <span class="text-danger">*</span></label>
                <select id="editType" class="form-control">
                  <option value="poll">Poll</option>
                  <option value="survey">Survey</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Description <small class="text-muted">(optional)</small></label>
            <textarea id="editDescription" class="form-control" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Structure <span class="text-danger">*</span></label>
                <select id="editStructure" class="form-control" onchange="editToggleStructure()">
                  <option value="single">Single Question</option>
                  <option value="multiple">Multiple Questions</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Voting Type <span class="text-danger">*</span></label>
                <select id="editVotingType" class="form-control">
                  <option value="user_based">User-based voting</option>
                  <option value="owner_based">Owner-based voting</option>
                  <option value="tenant_based">Tenant-based voting</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Expiry Date &amp; Time <small class="text-muted">(optional)</small></label>
                <input type="datetime-local" id="editExpiryDate" class="form-control">
              </div>
            </div>
          </div>
          <hr class="mt-1 mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0 font-weight-bold">Questions</label>
            <button type="button" id="editBtnAddQuestion" class="btn btn-sm btn-outline-primary d-none" onclick="editAddQuestion()">
              <i class="fa fa-plus"></i> Add Question
            </button>
          </div>
          <div id="editQuestionsContainer"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="btnSaveEditPoll" disabled>
          <i class="fa fa-save"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

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
    var helpers = {
      'user_based': 'Every registered user (owner & tenant) gets one vote.',
      'owner_based': 'Only flat owners can vote (one vote per flat).',
      'tenant_based': 'Only flat tenants can vote (one vote per flat).'
    };
    $('#votingHelper').text(helpers[val] || 'Select a voting type.');
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
  $(document).on('click', '[data-target="#createPollModal"]', function () {
    var form = document.getElementById('createPollForm');
    form.reset();
    $(form).find('input[type="text"], input[type="datetime-local"], textarea').val('');
    $(form).find('select').prop('selectedIndex', 0);
    $(form).find('.alert-danger').remove();
    $('#btnAddQuestion').addClass('d-none');
    $('#questionsContainer [data-question-index]').not(':first').remove();
    
    var firstQuestion = $('#questionsContainer [data-question-index="0"]');
    firstQuestion.find('input').val('');
    firstQuestion.find('.options-list').html(
      '<div class="input-group mb-2 option-row">' +
        '<input type="text" name="questions[0][options][]" class="form-control" placeholder="Option A" required>' +
        '<div class="input-group-append">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-option" title="Remove option">' +
            '<i class="fa fa-times"></i>' +
          '</button>' +
        '</div>' +
      '</div>' +
      '<div class="input-group mb-2 option-row">' +
        '<input type="text" name="questions[0][options][]" class="form-control" placeholder="Option B" required>' +
        '<div class="input-group-append">' +
          '<button type="button" class="btn btn-outline-danger btn-remove-option" title="Remove option">' +
            '<i class="fa fa-times"></i>' +
          '</button>' +
        '</div>' +
      '</div>'
    );
    
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
        var json = xhr.responseJSON;
        var msg = (json && json.error) ? json.error
                : (json && json.message) ? json.message
                : 'Failed to update expiry.';
        alert(msg);
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

  $(document).on('click', '.btn-reopen', function () {
    var id = $(this).data('id');
    setConfirm('Restore Poll', 'Restore this poll? Voting will resume.', 'btn-success', function () {
      pollAction('/poll/' + id + '/reopen', 'POST');
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
        var json = xhr.responseJSON;
        var msg = (json && json.error) ? json.error
                : (json && json.message) ? json.message
                : 'Action failed. The poll status may have already changed — the page will now refresh.';
        alert(msg);
        location.reload();
      }
    });
  }

  @if($errors->any())
    $('#createPollModal').modal('show');
  @endif

  // ── Edit Draft Poll ──────────────────────────────────────────
  var currentEditId = null;

  function editEsc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function editBuildQuestionHtml(idx, question, options) {
    var labels = 'ABCDEFGHIJ';
    var html = '<div class="card bg-light mb-3" data-eq-index="' + idx + '">' +
      '<div class="card-body pb-2">' +
        '<div class="d-flex justify-content-between align-items-center mb-2">' +
          '<strong class="eq-label text-muted" style="font-size:12px;text-transform:uppercase;">Question ' + (idx+1) + '</strong>' +
          (idx > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger eq-remove-q"><i class="fa fa-times"></i> Remove</button>' : '') +
        '</div>' +
        '<div class="form-group mb-2">' +
          '<input type="text" class="form-control eq-question" placeholder="Enter your question..." value="' + editEsc(question) + '" required>' +
        '</div>' +
        '<div class="eq-options">';
    options.forEach(function(opt, oi) {
      html += '<div class="input-group mb-2 eq-option-row">' +
        '<input type="text" class="form-control" placeholder="Option ' + (labels[oi]||oi+1) + '" value="' + editEsc(opt) + '" required>' +
        '<div class="input-group-append"><button type="button" class="btn btn-outline-danger eq-remove-opt"><i class="fa fa-times"></i></button></div>' +
      '</div>';
    });
    html += '</div>' +
      '<button type="button" class="btn btn-sm btn-outline-secondary eq-add-opt"><i class="fa fa-plus"></i> Add Option</button>' +
    '</div></div>';
    return html;
  }

  window.editToggleStructure = function () {
    if ($('#editStructure').val() === 'multiple') {
      $('#editBtnAddQuestion').removeClass('d-none');
    } else {
      $('#editBtnAddQuestion').addClass('d-none');
      $('#editQuestionsContainer [data-eq-index]').not(':first').remove();
    }
  };

  window.editAddQuestion = function () {
    var idx = $('#editQuestionsContainer [data-eq-index]').length;
    $('#editQuestionsContainer').append(editBuildQuestionHtml(idx, '', ['', '']));
  };

  $(document).on('click', '.eq-add-opt', function () {
    var block = $(this).closest('[data-eq-index]');
    var list = block.find('.eq-options');
    var cnt = list.find('.eq-option-row').length;
    var label = 'ABCDEFGHIJ'[cnt] || (cnt+1);
    list.append('<div class="input-group mb-2 eq-option-row">' +
      '<input type="text" class="form-control" placeholder="Option ' + label + '" required>' +
      '<div class="input-group-append"><button type="button" class="btn btn-outline-danger eq-remove-opt"><i class="fa fa-times"></i></button></div>' +
    '</div>');
  });

  $(document).on('click', '.eq-remove-opt', function () {
    var list = $(this).closest('[data-eq-index]').find('.eq-options');
    if (list.find('.eq-option-row').length > 2) $(this).closest('.eq-option-row').remove();
    else alert('Minimum 2 options required.');
  });

  $(document).on('click', '.eq-remove-q', function () {
    if ($('#editQuestionsContainer [data-eq-index]').length > 1) {
      $(this).closest('[data-eq-index]').remove();
      $('#editQuestionsContainer [data-eq-index]').each(function(i){
        $(this).attr('data-eq-index', i);
        $(this).find('.eq-label').text('Question '+(i+1));
      });
    } else alert('At least one question is required.');
  });

  $(document).on('click', '.btn-edit-poll', function () {
    currentEditId = $(this).data('id');
    $('#editPollError').addClass('d-none');
    $('#editPollLoader').removeClass('d-none');
    $('#editPollForm').addClass('d-none');
    $('#btnSaveEditPoll').prop('disabled', true);
    $('#editPollModal').modal('show');

    $.ajax({
      url: '/poll/' + currentEditId + '/edit-data',
      method: 'GET',
      success: function (d) {
        $('#editTitle').val(d.title);
        $('#editType').val(d.type);
        $('#editDescription').val(d.description || '');
        $('#editStructure').val(d.structure);
        $('#editVotingType').val(d.voting_type);
        $('#editExpiryDate').val(d.expiry_date || '');
        $('#editPollSubtitle').text('(Draft)');
        if (d.structure === 'multiple') $('#editBtnAddQuestion').removeClass('d-none');
        else $('#editBtnAddQuestion').addClass('d-none');
        var c = $('#editQuestionsContainer').empty();
        d.questions.forEach(function(q, i) { c.append(editBuildQuestionHtml(i, q.question, q.options)); });
        $('#editPollLoader').addClass('d-none');
        $('#editPollForm').removeClass('d-none');
        $('#btnSaveEditPoll').prop('disabled', false);
      },
      error: function () {
        $('#editPollModal').modal('hide');
        alert('Failed to load poll data.');
      }
    });
  });

  $('#btnSaveEditPoll').on('click', function () {
    var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    $('#editPollError').addClass('d-none');

    var title = $('#editTitle').val().trim();
    if (!title) {
      $('#editPollError').removeClass('d-none').text('Title is required.');
      $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
      return;
    }

    var payload = {
      _token: '{{ csrf_token() }}',
      title: title,
      description: $('#editDescription').val(),
      type: $('#editType').val(),
      structure: $('#editStructure').val(),
      voting_type: $('#editVotingType').val(),
      expiry_date: $('#editExpiryDate').val()
    };

    var valid = true;
    $('#editQuestionsContainer [data-eq-index]').each(function(qi) {
      var qText = $(this).find('.eq-question').val().trim();
      if (!qText) { valid = false; return false; }
      payload['questions['+qi+'][question]'] = qText;
      var oi = 0;
      $(this).find('.eq-option-row input').each(function() {
        var v = $(this).val().trim();
        if (v) { payload['questions['+qi+'][options]['+oi+']'] = v; oi++; }
      });
      if (oi < 2) { valid = false; return false; }
    });

    if (!valid) {
      $('#editPollError').removeClass('d-none').text('Each question needs a title and at least 2 options.');
      $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
      return;
    }

    $.ajax({
      url: '/poll/' + currentEditId + '/update-draft',
      method: 'POST',
      data: payload,
      success: function () { $('#editPollModal').modal('hide'); location.reload(); },
      error: function (xhr) {
        var json = xhr.responseJSON;
        var msg = (json && json.error) ? json.error
                : (json && json.message) ? json.message
                : 'Failed to save.';
        $('#editPollError').removeClass('d-none').text(msg);
        $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
      }
    });
  });

  // ── Auto-refresh / focus sync to stay in sync with Super Admin ─
  var _autoRefreshTimer = setInterval(function () {
    if (!$('#confirmModal').hasClass('show') && !$('#createPollModal').hasClass('show') && !$('#editExpiryModal').hasClass('show') && !$('#editPollModal').hasClass('show')) {
      location.reload();
    }
  }, 30000);

  // Cancel auto-refresh while a modal is open so it doesn't interrupt the user
  $(document).on('show.bs.modal', function () { clearInterval(_autoRefreshTimer); });
  $(document).on('hidden.bs.modal', function () {
    _autoRefreshTimer = setInterval(function () {
      location.reload();
    }, 30000);
  });

  // Refresh page immediately when user switches back to this tab
  $(window).on('focus', function () {
    if (!$('#confirmModal').hasClass('show') && !$('#createPollModal').hasClass('show') && !$('#editExpiryModal').hasClass('show') && !$('#editPollModal').hasClass('show')) {
      location.reload();
    }
  });

});
</script>
@endsection
