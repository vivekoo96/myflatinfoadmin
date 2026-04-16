@extends('layouts.admin')

@section('title')
    AMC Tracking
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>AMC Tracking</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">AMC Tracking</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">

    @if($dueSoon->count() > 0)
    <div class="row mb-3">
      <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show">
          <i class="fas fa-exclamation-circle"></i>
          <strong>{{ $dueSoon->count() }} AMC Contract(s)</strong> due for renewal within 30 days!
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
      </div>
    </div>
    @endif

    <!-- AMC Table -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Annual Maintenance Contracts</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#amcModal"
                      data-id="" data-asset_id="" data-provider_name="" data-start_date="" data-end_date=""
                      data-service_frequency="" data-notes="">
                <i class="fas fa-plus"></i> Add AMC
              </button>
            </div>
          </div>
          <div class="card-body">
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session()->get('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session()->get('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th style="width: 5%">#</th>
                    <th>Asset</th>
                    <th>Provider</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Frequency</th>
                    <th>Status</th>
                    <th>Days Remaining</th>
                    <th style="width: 12%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($amcs as $amc)
                    <tr>
                      <td>{{ $amc->id }}</td>
                      <td><strong>{{ $amc->asset->name ?? 'N/A' }}</strong></td>
                      <td>{{ $amc->provider_name }}</td>
                      <td>{{ $amc->start_date->format('d M Y') }}</td>
                      <td>{{ $amc->end_date->format('d M Y') }}</td>
                      <td><small>{{ $amc->service_frequency }}</small></td>
                      <td>
                        @if($amc->getStatusAttribute() === 'active')
                          <span class="badge badge-success">Active</span>
                        @else
                          <span class="badge badge-danger">Expired</span>
                        @endif
                      </td>
                      <td>
                        @php
                          $daysLeft = $amc->daysRemaining();
                        @endphp
                        @if($amc->getStatusAttribute() === 'active')
                          @if($amc->isDueForRenewal())
                            <span class="badge badge-warning">{{ $daysLeft }} days ⚠️</span>
                          @else
                            <span class="badge badge-info">{{ $daysLeft }} days</span>
                          @endif
                        @else
                          <span class="badge badge-secondary">{{ abs($daysLeft) }} days ago</span>
                        @endif
                      </td>
                      <td>
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#amcModal"
                                data-id="{{ $amc->id }}"
                                data-asset_id="{{ $amc->asset_id }}"
                                data-provider_name="{{ $amc->provider_name }}"
                                data-start_date="{{ $amc->start_date->format('Y-m-d') }}"
                                data-end_date="{{ $amc->end_date->format('Y-m-d') }}"
                                data-service_frequency="{{ $amc->service_frequency }}"
                                data-notes="{{ $amc->notes }}"
                                title="Edit">
                          <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('asset-amc.destroy', $amc->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="id" value="{{ $amc->id }}">
                          <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this AMC?')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="9" class="text-center text-muted">No AMC records found</td>
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

<!-- AMC Add/Edit Modal -->
<div class="modal fade" id="amcModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add AMC</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('asset-amc.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" id="amc-id" name="id" value="">

          <div class="form-group">
            <label>Asset *</label>
            <select id="amc-asset_id" name="asset_id" class="form-control" required>
              <option value="">-- Select Asset --</option>
              @foreach($assets as $asset)
                <option value="{{ $asset->id }}">{{ $asset->name }} ({{ $asset->category }})</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label>Provider Name *</label>
            <input type="text" id="amc-provider_name" name="provider_name" class="form-control" placeholder="e.g., XYZ Services" required>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Start Date *</label>
                <input type="date" id="amc-start_date" name="start_date" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>End Date *</label>
                <input type="date" id="amc-end_date" name="end_date" class="form-control" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Service Frequency *</label>
            <select id="amc-service_frequency" name="service_frequency" class="form-control" required>
              <option value="">-- Select Frequency --</option>
              <option value="Monthly">Monthly</option>
              <option value="Quarterly">Quarterly</option>
              <option value="Half-Yearly">Half-Yearly</option>
              <option value="Yearly">Yearly</option>
            </select>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea id="amc-notes" name="notes" class="form-control" rows="3" placeholder="Additional AMC details..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save AMC</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Populate AMC Modal on edit
$('#amcModal').on('show.bs.modal', function(e) {
  var button = $(e.relatedTarget);
  var id = button.data('id');

  if(id) {
    $('.modal-title').text('Edit AMC');
  } else {
    $('.modal-title').text('Add AMC');
  }

  $('#amc-id').val(id || '');
  $('#amc-asset_id').val(button.data('asset_id') || '');
  $('#amc-provider_name').val(button.data('provider_name') || '');
  $('#amc-start_date').val(button.data('start_date') || '');
  $('#amc-end_date').val(button.data('end_date') || '');
  $('#amc-service_frequency').val(button.data('service_frequency') || '');
  $('#amc-notes').val(button.data('notes') || '');
});

// Handle form deletion via AJAX
$('form').on('submit', function(e) {
  if($(this).attr('action').includes('destroy')) {
    e.preventDefault();
    var form = this;
    $.ajax({
      type: 'POST',
      url: $(this).attr('action'),
      data: $(this).serialize(),
      success: function(response) {
        if(response.msg === 'success') {
          window.location.reload();
        }
      }
    });
  }
});
</script>

@endsection
