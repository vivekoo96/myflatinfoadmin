@extends('layouts.admin')


@section('title')
    Noticeboard List
@endsection

<style>
.desc-short, .desc-full {
    font-weight: normal !important;
    font-style: normal !important;
}

.desc-toggle {
    font-weight: normal !important;
    text-decoration: none !important;
}

.desc-toggle:hover {
    text-decoration: underline !important;
}

/* Ensure table cells don't force bold text */
.table td {
    font-weight: normal !important;
}
</style>

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
                <div class="col-md-12">
                @if(session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
                @endif
                @if(empty($building) || !$building->id)
                <div class="alert alert-warning">
                  Current building context is not set, so noticeboard items cannot be displayed. Please ensure your profile is assigned to a building.
                </div>
                @endif
                @if(session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
                @endif
            </div>
          <div class="col-sm-6">
            <h1>Noticeboard List</h1>
          </div>
          <div class="col-sm-6">
            {{-- <form method="get" class="form-inline float-sm-right">
              @php $userBuildings = Auth::user() ? (method_exists(Auth::user(), 'allBuildings') ? Auth::user()->allBuildings() : Auth::user()->buildings()) : collect(); @endphp
              @if(Auth::user()->role == 'SA' || Auth::user()->role == 'SU' || (Auth::user()->role == 'BA' && count($userBuildings) > 1))
              <div class="form-group mr-2">
                <select name="building_id" class="form-control form-control-sm" onchange="this.form.submit();">
                  <option value="">Select building</option>
                  @foreach($userBuildings as $b)
                  <option value="{{$b->id}}" {{ request('building_id') == $b->id ? 'selected' : '' }}>{{$b->name}}</option>
                  @endforeach
                </select>
              </div>
              @endif
            </form> --}}
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Noticeboards</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
              <div class="card-header">
                    @if(Auth::User()->role == 'BA')
                    <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Noticeboard</button>
                    @endif
                  </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Building</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>From Time</th>
                    <th>To Time</th>
                      @if(Auth::User()->role == 'BA')
                    <th>Action</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->noticeboards as $item)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td><a href="{{route('buildings.show',$item->building_id)}}" target="_blank">{{$item->building->name}}</a></td>
                    {{-- Block column removed: defaulting to All Blocks via hidden input --}}
                    <td>
                      {{$item->title}}
                      @if(\Carbon\Carbon::parse($item->to_time)->isPast())
                        <span class="badge badge-secondary ml-1">Inactive</span>
                      @elseif(\Carbon\Carbon::parse($item->from_time)->isFuture())
                        <span class="badge badge-info ml-1">Future</span>
                      @elseif(\Carbon\Carbon::parse($item->from_time)->isToday())
                        <span class="badge badge-success ml-1">Today</span>
                      @else
                        <span class="badge badge-warning ml-1">Active</span>
                      @endif
                    </td>
                    <td>
                    @if(strlen($item->desc) > 300)
                        <span class="desc-short">{{Str::limit($item->desc, 300)}}</span>
                        <span class="desc-full d-none">{{$item->desc}}</span>
                        <a href="#" class="text-primary desc-toggle" data-id="{{$item->id}}">Show more</a>
                    @else
                        {{$item->desc}}
                    @endif
                    </td>
                    <td>{{\Carbon\Carbon::parse($item->from_time)->format('M j, Y g:i A')}}</td>
                    <td>{{\Carbon\Carbon::parse($item->to_time)->format('M j, Y g:i A')}}</td>
                    @if(Auth::User()->role == 'BA')
                    <td>
                      <!--<a href="{{route('noticeboard.show',$item->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>-->
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$item->id}}" data-title="{{$item->title}}" data-desc="{{$item->desc}}"  
                      data-from_time="{{$item->from_time}}" data-to_time="{{$item->to_time}}" data-block_ids="{{$item->blocks->pluck('id')->implode(',')}}" data-building_id="{{$item->building_id}}"><i class="fa fa-edit"></i></button>
                      @if($item->deleted_at)
                      <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$item->id}}" data-action="restore"><i class="fa fa-undo"></i></button>
                      @else
                      <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$item->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                      @endif
                    </td>
                    @endif
                  </tr>
                  @empty
                  @endforelse
                  </tbody>
                </table>
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('noticeboard.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
          <div class="modal-body">
          <div class="error">
            @if ($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
          {{-- <div class="form-group"> --}}
        {{-- <label for="name" class="col-form-label">Building:</label> --}}
            @php $userBuildings = Auth::user() ? (method_exists(Auth::user(), 'allBuildings') ? Auth::user()->allBuildings() : Auth::user()->buildings()) : collect(); @endphp
            {{-- <select name="building_id" id="building_id" class="form-control" {{ Auth::user()->role == 'BA' ? 'required' : '' }}>
                @if($userBuildings && count($userBuildings) > 0)
                  @foreach($userBuildings as $ub)
                    <option value="{{$ub->id}}" {{ (old('building_id', request('building_id', $building->id ?? null)) == $ub->id) ? 'selected' : '' }}>{{$ub->name}}</option>
                  @endforeach
                @else
                  <option value="{{$building->id}}">{{$building->name ?? 'N/A'}}</option>
                @endif
            </select> --}}
          {{-- </div> --}}
          <input type="hidden" name="building_id" id="building_id" value="{{$building->id}}">
          <div class="form-group">
            <label for="name" class="col-form-label">Title:</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" maxlength="50" placeholder="Enter title" required>
          </div>

          <div class="form-group">
            <label for="code" class="col-form-label">Description:</label>
            <textarea name="desc" class="form-control" id="desc" placeholder="Enter description" required rows="4">{{ old('desc') }}</textarea>
           
          </div>
          <input type="hidden" name="block_ids[]" value="all" id="block_ids">
          {{-- <div class="form-group">
            <label for="block_ids" class="col-form-label">Block Names: <span class="text-danger">*</span></label>
            <select name="block_ids[]" class="form-control select2-blocks" id="block_ids" multiple required>
                <option value="all">All Blocks</option>
                @foreach(Auth::User()->building->blocks()->where('status', 'Active')->get() as $block)
                <option value="{{$block->id}}" {{ (collect(old('block_ids'))->contains($block->id)) ? 'selected':'' }}>{{$block->name}}</option>
                @endforeach
            </select>
            <small class="text-muted">
              <i class="fa fa-info-circle"></i> Select "All Blocks" to send to all blocks, or choose specific blocks
              <br><span class="text-primary">You can select multiple blocks by holding Ctrl/Cmd</span>
            </small>
          </div> --}}
          
          <div class="form-group">
            <label for="code" class="col-form-label">From Time:</label>
            <input type="datetime-local" name="from_time" class="form-control" id="from_time" placeholder="From Time" value="{{ old('from_time') }}" required>
          
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">To Time:</label>
            <input type="datetime-local" name="to_time" class="form-control" id="to_time" placeholder="To Time" value="{{ old('to_time') }}" required>
            
          </div>
          <input type="hidden" name="id" id="edit-id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" id="delete-button">Confirm Delete</button>
      </div>
    </div>
  </div>
</div>


@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    
    // Fix noticeboard description textarea - completely remove all validation and enable emojis/new lines
    setTimeout(function() {
        var $desc = $('#desc');
        
        // Remove ALL event handlers that might interfere
        $desc.off('input').off('keydown').off('keypress').off('keyup').off('paste').off('cut');
        
        // Remove all restrictive attributes
        $desc.removeAttr('maxlength').removeAttr('pattern').removeAttr('data-validation');
        
        // Mark this textarea as completely free from validation
        $desc.attr('data-no-validation', 'true').attr('data-allow-emojis', 'true');
        
        // Add completely unrestricted event handling
        $desc.on('keydown', function(e) {
            // Allow ALL keys including Enter for new lines
            return true;
        });
        
        $desc.on('input', function(e) {
            // Allow ALL input including emojis and special characters
            return true;
        });
        
        $desc.on('paste', function(e) {
            // Allow all pasting including emojis
            return true;
        });
        
        // Override any global validation that might try to interfere
        $desc[0].addEventListener('input', function(e) {
            e.stopImmediatePropagation();
        }, true);
        
        $desc[0].addEventListener('keydown', function(e) {
            e.stopImmediatePropagation();
        }, true);
        
        console.log('Noticeboard description textarea completely unrestricted - emojis and new lines enabled');
    }, 200);
    
    // Handle description show more/less functionality
    $(document).on('click', '.desc-toggle', function(e) {
        e.preventDefault();
        var $toggle = $(this);
        var $short = $toggle.siblings('.desc-short');
        var $full = $toggle.siblings('.desc-full');
        
        if ($full.hasClass('d-none')) {
            // Show full content
            $short.addClass('d-none');
            $full.removeClass('d-none');
            $toggle.text('Show less');
        } else {
            // Show short content
            $short.removeClass('d-none');
            $full.addClass('d-none');
            $toggle.text('Show more');
        }
    });
    
    // Block selection removed from UI. Keep hidden input defaulting to 'all'.
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      if(action == 'delete'){
          $('#delete-button').addClass('btn-danger');
          $('#delete-button').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item..');
      }else{
          $('#delete-button').addClass('btn-success');
          $('#delete-button').text('Confirm Restore');
          $('.text').text('You are going to restore this item..');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "{{route('noticeboard.destroy','')}}";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

      $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');

      // Clear fields first unless we have old input from validation errors
      var hasOldInput = {{ $errors->any() ? 'true' : 'false' }};
      var edit_id = button.data('id');
      
      if (!hasOldInput) {
        $('#edit-id').val('');
        $('#title').val('');
        $('#desc').val('');
        // Keep block_ids defaulting to 'all' (hidden input). Do NOT clear to null —
        // clearing produced an empty string which caused a DB insert with '' block_id.
        $('#block_ids').val('all');
        // Only clear datetime fields if NOT editing; for creation, let user fill them in
        if (!edit_id) {
          $('#from_time').val('');
          $('#to_time').val('');
        }
        // Set default building selection for creation
        $('#building_id').val('{{ request('building_id', $building->id ?? '') }}');
      }
      $('.modal-title').text('Add New Noticeboard');

      // Remove all min constraints - allow any date/time selection
      // Validation will happen on submit only
      $('#from_time').removeAttr('min');
      $('#to_time').removeAttr('min');

      // If editing, populate fields
      if(edit_id) {
        $('#edit-id').val(button.data('id'));
        $('#title').val(button.data('title'));
        $('#desc').val(button.data('desc'));

        // We no longer expose block selection in the UI; always default to 'all'
        $('#block_ids').val('all');

        // Populate datetime values - convert from server format to datetime-local format
        var fromVal = button.data('from_time');
        var toVal = button.data('to_time');
        
        if (fromVal) {
          // Server sends: 2025-12-09 14:30:00 or 2025-12-09T14:30:00
          // datetime-local expects: 2025-12-09T14:30
          var cleanFrom = String(fromVal).replace(' ', 'T').substring(0, 16);
          $('#from_time').val(cleanFrom);
          originalFromDate = cleanFrom; // Store original for validation
        }
        if (toVal) {
          var cleanTo = String(toVal).replace(' ', 'T').substring(0, 16);
          $('#to_time').val(cleanTo);
          originalToDate = cleanTo; // Store original for validation
        }
        // populate building_id if present (for SA or BA with selection)
        var buildingId = button.data('building_id');
        if (buildingId) { $('#building_id').val(buildingId); }
        $('.modal-title').text('Update Noticeboard');
      } else {
        // Reset original values when creating new
        originalFromDate = null;
        originalToDate = null;
        isUserInteracting = false;
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-building-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

        // Validate date and time fields (prevent past selections and ensure to_time > from_time)
        function isPast(dt) {
          var now = new Date();
          // Check if the selected date/time is in the past (including today's past times)
          return dt < now;
        }

        function isPastOrTodayPastTime(dt) {
          var now = new Date();
          var selectedDate = new Date(dt);
          
          console.log('Validation Check:', {
            selectedDateTime: selectedDate,
            currentDateTime: now,
            isSelectedPast: selectedDate < now,
            selectedString: selectedDate.toString(),
            currentString: now.toString()
          });
          
          // Get today's date at midnight for comparison
          var todayMidnight = new Date();
          todayMidnight.setHours(0, 0, 0, 0);
          
          // Get tomorrow's date at midnight for comparison
          var tomorrowMidnight = new Date(todayMidnight);
          tomorrowMidnight.setDate(tomorrowMidnight.getDate() + 1);
          
          // Check if it's a past date (before today)
          if (selectedDate < todayMidnight) {
            return true; // Past date - not allowed
          }
          
          // If it's today, only allow future times
          if (selectedDate >= todayMidnight && selectedDate < tomorrowMidnight) {
            return selectedDate <= now; // Past or current time on today's date
          }
          
          // Future dates are always allowed
          return false; // Future date - allowed
        }

        // Store original date values when editing starts
        var originalFromDate = null;
        var originalToDate = null;
        var isUserInteracting = false;

        $('#from_time, #to_time').on('change', function() {
          isUserInteracting = true; // Mark that user is interacting
          
          // Only validate on submit, not on change
          // Allow any date/time selection in the calendar
          console.log('Date changed, will validate on submit');
        });

        function validateNewDates() {
          // No validation on change - only on submit
          console.log('Date selection allowed, will validate on submit');
        }

        // Ensure dates are validated on submit (covers programmatic or create values)
        $('.add-form').on('submit', function(e) {
          var isEdit = $('#edit-id').val();
          var fromVal = $('#from_time').val();
          var toVal = $('#to_time').val();
          var now = new Date();

          // Log what's being submitted for debugging
          console.log('Form submission - From Time:', fromVal);
          console.log('Form submission - To Time:', toVal);

          // Ensure both from_time and to_time have values before submitting
          if (!fromVal) {
            e.preventDefault();
            alert('From Time is required.');
            return false;
          }

          if (!toVal) {
            e.preventDefault();
            alert('To Time is required.');
            return false;
          }

          // For creation, validate normally
          if (!isEdit) {
            if (fromVal) {
              var f = new Date(fromVal);
              if (f < now) {
                e.preventDefault();
                alert('From date and time cannot be in the past.');
                return false;
              }
            }

            if (toVal) {
              var t = new Date(toVal);
              if (t < now) {
                e.preventDefault();
                alert('To date and time cannot be in the past.');
                return false;
              }
            }
          }

          // For editing, prevent past dates and times only if changed
          if (isEdit) {
            if (fromVal && fromVal !== originalFromDate) {
              var f = new Date(fromVal);
              if (isPastOrTodayPastTime(f)) {
                e.preventDefault();
                alert('Cannot select a past date and time. Please select a future date and time.');
                return false;
              }
            }

            if (toVal && toVal !== originalToDate) {
              var t = new Date(toVal);
              if (isPastOrTodayPastTime(t)) {
                e.preventDefault();
                alert('Cannot select a past date and time. Please select a future date and time.');
                return false;
              }
            }
          }

          // Always validate that to_time is after from_time
          if (fromVal && toVal) {
            var fromDate = new Date(fromVal);
            var toDate = new Date(toVal);
            if (toDate <= fromDate) {
              e.preventDefault();
              alert('To date and time must be after from date and time.');
              return false;
            }
          }

          return true;
        });

  });
</script>
@if ($errors->any())
<script>
  $(document).ready(function(){
    // Show modal if validation errors occurred
    $('#addModal').modal('show');
    // If old id present, set edit id
    var oldId = '{{ old('id') }}';
    if(oldId) {
      $('#edit-id').val(oldId);
    }
    var oldBlockIds = @json(old('block_ids', []));
    if(oldBlockIds && oldBlockIds.length > 0) {
      $('#block_ids').val(oldBlockIds).trigger('change');
    }
    // Restore old datetime values - they come from the form submission
    // Format should be: YYYY-MM-DDTHH:MM or YYYY-MM-DDTHH:MM:SS
    var oldFrom = '{{ old('from_time') }}';
    var oldTo = '{{ old('to_time') }}';
    if(oldFrom) {
      // Ensure format is datetime-local compatible (YYYY-MM-DDTHH:MM)
      var cleanFrom = String(oldFrom).replace(' ', 'T').substring(0, 16);
      $('#from_time').val(cleanFrom);
    }
    if(oldTo) {
      var cleanTo = String(oldTo).replace(' ', 'T').substring(0, 16);
      $('#to_time').val(cleanTo);
    }
  });
</script>
@endif
@endsection

@endsection


