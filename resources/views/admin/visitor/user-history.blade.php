@extends('layouts.admin')

@section('title')
    User Visit History
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>
          <i class="fa fa-history"></i> User Visit History
          @if($userInfo)
            <small class="text-muted">- {{ $userInfo->head_name }}</small>
          @endif
        </h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ url('visitor') }}">Visitors</a></li>
          <li class="breadcrumb-item active">User History</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- User Info Card -->
@if($userInfo)
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header" style="background-color: #EBF6F8; color: #333;">
            <h3 class="card-title">
              <i class="fa fa-user"></i> User Information
            </h3>
            <div class="card-tools">
              <a href="{{ url('visitor') }}" class="btn btn-sm btn-light">
                <i class="fa fa-arrow-left"></i> Back to Visitors
              </a>
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-2 text-center">
                @if($userInfo->head_photo)
                  <img src="{{ $userInfo->head_photo }}" class="img-thumbnail" style="width:120px; height:120px; object-fit:cover;" alt="Photo">
                @else
                  <div class="bg-secondary text-white text-center" style="width:120px; height:120px; line-height:120px; border-radius:8px; margin:0 auto;">
                    <i class="fa fa-user fa-3x"></i>
                  </div>
                @endif
              </div>
              <div class="col-md-10">
                <div class="row">
                  <div class="col-md-6">
                    <table class="table table-borderless">
                      <tr>
                        <td><strong><i class="fa fa-user"></i> Name:</strong></td>
                        <td>{{ $userInfo->head_name }}</td>
                      </tr>
                      <tr>
                        <td><strong><i class="fa fa-phone"></i> Mobile:</strong></td>
                        <td>{{ $userInfo->head_phone }}</td>
                      </tr>
                      <tr>
                        <td><strong><i class="fa fa-calendar"></i> Total Visits:</strong></td>
                        <td><span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">{{ $totalVisits }}</span></td>
                      </tr>
                    </table>
                  </div>
                  <div class="col-md-6">
                    <table class="table table-borderless">
                      <tr>
                        <td><strong><i class="fa fa-clock"></i> Last Visit:</strong></td>
                        <td>{{ $lastVisit ? $lastVisit->created_at->format('M j, Y g:i A') : 'Never' }}</td>
                      </tr>
                      <tr>
                        <td><strong><i class="fa fa-check-circle"></i> Completed Visits:</strong></td>
                        <td><span class="badge badge-success">{{ $completedVisits }}</span></td>
                      </tr>
                      <tr>
                        <td><strong><i class="fa fa-clock"></i> Ongoing Visits:</strong></td>
                        <td><span class="badge badge-warning">{{ $ongoingVisits }}</span></td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endif

<!-- Visit History -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header" style="background-color: #EBF6F8; color: #333;">
            <h3 class="card-title">
              <i class="fa fa-list"></i> Complete Visit History
              @if($phone)
                <small class="badge badge-light ml-2">{{ $phone }}</small>
              @endif
            </h3>
            <div class="card-tools">
              <div class="input-group input-group-sm" style="width: 300px;">
                <input type="text" id="searchTable" class="form-control float-right" placeholder="Search visits...">
                <div class="input-group-append">
                  <button type="button" class="btn" style="background-color: #EBF6F8; color: #333; border-color: #ddd;">
                    <i class="fa fa-search"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if($visits->count() > 0)
            <div class="table-responsive">
              <table id="historyTable" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Visit Date</th>
                    <th>Purpose</th>
                    <th>Flat Visited</th>
                    <th>Entry Time</th>
                    <th>Exit Time</th>
                    <th>Duration</th>
                    <th>Total Members</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($visits as $visit)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                      <strong>{{ $visit->created_at->format('M j, Y') }}</strong><br>
                      <small class="text-muted">{{ $visit->created_at->format('l') }}</small>
                    </td>
                    <td>
                      <span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">{{ $visit->visiting_purpose }}</span>
                    </td>
                    <td>
                      @if($visit->flat)
                        <strong>{{ $visit->flat->name }}</strong><br>
                        <small class="text-muted">{{ $visit->flat->block->name ?? 'N/A' }}</small>
                      @else
                        <span class="text-muted">N/A</span>
                      @endif
                    </td>
                    <td>
                      @if($visit->created_at)
                        <span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">
                          {{ $visit->created_at->format('M j, g:i A') }}
                        </span>
                      @else
                        <span class="badge badge-secondary">Not entered</span>
                      @endif
                    </td>
                    <td>
                      @if($visit->updated_at && $visit->updated_at != $visit->created_at)
                        <span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">
                          {{ $visit->updated_at->format('M j, g:i A') }}
                        </span>
                      @else
                        <span class="badge badge-warning">Not exited</span>
                      @endif
                    </td>
                    <td>
                      @if($visit->created_at && $visit->updated_at && $visit->updated_at != $visit->created_at)
                        @php
                          $entry = $visit->created_at;
                          $exit = $visit->updated_at;
                          $duration = $entry->diff($exit);
                        @endphp
                        <span class="badge badge-secondary">
                          {{ $duration->h }}h {{ $duration->i }}m
                        </span>
                      @elseif($visit->created_at)
                        <span class="badge badge-warning">Ongoing</span>
                      @else
                        <span class="text-muted">N/A</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">{{ $visit->total_members }}</span>
                    </td>
                    <td>
                      @if($visit->vehicle_number)
                        <span class="badge" style="background-color: #EBF6F8; color: #333; border: 1px solid #ddd;">{{ $visit->vehicle_number }}</span><br>
                        <small class="badge badge-light">{{ $visit->vehicle_type }}</small>
                      @else
                        <span class="badge badge-secondary">No Vehicle</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $statusClass = 'secondary';
                        switch($visit->status) {
                          case 'CheckedIn':
                            $statusClass = 'success';
                            break;
                          case 'CheckedOut':
                            $statusClass = 'info';
                            break;
                          case 'AllowIn':
                            $statusClass = 'primary';
                            break;
                          case 'Expired':
                            $statusClass = 'warning';
                            break;
                        }
                      @endphp
                      <span class="badge badge-{{ $statusClass }}">{{ $visit->status }}</span>
                    </td>
                    <td>
                      <a href="{{ route('visitor.show', $visit->id) }}" target="_blank" class="btn btn-sm btn-info" title="View Details">
                        <i class="fa fa-eye"></i>
                      </a>
                      <button class="btn btn-sm btn-primary view-timeline" data-id="{{ $visit->id }}" title="View Timeline">
                        <i class="fa fa-clock"></i>
                      </button>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center">
              {{ $visits->links() }}
            </div>
            @else
            <div class="text-center py-5">
              <i class="fa fa-history fa-3x text-muted mb-3"></i>
              <h4 class="text-muted">No Visit History Found</h4>
              <p class="text-muted">This user has no recorded visits yet.</p>
              <a href="{{ url('visitor') }}" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> Back to Visitors
              </a>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Timeline Modal -->
<div class="modal fade" id="timelineModal" tabindex="-1" role="dialog" aria-labelledby="timelineModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="timelineModalLabel">
          <i class="fa fa-clock"></i> Visit Timeline
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="timelineContent">
        <!-- Timeline content will be loaded here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function(){
  var token = "{{ csrf_token() }}";
  
  // Initialize DataTable
  $('#historyTable').DataTable({
    "responsive": true,
    "lengthChange": true,
    "autoWidth": false,
    "ordering": true,
    "info": true,
    "paging": true,
    "pageLength": 25,
    "order": [[ 1, "desc" ]], // Sort by visit date descending
    "columnDefs": [
      { "orderable": false, "targets": [10] } // Disable sorting for Actions column
    ]
  });
  
  // Search functionality
  $('#searchTable').on('keyup', function() {
    $('#historyTable').DataTable().search(this.value).draw();
  });
  
  // View timeline
  $(document).on('click', '.view-timeline', function() {
    var visitId = $(this).data('id');
    
    $.ajax({
      url: "{{ url('visitor') }}/" + visitId + "/timeline",
      type: "GET",
      data: { _token: token },
      success: function(response) {
        if (response.success) {
          populateTimeline(response.data);
          $('#timelineModal').modal('show');
        } else {
          alert('Error loading timeline');
        }
      },
      error: function() {
        alert('Error loading timeline');
      }
    });
  });
  
  // Populate timeline
  function populateTimeline(visit) {
    var timeline = '<div class="timeline">';
    
    // Visit created
    timeline += '<div class="time-label">' +
      '<span class="bg-primary">' + formatDate(visit.created_at) + '</span>' +
    '</div>';
    
    timeline += '<div>' +
      '<i class="fa fa-plus bg-blue"></i>' +
      '<div class="timeline-item">' +
        '<span class="time"><i class="fa fa-clock"></i> ' + formatTime(visit.created_at) + '</span>' +
        '<h3 class="timeline-header">Visit Registered</h3>' +
        '<div class="timeline-body">' +
          'Purpose: ' + visit.visiting_purpose + '<br>' +
          'Total Members: ' + visit.total_members +
        '</div>' +
      '</div>' +
    '</div>';
    
    // Entry time
    if (visit.entry_time) {
      timeline += '<div>' +
        '<i class="fa fa-sign-in-alt bg-green"></i>' +
        '<div class="timeline-item">' +
          '<span class="time"><i class="fa fa-clock"></i> ' + formatTime(visit.entry_time) + '</span>' +
          '<h3 class="timeline-header">Guest Entered</h3>' +
          '<div class="timeline-body">Guest checked in to the premises</div>' +
        '</div>' +
      '</div>';
    }
    
    // Exit time
    if (visit.exit_time) {
      timeline += '<div>' +
        '<i class="fa fa-sign-out-alt bg-yellow"></i>' +
        '<div class="timeline-item">' +
          '<span class="time"><i class="fa fa-clock"></i> ' + formatTime(visit.exit_time) + '</span>' +
          '<h3 class="timeline-header">Guest Exited</h3>' +
          '<div class="timeline-body">Guest checked out from the premises</div>' +
        '</div>' +
      '</div>';
    }
    
    timeline += '<div><i class="fa fa-clock bg-gray"></i></div>';
    timeline += '</div>';
    
    $('#timelineContent').html(timeline);
  }
  
  // Helper functions
  function formatDate(dateTime) {
    var date = new Date(dateTime);
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    });
  }
  
  function formatTime(dateTime) {
    var date = new Date(dateTime);
    return date.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  }
});
</script>

<style>
.timeline {
  position: relative;
  margin: 0 0 30px 0;
  padding: 0;
  list-style: none;
}

.timeline:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 4px;
  background: #ddd;
  left: 31px;
  margin: 0;
  border-radius: 2px;
}

.timeline > div {
  position: relative;
  margin: 0 0 15px 0;
}

.timeline > div > .timeline-item {
  box-shadow: 0 1px 1px rgba(0,0,0,0.1);
  border-radius: 3px;
  margin-top: 0;
  background: #fff;
  color: #444;
  margin-left: 60px;
  margin-right: 15px;
  padding: 0;
  position: relative;
}

.timeline > div > .fa {
  width: 30px;
  height: 30px;
  font-size: 15px;
  line-height: 30px;
  position: absolute;
  color: #666;
  background: #d2d6de;
  border-radius: 50%;
  text-align: center;
  left: 18px;
  top: 0;
}

.timeline > div > .timeline-item > .time {
  color: #999;
  float: right;
  padding: 10px;
  font-size: 12px;
}

.timeline > div > .timeline-item > .timeline-header {
  margin: 0;
  color: #555;
  border-bottom: 1px solid #f4f4f4;
  padding: 10px;
  font-size: 16px;
  line-height: 1.1;
}

.timeline > div > .timeline-item > .timeline-body {
  padding: 10px;
}

.time-label > span {
  font-weight: 600;
  padding: 5px 10px;
  display: inline-block;
  background-color: #fff;
  border-radius: 4px;
}
</style>
@endsection
