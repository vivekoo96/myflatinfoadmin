@extends('layouts.admin')

@section('title')
    Move-In / Move-Out Management
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Move-In / Move-Out</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Move-In / Move-Out</li>
        </ol>
      </div>
    </div>
  </div>
</section>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  {{ session('success') }}
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  {{ session('error') }}
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
@endif

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">Move-In / Move-Out Requests</h5>
            <div class="card-tools">
              @if(Auth::User()->role == 'BA')
              <a href="{{ route('move-in-out.create') }}" class="btn btn-sm btn-success">
                <i class="fa fa-plus"></i> Create Move-In Pass
              </a>
              @endif
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Type</th>
                    <th>Person</th>
                    <th>Flat</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Passcode</th>
                    <th>Dues</th>
                    <th>Status</th>
                    <th>Comment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($requests as $req)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                      <span class="badge badge-{{ $req->type == 'Move-In' ? 'primary' : 'info' }}">
                        {{ $req->type }}
                      </span>
                    </td>
                    <td>{{ $req->person_type }}</td>
                    <td>{{ $req->flat->name }}</td>
                    <td>{{ $req->user ? $req->user->name : ($req->first_name . ' ' . $req->last_name) }}</td>
                    <td>{{ $req->user ? $req->user->phone : $req->phone }}</td>
                    <td>{{ date('d-m-Y', strtotime($req->date_of_entry_exit)) }}</td>
                    <td>
                      @if($req->passcode)
                        <code class="bg-light p-1">{{ $req->passcode }}</code>
                      @else
                        <span class="text-muted">N/A</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $dues = $req->flat->pendingDues();
                      @endphp
                      <span class="text-{{ $dues > 0 ? 'danger font-weight-bold' : 'success' }}">
                        {{ $setting->currency_symbol ?? '₹' }}{{ number_format($dues, 2) }}
                      </span>
                    </td>
                    <td>
                      @php
                        $badge = 'secondary';
                        if($req->status == 'Approved') $badge = 'success';
                        if($req->status == 'Pending') $badge = 'warning';
                        if($req->status == 'Pending Owner') $badge = 'info';
                        if($req->status == 'Pending Accounts') $badge = 'dark';
                        if($req->status == 'Rejected') $badge = 'danger';
                        if($req->status == 'Completed') $badge = 'primary';
                      @endphp
                      <span class="badge badge-{{ $badge }}">{{ $req->status }}</span>
                    </td>
                    <td>
                      <small class="text-muted">{{ $req->comment ?? '-' }}</small>
                    </td>
                    <td>
                      @if(($req->status == 'Pending' && (Auth::User()->role == 'BA' || Auth::User()->hasRole('president'))) || 
                          ($req->status == 'Pending Accounts' && Auth::User()->hasRole('accounts')) ||
                          ($req->status == 'Pending Accounts' && Auth::User()->role == 'BA'))
                        <form action="{{ route('move-in-out.approve', $req->id) }}" method="POST" style="display:inline-block;">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-success" title="Approve">
                            <i class="fa fa-check"></i>
                          </button>
                        </form>
                      @endif

                      @if(($req->status != 'Completed' && $req->status != 'Approved' && $req->status != 'Rejected') && 
                          (Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts')))
                        <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal" data-id="{{ $req->id }}" title="Reject">
                          <i class="fa fa-times"></i>
                        </button>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="rejectModalLabel">Reject Request</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="rejectForm" method="POST" action="">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label>Comment/Reason</label>
            <textarea name="comment" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function(){
  $('#rejectModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var modal = $(this);
    modal.find('#rejectForm').attr('action', "{{ url('move-in-out/reject') }}/" + id);
  });
});
</script>
@endsection
