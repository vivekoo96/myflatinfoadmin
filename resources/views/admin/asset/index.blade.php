@extends('layouts.admin')

@section('title')
    Assets
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Assets Management</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Assets</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Asset List</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#assetModal"
                      data-id="" data-name="" data-category="" data-location="" data-total_quantity=""
                      data-available_qty="" data-used_qty="" data-damaged_qty="" data-low_stock_threshold=""
                      data-purchase_date="" data-vendor_name="" data-vendor_contact="">
                <i class="fas fa-plus"></i> Add Asset
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
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Purchase Date</th>
                    <th style="width: 15%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($assets as $asset)
                    <tr>
                      <td>{{ $asset->id }}</td>
                      <td>{{ $asset->name }}</td>
                      <td><small>{{ $asset->category }}</small></td>
                      <td><small>{{ $asset->location ?? 'N/A' }}</small></td>
                      <td>{{ $asset->total_quantity }}</td>
                      <td>
                        @if($asset->status === 'active')
                          <span class="badge badge-success">Active</span>
                        @elseif($asset->status === 'under_repair')
                          <span class="badge badge-warning">Under Repair</span>
                        @else
                          <span class="badge badge-secondary">Disposed</span>
                        @endif
                      </td>
                      <td><small>{{ $asset->purchase_date ? $asset->purchase_date->format('d M Y') : 'N/A' }}</small></td>
                      <td>
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#assetModal"
                                data-id="{{ $asset->id }}"
                                data-name="{{ htmlspecialchars($asset->name ?? '', ENT_QUOTES, 'UTF-8') }}"
                                data-category="{{ htmlspecialchars($asset->category ?? '', ENT_QUOTES, 'UTF-8') }}"
                                data-location="{{ htmlspecialchars($asset->location ?? '', ENT_QUOTES, 'UTF-8') }}"
                                data-total_quantity="{{ $asset->total_quantity }}"
                                data-available_qty="{{ $asset->available_qty }}"
                                data-used_qty="{{ $asset->used_qty }}"
                                data-damaged_qty="{{ $asset->damaged_qty }}"
                                data-low_stock_threshold="{{ $asset->low_stock_threshold }}"
                                data-purchase_date="{{ $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '' }}"
                                data-vendor_name="{{ htmlspecialchars($asset->vendor_name ?? '', ENT_QUOTES, 'UTF-8') }}"
                                data-vendor_contact="{{ htmlspecialchars($asset->vendor_contact ?? '', ENT_QUOTES, 'UTF-8') }}"
                                title="Edit">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#statusModal"
                                data-id="{{ $asset->id }}" data-status="{{ $asset->status }}" title="Change Status">
                          <i class="fas fa-exchange-alt"></i>
                        </button>
                        <form action="{{ route('asset.destroy', $asset->id) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="id" value="{{ $asset->id }}">
                          <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this asset?')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted">No assets found</td>
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

<!-- Asset Add/Edit Modal -->
<div class="modal fade" id="assetModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Asset</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('asset.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <input type="hidden" id="asset-id" name="id" value="">

          <div class="form-group">
            <label>Asset Name *</label>
            <input type="text" id="asset-name" name="name" class="form-control" placeholder="e.g., Chair, Motor" required>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Category *</label>
                <input type="text" id="asset-category" name="category" class="form-control" placeholder="e.g., Electrical" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Location</label>
                <input type="text" id="asset-location" name="location" class="form-control" placeholder="e.g., Block A">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Total Quantity *</label>
                <input type="number" id="asset-total_quantity" name="total_quantity" class="form-control" min="1" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Available *</label>
                <input type="number" id="asset-available_qty" name="available_qty" class="form-control" min="0" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Used *</label>
                <input type="number" id="asset-used_qty" name="used_qty" class="form-control" min="0" required>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Damaged *</label>
                <input type="number" id="asset-damaged_qty" name="damaged_qty" class="form-control" min="0" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Low Stock Threshold *</label>
                <input type="number" id="asset-low_stock_threshold" name="low_stock_threshold" class="form-control" min="1" value="2" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Purchase Date</label>
                <input type="date" id="asset-purchase_date" name="purchase_date" class="form-control">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Vendor Name</label>
                <input type="text" id="asset-vendor_name" name="vendor_name" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Vendor Contact</label>
                <input type="text" id="asset-vendor_contact" name="vendor_contact" class="form-control" maxlength="20">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change Asset Status</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('asset.updateStatus') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" id="status-asset-id" name="id" value="">
          <div class="form-group">
            <label>Status *</label>
            <select id="status-select" name="status" class="form-control" required>
              <option value="">-- Select Status --</option>
              <option value="active">Active</option>
              <option value="under_repair">Under Repair</option>
              <option value="disposed">Disposed</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
// Populate Asset Modal on edit
$(document).on('show.bs.modal', '#assetModal', function(e) {
  var button = $(e.relatedTarget);
  var id = button.data('id');
  var modal = $(this);

  if(id) {
    modal.find('.modal-title').text('Edit Asset');
    // Populate fields for edit
    modal.find('#asset-id').val(id);
    modal.find('#asset-name').val(button.data('name') || '');
    modal.find('#asset-category').val(button.data('category') || '');
    modal.find('#asset-location').val(button.data('location') || '');
    modal.find('#asset-total_quantity').val(button.data('total_quantity') || '');
    modal.find('#asset-available_qty').val(button.data('available_qty') || '');
    modal.find('#asset-used_qty').val(button.data('used_qty') || '');
    modal.find('#asset-damaged_qty').val(button.data('damaged_qty') || '');
    modal.find('#asset-low_stock_threshold').val(button.data('low_stock_threshold') || '');
    modal.find('#asset-purchase_date').val(button.data('purchase_date') || '');
    modal.find('#asset-vendor_name').val(button.data('vendor_name') || '');
    modal.find('#asset-vendor_contact').val(button.data('vendor_contact') || '');
  } else {
    modal.find('.modal-title').text('Add Asset');
    // Clear all fields for new
    modal.find('form')[0].reset();
    modal.find('#asset-id').val('');
    modal.find('#asset-total_quantity').val(1);
    modal.find('#asset-low_stock_threshold').val(2);
  }
});

// Populate Status Modal
$(document).on('show.bs.modal', '#statusModal', function(e) {
  var button = $(e.relatedTarget);
  var modal = $(this);
  var assetId = button.data('id');
  var status = button.data('status');

  modal.find('#status-asset-id').val(assetId);
  modal.find('#status-select').val(status);
});

// Handle form submissions via AJAX (delete and status update)
$(document).on('submit', 'form', function(e) {
  var action = $(this).attr('action');

  if(action.includes('destroy') || action.includes('update-status')) {
    e.preventDefault();
    var form = this;

    $.ajax({
      type: 'POST',
      url: action,
      data: $(this).serialize(),
      success: function(response) {
        if(response.msg === 'success' || response.success) {
          window.location.reload();
        } else if(response.error) {
          alert('Error: ' + response.error);
        }
      },
      error: function(xhr) {
        alert('Error: ' + (xhr.responseJSON?.error || 'Operation failed'));
      }
    });
  }
});
</script>
@endsection
