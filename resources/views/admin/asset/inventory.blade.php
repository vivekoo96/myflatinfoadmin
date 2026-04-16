@extends('layouts.admin')

@section('title')
    Inventory
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Inventory Management</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Inventory</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">

    <!-- Summary Cards -->
    <div class="row mb-3">
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon bg-info"><i class="fas fa-box"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Assets</span>
            <span class="info-box-number">{{ $assets->count() }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon {{ $lowStockCount > 0 ? 'bg-danger' : 'bg-success' }}"><i class="fas fa-exclamation-triangle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Low Stock Items</span>
            <span class="info-box-number">{{ $lowStockCount }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon bg-warning"><i class="fas fa-cubes"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Available</span>
            <span class="info-box-number">{{ $assets->sum('available_qty') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Stock Levels</h3>
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
                    <th>#</th>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th style="width: 10%">Total</th>
                    <th style="width: 10%">Available</th>
                    <th style="width: 10%">Used</th>
                    <th style="width: 10%">Damaged</th>
                    <th>Threshold</th>
                    <th>Stock Status</th>
                    <th style="width: 12%">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($assets as $asset)
                    <tr>
                      <td>{{ $asset->id }}</td>
                      <td>{{ $asset->name }}</td>
                      <td><small>{{ $asset->category }}</small></td>
                      <td><strong>{{ $asset->total_quantity }}</strong></td>
                      <td class="text-success"><strong>{{ $asset->available_qty }}</strong></td>
                      <td class="text-warning">{{ $asset->used_qty }}</td>
                      <td class="text-danger">{{ $asset->damaged_qty }}</td>
                      <td>{{ $asset->low_stock_threshold }}</td>
                      <td>
                        @if($asset->isLowStock())
                          <span class="badge badge-danger">Low Stock ⚠️</span>
                        @else
                          <span class="badge badge-success">OK</span>
                        @endif
                      </td>
                      <td>
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#stockModal"
                                data-id="{{ $asset->id }}"
                                data-name="{{ $asset->name }}"
                                data-available_qty="{{ $asset->available_qty }}"
                                data-used_qty="{{ $asset->used_qty }}"
                                data-damaged_qty="{{ $asset->damaged_qty }}"
                                title="Update Stock">
                          <i class="fas fa-edit"></i> Update
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="10" class="text-center text-muted">No assets found</td>
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

<!-- Update Stock Modal -->
<div class="modal fade" id="stockModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stock-modal-title">Update Stock</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('asset.updateStock') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" id="stock-asset-id" name="id" value="">

          <div class="alert alert-info">
            <strong>Current Levels:</strong> Available: <span id="stock-available">0</span> | Used: <span id="stock-used">0</span> | Damaged: <span id="stock-damaged">0</span>
          </div>

          <div class="form-group">
            <label>Add Quantity (new stock received)</label>
            <input type="number" name="add_qty" class="form-control" min="0" placeholder="0">
            <small class="form-text text-muted">Enter quantity to add to available stock</small>
          </div>

          <div class="form-group">
            <label>Mark as Used</label>
            <input type="number" name="used_qty" class="form-control" min="0" placeholder="0">
            <small class="form-text text-muted">Enter quantity consumed/used from available stock</small>
          </div>

          <div class="form-group">
            <label>Mark as Damaged</label>
            <input type="number" name="damaged_qty" class="form-control" min="0" placeholder="0">
            <small class="form-text text-muted">Enter quantity damaged (will reduce available stock)</small>
          </div>

          <div class="alert alert-warning">
            <strong>Calculation:</strong><br>
            New Available = Current + Add - Used - Damaged
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update Stock</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Populate Stock Modal
$('#stockModal').on('show.bs.modal', function(e) {
  var button = $(e.relatedTarget);
  var modal = $(this);
  var assetId = button.data('id');

  modal.find('#stock-modal-title').text('Update Stock - ' + button.data('name'));
  modal.find('#stock-asset-id').val(assetId);

  // Clear the input fields (but not the hidden asset ID)
  modal.find('input[name="add_qty"]').val('');
  modal.find('input[name="used_qty"]').val('');
  modal.find('input[name="damaged_qty"]').val('');

  // Update current levels display
  modal.find('#stock-available').text(button.data('available_qty'));
  modal.find('#stock-used').text(button.data('used_qty'));
  modal.find('#stock-damaged').text(button.data('damaged_qty'));
});
</script>

@endsection
