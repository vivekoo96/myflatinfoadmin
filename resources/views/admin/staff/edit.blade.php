@extends('layouts.admin')

@section('title')
    Edit Domestic Staff - {{ $staff->name }}
@endsection

@php
    $presetTypes  = ['Maid','Cook','Driver','Security','Gardener','Nanny'];
    $isPreset     = in_array($staff->type, $presetTypes);
    $tag          = $staff->activeTag;
    $currentFlat  = $tag->flat_id ?? null;
    $currentBlock = optional(optional($tag)->flat)->block_id ?? null;
    $currentEng   = $tag->engagement_type ?? 'In-house';
    $currentSlot  = $tag->time_slot ?? '';
@endphp

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Edit Domestic Staff</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Domestic Staff</a></li>
              <li class="breadcrumb-item active">Edit</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        @if($errors->any())
          <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="row">
            <div class="col-md-6">
              <div class="card">
                <div class="card-header"><h3 class="card-title">Staff Details — Gate ID: <strong>{{ $staff->staff_id }}</strong></h3></div>
                <div class="card-body">
                  <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $staff->name) }}" required>
                  </div>
                  <div class="form-group">
                    <label>Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}" required>
                  </div>
                  <div class="form-group">
                    <label>Type <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <select name="type" id="type_select" class="form-control" required>
                        @foreach($allTypes ?? ['Maid','Cook','Driver','Security','Gardener','Nanny'] as $t)
                          <option value="{{ $t }}" {{ old('type', $staff->type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                      </select>
                      <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" id="btn_add_type" title="Add New Type"><i class="fas fa-plus"></i></button>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $staff->address) }}</textarea>
                  </div>
                  <div class="form-group">
                    <label>Photo</label>
                    @if($staff->photo)<div class="mb-2"><img src="{{ asset($staff->photo) }}" style="width:90px;height:90px;object-fit:cover;border-radius:8px;"></div>@endif
                    <input type="file" name="photo" class="form-control-file" accept="image/*" capture="environment">
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                      <option value="Active" {{ $staff->status === 'Active' ? 'selected' : '' }}>Active</option>
                      <option value="Inactive" {{ $staff->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header"><h3 class="card-title">Assignment</h3></div>
                <div class="card-body">
                  <input type="hidden" name="category" value="{{ $staff->category ?: 'flat_staff' }}">
                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" name="is_open_to_all" class="custom-control-input" id="openToAll" value="1" {{ $staff->is_open_to_all ? 'checked' : '' }}>
                      <label class="custom-control-label" for="openToAll">Open to all flats</label>
                    </div>
                    <small class="form-text text-muted">Turn off to assign this staff to a single flat.</small>
                  </div>

                  <div id="assignmentFields">
                    <div class="form-group">
                      <label>Block</label>
                      <select id="block_id" class="form-control">
                        <option value="">Select Block</option>
                        @foreach($blocks as $block)
                          <option value="{{ $block->id }}" {{ $currentBlock == $block->id ? 'selected' : '' }}>{{ $block->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Flat</label>
                      <select name="flat_id" id="flat_id" class="form-control" data-current="{{ $currentFlat }}">
                        <option value="">Select Flat</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Engagement</label>
                      <select name="engagement_type" id="engagement_type" class="form-control">
                        <option value="In-house" {{ $currentEng === 'In-house' ? 'selected' : '' }}>In-house</option>
                        <option value="Timely-basis" {{ $currentEng === 'Timely-basis' ? 'selected' : '' }}>Timely-basis</option>
                      </select>
                    </div>
                    <div class="form-group" id="timeSlotGroup" style="{{ $currentEng === 'Timely-basis' ? '' : 'display:none;' }}">
                      <label>Time slot</label>
                      <input type="text" name="time_slot" class="form-control" value="{{ $currentSlot }}" placeholder="e.g. 8:00 AM - 10:00 AM">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card">
                <div class="card-header"><h3 class="card-title">Verification</h3></div>
                <div class="card-body">
                  <div class="form-group">
                    <label>Document (ID proof — image or PDF)</label>
                    @if($staff->document_verification)<div class="mb-1"><a href="{{ asset($staff->document_verification) }}" target="_blank">View current document</a></div>@endif
                    <input type="file" name="document" class="form-control-file" accept="image/*,application/pdf">
                  </div>
                  <div class="form-group">
                    <label>Document status</label>
                    <select name="document_status" class="form-control">
                      <option value="">— Not set —</option>
                      <option value="Pending" {{ $staff->document_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                      <option value="Verified" {{ $staff->document_status === 'Verified' ? 'selected' : '' }}>Verified</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Police NOC (optional — image or PDF)</label>
                    <input type="file" name="noc" class="form-control-file" accept="image/*,application/pdf">
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update Staff</button>
                  <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </section>
@endsection

@section('script')
<script>
$(function () {
    $('#btn_add_type').on('click', function() {
        let newType = prompt("Enter new staff type:");
        if (newType) {
            newType = newType.trim();
            if (newType.length > 0) {
                if ($('#type_select option[value="' + newType + '"]').length === 0) {
                    $('#type_select').append(new Option(newType, newType));
                }
                $('#type_select').val(newType);
            }
        }
    });

    function syncAssignment() {
        if ($('#openToAll').is(':checked')) {
            $('#assignmentFields').slideUp();
            $('#flat_id').val('');
        } else {
            $('#assignmentFields').slideDown();
        }
    }
    $('#openToAll').on('change', syncAssignment);

    function loadFlats(blockId, selectFlatId) {
        $('#flat_id').html('<option value="">Select Flat</option>');
        if (!blockId) return;
        $.ajax({
            url: '/get-flats/' + blockId,
            type: 'GET',
            success: function (res) {
                var flats = (res && res.flats) ? res.flats : [];
                $.each(flats, function (i, f) {
                    var sel = (selectFlatId && String(f.id) === String(selectFlatId)) ? ' selected' : '';
                    $('#flat_id').append('<option value="' + f.id + '"' + sel + '>' + f.name + '</option>');
                });
            }
        });
    }

    $('#block_id').on('change', function () { loadFlats($(this).val(), null); });
    $('#engagement_type').on('change', function () {
        $('#timeSlotGroup').toggle($(this).val() === 'Timely-basis');
    });

    // Prefill the current flat assignment
    // Prefill the current flat assignment
    syncAssignment();
    var initialBlock = $('#block_id').val();
    var currentFlat  = $('#flat_id').data('current');
    if (initialBlock) loadFlats(initialBlock, currentFlat);
});
</script>
@endsection
