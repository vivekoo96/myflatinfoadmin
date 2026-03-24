@extends('layouts.admin')


@section('title')
    Classified List
@endsection


  <style>
  .readonly-select {
    background-color: #f4f4f4 !important;
    pointer-events: none;   /* makes select unclickable */
    cursor: not-allowed;
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
                @if(session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
                @endif
                        <?php $i = 0; ?>
          <div class="col-sm-6">
            <h1>Classified List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Classifieds</li>
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
                    @php
                      try {
                        $__now = \Carbon\Carbon::now();
                        $__within_span = intval($building->within_for_month ?? 1);
                        $__all_span = intval($building->all_for_month ?? 1);
                        $__within_span = max(1, $__within_span);
                        $__all_span = max(1, $__all_span);
                        $__start_within = $__now->copy()->subMonths($__within_span - 1)->startOfMonth();
                        
                        $__start_all = $__now->copy()->subMonths($__all_span - 1)->startOfMonth();
                        $__end = $__now->copy()->endOfMonth();

                        // SIMPLE COUNT LOGIC - PER-USER APPROVED CLASSIFIEDS
                        if (!isset($within_used_user)) {
                          $within_used_user = \App\Models\Classified::where('building_id', $building->id)
                            ->where('user_id', Auth::id())
                            ->where('category', 'Within Building')
                            ->where('status', 'Approved')
                            ->where('is_approved_on_creation', true)
                            ->whereBetween('created_at', [$__start_within, $__end])
                            ->count();
                        }

                        if (!isset($all_used_user)) {
                          $all_used_user = \App\Models\Classified::where('building_id', $building->id)
                            ->where('user_id', Auth::id())
                            ->where('category', 'All Buildings')
                            ->where('status', 'Approved')
                            ->where('is_approved_on_creation', true)
                            ->whereBetween('created_at', [$__start_all, $__end])
                            ->count();
                        }

                        // CALCULATE PER-USER REMAINING QUOTA (each user has individual limit)
                        if (!isset($within_remaining)) {
                          if (isset($building->classified_limit_within_building) && $building->classified_limit_within_building !== null) {
                            $limit = intval($building->classified_limit_within_building);
                            $within_remaining = max(0, $limit - $within_used_user);
                          } else {
                            $within_remaining = null; // unlimited
                          }
                        }

                        if (!isset($all_remaining)) {
                          if (isset($building->classified_limit_all_building) && $building->classified_limit_all_building !== null) {
                            $limit = intval($building->classified_limit_all_building);
                            $all_remaining = max(0, $limit - $all_used_user);
                          } else {
                            $all_remaining = null; // unlimited
                          }
                        }

                        $within_exhausted = ($within_remaining === 0);
                        $all_exhausted = ($all_remaining === 0);
                      } catch (\Exception $e) {
                        // If anything goes wrong while computing counts, default to not exhausting quotas
                        // so the Add button remains visible and users are not blocked by view errors.
                        $within_remaining = $within_remaining ?? null;
                        $all_remaining = $all_remaining ?? null;
                        $within_used_user = $within_used_user ?? 0;
                        $all_used_user = $all_used_user ?? 0;
                        $within_exhausted = false;
                        $all_exhausted = false;
                      }
                    @endphp
              @if(Auth::User()->role == 'BA')
              <div class="card-header">
                 @if(Auth::User()->building && (Auth::User()->building->hasPermission('Classified for withinbuilding') || Auth::User()->building->hasPermission('Classified for all buildings')))
                <button id="add-classified-btn" class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal" {{ ($within_exhausted && $all_exhausted) ? 'disabled' : '' }}>Add New Classified</button>
                @endif
                @if(Auth::User()->building && Auth::User()->building->hasPermission('Classified for withinbuilding'))
                <div class="float-right px-3">
                  <small class="text-muted">Within: @if($within_remaining === null) Unlimited @else {{$within_remaining}} left @endif @if(isset($within_used_user)) <span class="text-primary"> @endif</small>
                  @if(Auth::User()->building->hasPermission('Classified for all buildings'))
                  &nbsp;|&nbsp;
                  <small class="text-muted">All: @if($all_remaining === null) Unlimited @else {{$all_remaining}} left @endif @if(isset($all_used_user)) <span class="text-primary"> @endif</small>
                  @endif
                </div>
                @elseif(Auth::User()->building && Auth::User()->building->hasPermission('Classified for all buildings'))
                <div class="float-right px-3">
                  <small class="text-muted">All: @if($all_remaining === null) Unlimited @else {{$all_remaining}} left @endif @if(isset($all_used_user)) <span class="text-primary"> @endif</small>
                </div>
                @endif
              </div>
              @endif
             {{-- <div class="card-header">
                 @if(Auth::User()->building && (Auth::User()->building->hasPermission('Classified for withinbuilding') || Auth::User()->building->hasPermission('Classified for all buildings')))
                <button id="add-classified-btn" class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal" {{ ($within_exhausted && $all_exhausted) ? 'disabled' : '' }}>Add New Classified</button>
                @endif
                <div class="float-right px-3">
                  <small class="text-muted">Within: @if($within_remaining === null) Unlimited @else {{$within_remaining}} left @endif @if(isset($within_used_user)) <span class="text-primary"> @endif</small>
                  &nbsp;|&nbsp;
                  <small class="text-muted">All: @if($all_remaining === null) Unlimited @else {{$all_remaining}} left @endif @if(isset($all_used_user)) <span class="text-primary"> @endif</small>
                </div>
              </div> --}}
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped table-sm">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Posted By</th>
                    <th>Building</th>
                    <!--<th>Block</th>-->
                    <th>Category</th>
                    <th>Title</th>
                    <!--<th>Image</th>-->
                    <th>Desc</th>
                    <th>Status</th>
                    @if(Auth::User()->role == 'BA')
                    <th>Action</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->classifieds as $item)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>
                      @if($item->user)
                        <a href="{{url('customer',$item->user_id)}}">{{$item->user->name}}</a>
                      @else
                        <span class="text-muted">User Deleted</span>
                      @endif
                    </td>
                    <td>
                      @if($item->building_id == 0)
                        <span class="badge badge-info">Super Admin</span>
                      @elseif($item->building)
                        <a href="{{route('buildings.show',$item->building_id)}}">{{$item->building->name}}</a>
                      @else
                        <span class="text-muted">Building Deleted</span>
                      @endif
                    </td>
                   {{-- <td>
                      @if($item->block)
                        {{$item->block->name}}
                      @elseif($item->block_id === 0 || $item->block_id === null)
                        <span class="badge badge-info">All Blocks</span>
                      @else
                        <span class="text-muted">N/A</span>
                      @endif
                    </td> --}}
                    <td>{{$item->category}}</td>
                    <td>{{$item->title}}</td>
                    <!--<td>-->
                   
                    <!--   @if($item->photos->first() && $item->photos->first()->photo)-->
                      
                    <!--    <a href="{{$item->photos->first()->photo}}" target="_blank" style="text-decoration: underline;">-->
                    <!--        View Image-->
                    <!--    </a>-->
                    <!--@else-->
                    <!--    <span class="text-muted">No Image</span>-->
                    <!--@endif-->
                     
                    <!--</td>-->
                    <td>{{$item->desc}}</td>
                    <td>{{$item->status}}</td>
                     @if(Auth::User()->role == 'BA')
                    <td>
                      <a href="{{route('classified.show',$item->id)}}"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                      @if($item->building_id != 0)
                      @if(Auth::user()->building_id == $item->building_id)
                      <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$item->id}}" data-title="{{$item->title}}" data-desc="{{$item->desc}}"  
                       data-status="{{$item->status}}" data-building_id="{{$item->building_id}}" data-reason="{{$item->reason}}" 
                      data-block_id="{{$item->block_id}}" data-category="{{$item->category}}" data-user_id="{{$item->user_id}}"><i class="fa fa-edit"></i></button>
                      @endif
                      @if($item->deleted_at)
                        @if(Auth::user()->building_id == $item->building_id)
                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$item->id}}" data-action="delete"><i class="fa fa-trash"></i></button>
                    
                        @endif
                      @endif
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
        <h5 class="modal-title" id="exampleModalLabel">Add Classified</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('classified.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <!--<input type="hidden" name="id" id="edit-id" value="">-->
           <input type="hidden" name="building_id" id="building_id" value="{{$building->id}}">
         
          {{-- <div class="form-group">
            <label for="name" class="col-form-label">Block:</label>
            <select name="block_id" id="block_id" class="form-control" required>
                <option value="">Select Block</option>
                <option value="all">All Blocks</option>
                @forelse($building->blocks as $block)
                <option value="{{$block->id}}">{{$block->name}}</option>
                @empty
                @endforelse
            </select>
          </div> --}}
          <div class="form-group">
            <label for="name" class="col-form-label">Category:</label>
            <select name="category" id="category" class="form-control" required>
              <option value="">-- Select Category --</option>
              <option value="Within Building"
                    data-remaining="{{ $within_remaining === null ? -1 : $within_remaining }}"
                    data-permission="{{ Auth::User()->building->hasPermission('Classified for withinbuilding') ? 'true' : 'false' }}"
                >Within Building</option>

                <option value="All Buildings"
                    data-remaining="{{ $all_remaining === null ? -1 : $all_remaining }}"
                    data-permission="{{ Auth::User()->building->hasPermission('Classified for all buildings') ? 'true' : 'false' }}"
                >All Buildings</option>
            </select>
            <small id="limit-help" class="form-text text-muted"></small>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Title:</label>
            <input type="text" name="title" id="title" class="form-control" min="3" max="30" placeholder="Title" minlength="4" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Desc:</label>
            <textarea name="desc" id="desc" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="image" class="col-form-label">Images (Optional):</label>
            <input type="file" name="photos[]" id="image" class="form-control" multiple accept="image/*">
          </div>
           <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                @if(Auth::User()->role == 'BA')
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Send For Editing">Send For Editing</option>
                 
                @endif
            </select>
          </div>
          <!-- CRITICAL: This hidden field MUST be populated when editing existing classifieds -->
          <!-- When BA approves a user's post, this user_id ensures quota is credited to original poster, not BA -->
          <input type="hidden" name="user_id" id="user_id" value="">
          
          <!-- Hidden Reason field -->
        <div class="form-group" id="reasonBox" style="display: none;">
          <label for="reason" class="col-form-label">Reason:</label>
          <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
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
    const LOGGED_IN_USER_ID = {{ Auth::id() }};
</script>

<script>
 
  $(document).ready(function(){

    var id = '';
    var action = '';
    var token = "{{ csrf_token() }}";

    /* ================= DELETE / RESTORE ================= */

    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      action = button.data('action');

      $('#delete-button').removeClass('btn-success btn-danger');

      if(action === 'delete'){
          $('#delete-button').addClass('btn-danger').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item.');
      } else {
          $('#delete-button').addClass('btn-success').text('Confirm Restore');
          $('.text').text('You are going to restore this item.');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "{{ route('classified.destroy','') }}/" + id;

      $.ajax({
        url : url,
        type: "DELETE",
        data : {_token: token, action: action},
        success: function(){
          window.location.reload();
        }
      });
    });

    /* ================= ADD / EDIT MODAL ================= */

    $('#addModal').on('show.bs.modal', function (event) {

      var button = $(event.relatedTarget);
      var edit_id = button.data('id') || '';
      var classifiedUserId = parseInt(button.data('user_id')) || null;
      var isOwner = (classifiedUserId === LOGGED_IN_USER_ID);
            var currentStatus = button.data('status') || '';
        $('#status option').show().prop('disabled', false);
        
            @if(Auth::user()->role == 'BA')
                if (edit_id && currentStatus === 'Approved') {
                    // remove unwanted options
                    $('#status option[value="Rejected"]').remove();
                    $('#status option[value="Send For Editing"]').remove();
        
                    // ensure Approved stays selected
                    $('#status').val('Approved');
                }
            @endif

  $('#status option').prop('disabled', false).hide();

    if (!edit_id) {
        // ================= ADD MODE =================
        $('#status option[value="Approved"]').show();
        $('#status').val('Approved');

        // disable others completely
        $('#status option[value!="Approved"]').prop('disabled', true);

    } else {
        // ================= EDIT MODE =================
        $('#status option').show();

        // set existing status
        $('#status').val(button.data('status'));
    }
      // reset
      $('.add-form')[0].reset();
      $('#edit-id').val(edit_id);
      $('#user_id').val(classifiedUserId || '');
      $('#limit-help').text('').removeClass('text-danger');
      $('#save-button').prop('disabled', false);

      $('.modal-title').text(edit_id ? 'Update Classified' : 'Add New Classified');

      /* ---------------- STATUS FIX ---------------- */
      if (edit_id) {
        $('#status').val(button.data('status'));
      } else {
        $('#status').val('Pending'); // ✅ ADD DEFAULT
      }

      $('#title').val(button.data('title') || '');
      $('#desc').val(button.data('desc') || '');
      $('#reason').val(button.data('reason') || '');

      toggleReason($('#status').val());

      /* ---------------- CATEGORY ---------------- */
      var cat = button.data('category');
      if (cat) {
        $('#category').val(cat).data('original', cat);
      }

      /* ================= OWNER CHECK ================= */

      if (edit_id && !isOwner) {

        // ✅ READONLY (NOT DISABLED)
        $('#title, #desc').prop('readonly', true);
        $('#image').prop('disabled', true); // file input can't be readonly

        $('#category')
          .addClass('readonly-select')
          .data('locked', true);

        $('#limit-help')
          .text('You are not the owner. Only status / reason can be changed.')
          .addClass('text-danger');

      } else {

        // ✅ ENABLE FOR OWNER / CREATE
        $('#title, #desc').prop('readonly', false);
        $('#image').prop('disabled', false);

        $('#category')
          .removeClass('readonly-select')
          .data('locked', false);
      }

      /* ---------------- CREATE CATEGORY AVAILABILITY ---------------- */

      if (!edit_id) {
        var enabledOptions = [];

        $('#category option').each(function(){
          var val = $(this).val();
          if (!val) return;

          var rem = Number($(this).data('remaining'));
          var perm = String($(this).data('permission')) === 'true';

          if (perm && (isNaN(rem) || rem === -1 || rem > 0)) {
            $(this).prop('disabled', false);
            enabledOptions.push(val);
          } else {
            $(this).prop('disabled', true);
          }
        });

        if (enabledOptions.length === 1) {
          $('#category').val(enabledOptions[0]).trigger('change');
        } else if (enabledOptions.length === 0) {
          $('#limit-help').text('No categories available to post.');
          $('#save-button').prop('disabled', true);
        }
      }
    });

    /* ================= BLOCK CATEGORY CHANGE (LOCK) ================= */

    $(document).on('change', '#category', function (e) {
      if ($(this).data('locked') === true) {
        e.preventDefault();
        $(this).val($(this).data('original'));
        return false;
      } else {
        $(this).data('original', $(this).val());
      }
    });

    /* ================= STATUS → REASON ================= */

    $(document).on('change', '#status', function () {
      toggleReason($(this).val());
    });

    function toggleReason(status) {
      if (status === 'Rejected' || status === 'Send For Editing') {
        $('#reasonBox').show();
        $('#reason').attr('required', true);
      } else {
        $('#reasonBox').hide();
        $('#reason').removeAttr('required');
      }
    }

    /* ================= CATEGORY LIMIT TEXT ================= */

    $(document).on('change', '#category', function () {
      if ($(this).data('locked') === true) return;

      var opt = $(this).find('option:selected');
      var rem = parseInt(opt.data('remaining'));
      var perm = String(opt.data('permission'));

      if (perm === 'false') {
        $('#limit-help').text('You do not have permission.');
        $('#save-button').prop('disabled', true);
      } else if (isNaN(rem) || rem === -1) {
        $('#limit-help').text('Unlimited posts allowed.');
        $('#save-button').prop('disabled', false);
      } else if (rem === 0) {
        $('#limit-help').text('Monthly limit reached.');
        $('#save-button').prop('disabled', true);
      } else {
        $('#limit-help').text(rem + ' post(s) left this month.');
        $('#save-button').prop('disabled', false);
      }
    });

  });
</script>



@endsection

@endsection


