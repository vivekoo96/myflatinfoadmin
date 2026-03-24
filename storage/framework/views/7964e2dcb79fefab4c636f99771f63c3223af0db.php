<?php $__env->startSection('title'); ?>
    Flat List
<?php $__env->stopSection(); ?>

<style>
.readonly-field {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
}
.readonly-field:focus {
    background-color: #f8f9fa !important;
    border-color: #ced4da !important;
    box-shadow: none !important;
}
select:disabled {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
}
</style>

<?php $__env->startSection('content'); ?>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                <?php if(session()->has('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session()->get('error')); ?>

                </div>
                <?php endif; ?>
                <?php if(session()->has('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session()->get('success')); ?>

                </div>
                <?php endif; ?>
            </div>
          <div class="col-sm-6">
            <h1>Flat List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Flats</li>
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
                <?php 
                  $created_counts = \App\Models\Flat::where('building_id', Auth::user()->building_id)->whereNull('deleted_at')->count();
                  $flat_limit = Auth::user()->building->no_of_flats;
                ?>
                <span><?php echo e($created_counts); ?>/<?php echo e($flat_limit); ?></span>
                <?php if(Auth::User()->role == 'BA'): ?>
                <button class="btn btn-sm btn-success right ml-2" data-toggle="modal" data-target="#addModal" <?php echo e($created_counts >= $flat_limit ? 'disabled' : ''); ?>>Add New Flat</button>
                <button class="btn btn-sm btn-info right ml-2" data-toggle="modal" data-target="#bulkUploadModal">Bulk Upload</button>
                <?php endif; ?>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                     <th>S No</th> 
                    <th>Block</th>
                    <th>Flat</th>
                    <th>Owner</th>
                    <th>Tenant</th>
                    <th>Area</th>
                    <th>Family Members</th>
                    <th>Status</th>
                    <th>SoldOut</th>
                    <th>Living</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $building->flats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $flat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    
                     <td><?php echo e($i); ?></td> 
                    <td><?php echo e($flat->block->name); ?></td>
                    <td><?php echo e($flat->name); ?></td>
                 
                     <?php
                            $ownerBuildingUserId = \App\Models\BuildingUser::join('roles', 'roles.id', '=', 'building_users.role_id')
                                ->where('building_users.user_id', optional($flat->owner)->id)
                                ->where('building_users.building_id', $flat->building_id)
                                ->where('roles.name', 'user')
                                ->where('roles.building_id', $flat->building_id)
                                ->value('building_users.id');
                        
                            $tenantBuildingUserId = \App\Models\BuildingUser::join('roles', 'roles.id', '=', 'building_users.role_id')
                                ->where('building_users.user_id', optional($flat->tanent)->id)
                                ->where('building_users.building_id', $flat->building_id)
                                ->where('roles.name', 'user')
                                ->where('roles.building_id', $flat->building_id)
                                ->value('building_users.id');
                        ?>
<td>
<?php if($flat->owner && $ownerBuildingUserId): ?>
    <a href="<?php echo e(url('user/'.$flat->owner->id.'/'.$ownerBuildingUserId)); ?>" target="_blank">
        <?php echo e($flat->owner->name); ?>

    </a>
<?php else: ?>
    —
<?php endif; ?>
</td>

<td>
<?php if($flat->tanent && $tenantBuildingUserId): ?>
    <a href="<?php echo e(url('user/'.$flat->tanent->id.'/'.$tenantBuildingUserId)); ?>" target="_blank">
        <?php echo e($flat->tanent->name); ?>

    </a>
<?php else: ?>
    —
<?php endif; ?>
</td>

                    <td><?php echo e($flat->area); ?></td>
                    <td><?php echo e($flat->family_members->count()); ?></td>
                    <td><?php echo e($flat->status); ?></td>
                    <td><?php echo e($flat->sold_out); ?></td>
                    <td>
                        <?php if($flat->living_status == 'Tanent'): ?>
                            Tenant
                        <?php else: ?>
                          <?php echo e($flat->living_status); ?>

                        <?php endif; ?>
                      
                        </td>
                    <td class="d-flex">
            <a href="<?php echo e(route('flat.show',$flat->id)); ?>"   class="btn btn-sm btn-warning mx-1"><i class="fa fa-eye"></i></a>
            <?php if(Auth::User()->role == 'BA'): ?>
            <button class="btn btn-sm btn-primary mx-1" data-toggle="modal" data-target="#addModal" data-id="<?php echo e($flat->id); ?>" data-name="<?php echo e($flat->name); ?>" data-status="<?php echo e($flat->status); ?>" data-sold_out="<?php echo e($flat->sold_out); ?>" 
              data-owner_name="<?php echo e($flat->owner ? $flat->owner->name : ''); ?>" data-tanent_name="<?php echo e($flat->tanent ? $flat->tanent->name : ''); ?>" data-owner_id="<?php echo e($flat->owner_id); ?>" data-tanent_id="<?php echo e($flat->tanent_id); ?>"
              data-area="<?php echo e($flat->area); ?>" data-corpus_fund="<?php echo e($flat->corpus_fund); ?>" data-building_id="<?php echo e($flat->building_id); ?>" data-block_id="<?php echo e($flat->block_id); ?>" data-living_status="<?php echo e($flat->living_status); ?>" 
               data-owner_email="<?php echo e($flat->owner ? $flat->owner->email : ''); ?>" data-tanent_email="<?php echo e($flat->tanent ? $flat->tanent->email : ''); ?>"><i class="fa fa-edit"></i></button>
            <?php endif; ?>
         <?php if($flat->owner_id >= 1): ?>
         
         
           <?php if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts') ): ?>
         <?php if(Auth::User()->building->hasPermission('Corpusfund')): ?>
                <?php if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts')): ?>
                    <button class="btn btn-sm btn-primary mx-1" data-toggle="modal" data-target="#corpusModal" data-id="<?php echo e($flat->id); ?>" data-corpus-fund="<?php echo e($flat->corpus_fund); ?>" data-is_corpus_paid="<?php echo e($flat->is_corpus_paid); ?>" 
                    data-corpus_paid_on="<?php echo e($flat->corpus_paid_on); ?>" data-bill_no="<?php echo e($flat->bill_no); ?>" data-payment_type="<?php echo e($flat->corpus_payment_type); ?>"><i class="fa fa-money"></i></button>
               <?php endif; ?>
               <?php else: ?>
                    <button class="btn btn-sm btn-primary mx-1 corpus-no-permission" data-no-access="true"><i class="fa fa-money"></i></button>
                <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
            <?php if(Auth::User()->role == 'BA'): ?>
              <?php if($flat->deleted_at): ?>
                <button class="btn btn-sm btn-success mx-1" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($flat->id); ?>" data-action="restore"><i class="fa fa-undo"></i></button>
              <?php else: ?>
                <button class="btn btn-sm btn-danger mx-1" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($flat->id); ?>" data-action="delete"><i class="fa fa-trash"></i></button>
              <?php endif; ?>
            <?php endif; ?>
                    </td>

                  </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <?php endif; ?>
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
        <h5 class="modal-title" id="exampleModalLabel">Add New Flat</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('flat.store')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="form-group">
            <label for="phone" class="col-form-label">Block:</label>
            <select name="block_id" class="form-control" id="block_id" required >
                <?php $__empty_1 = true; $__currentLoopData = $building->blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $block): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <option value="<?php echo e($block->id); ?>"><?php echo e($block->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Flat Name or Number:</label>
            <input type="text" name="name" class="form-control" id="name" placeholder="Flat Name or No" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Flat Area:In sqft</label>
            <input type="number" name="area" class="form-control" id="area" placeholder="Flat Area" min="0" required data-no-edit="true" />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Corpus Fund:</label>
            <input type="number" name="corpus_fund" class="form-control" id="corpus_fund" placeholder="Society Fund" min="0" required data-no-edit="true" />
          </div>
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label for="sold_out" class="col-form-label">SoldOut:</label>
            <select name="sold_out" class="form-control" id="sold_out">
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select>
          </div>
          <div class="form-group">
            <label for="living_status" class="col-form-label">Living Status:</label>
            <select name="living_status" class="form-control living_status" id="living_status" required>
              <option value="Vacant">Vacant</option>
              <option value="Owner">Owner</option>
              <option value="Tanent">Tenant</option>
            </select>
          </div>

          <div class="form-group owner-email-group">
            <label for="email" class="col-form-label">Owner Email:</label>
            <div class="input-group">
              <input type="email" name="owner_email" class="form-control" id="owner_email" maxlength="40" placeholder="Owner Email">
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getOwnerData">Get Owner Data</button>
              </div>
            </div>
          </div>
          <div class="owner_error text-danger"></div>
          <div class="form-group owner-name-group">
            <label for="email" class="col-form-label">Owner Name:</label>
            <input type="text" name="owner_name" class="form-control" id="owner_name" disabled>
          </div>
          <div class="form-group tanent-email-group">
            <label for="email" class="col-form-label">Tenant Email:</label>
            <div class="input-group">
              <input type="email" name="tanent_email" class="form-control" id="tanent_email" maxlength="40" placeholder="Tenant Email">
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getTanentData">Get Tenant Data</button>
              </div>
            </div>
          </div>
          <div class="tanent_error text-danger"></div>
          <div class="form-group tanent-name-group">
            <label for="email" class="col-form-label">Tenant Name:</label>
            <input type="text" name="tanent_name" class="form-control" id="tanent_name" disabled>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="owner_id" id="owner_id" value="">
          <input type="hidden" name="tanent_id" id="tanent_id" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- corpus Modal -->

<div class="modal fade" id="corpusModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Corpus Fund</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('update-corpus-fund')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="form-group">
            <label for="phone" class="col-form-label">Corpus Amount:</label>
            <input type="number" name="corpus_fund" class="form-control" id="corpus_fund2" placeholder="Corpus Fund" min="0" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Payment Status:</label>
            <select name="is_corpus_paid" class="form-control" id="is_corpus_paid" required>
              <option value="Yes">Paid</option>
              <option value="No">Unpaid</option>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Payment Mode:</label>
            <select name="payment_type" class="form-control" id="payment_type" required>
              <option value="InHand">InHand</option>
              <option value="InBank">InBank</option>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Paid On:</label>
            <input type="date" name="corpus_paid_on" class="form-control" id="corpus_paid_on" placeholder="corpus_paid_on" required>
          </div>
          <div class="form-group">
            <label for="bill_no" class="col-form-label">Bill Number:</label>
            <input type="text" name="bill_no" class="form-control" id="bill_no" readonly>
          </div>
          <input type="hidden" name="id" id="edit-id2">
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

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Flats</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('bulk-upload-flats')); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-8">
              <div class="alert alert-info">
                <h6><i class="fa fa-info-circle"></i> <strong>How to Use Bulk Upload</strong></h6>
                <ol class="mb-0">
                  <li><strong>Download Template:</strong> Click the green button to get the CSV template</li>
                  <li><strong>Fill Your Data:</strong> Replace example data with your actual flat information</li>
                  <li><strong>Save as CSV:</strong> Keep the file in CSV format (.csv)</li>
                  <li><strong>Upload:</strong> Select your file and click "Upload Flats"</li>
                </ol>
                <small class="text-muted mt-2 d-block"><strong>Tip:</strong> The template includes 3 example rows showing different scenarios (Owner-only, Owner+Tenant, Vacant flat)</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <a href="<?php echo e(url('download-sample-flats')); ?>" class="btn btn-success btn-block">
                  <i class="fa fa-download"></i> Download CSV Template
                </a>
                <small class="text-muted">Ready-to-use template with examples</small>
              </div>
            </div>
          </div>
          
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="form-group">
                <label for="bulk_file"><strong>Choose File</strong></label>
                <input type="file" name="bulk_file" id="bulk_file" class="form-control-file" accept=".csv, .xlsx, .xls" required>
                <small class="form-text text-muted">Maximum file size: 2MB</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="alert alert-warning">
                <h6><i class="fa fa-exclamation-triangle"></i> <strong>Important Rules</strong></h6>
                <div class="row">
                  <div class="col-md-6">
                    <ul class="mb-0">
                      <li><strong>Block Names:</strong> 
                        <?php if(isset($blocks) && count($blocks) > 0): ?>
                          <?php echo e(implode(', ', $blocks->pluck('name')->toArray())); ?>

                        <?php else: ?>
                          Must exist in your building
                        <?php endif; ?>
                      </li>
                      <li><strong>Flat Names:</strong> Must be unique per block</li>
                      <li><strong>Status:</strong> Active or Inactive</li>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <ul class="mb-0">
                      <li><strong>Sold Out:</strong> Yes or No</li>
                      <li><strong>Living Status:</strong> Owner, Tenant, or Vacant</li>
                      <li><strong>Users:</strong> Auto-created if details provided</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="card">
                <div class="card-body text-center">
                  <h6 class="text-primary">Current Limits</h6>
                  <p class="mb-1"><strong>Flats:</strong> <?php echo e($created_counts); ?> / <?php echo e($flat_limit); ?></p>
                  <p class="mb-0"><strong>Available:</strong> <?php echo e($flat_limit - $created_counts); ?> flats</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card">
                <div class="card-body text-center">
                  <h6 class="text-success">Upload Status</h6>
                  <p class="mb-0">
                    <?php if($created_counts >= $flat_limit): ?>
                      <span class="text-danger"><i class="fa fa-times-circle"></i> Limit Reached</span>
                    <?php else: ?>
                      <span class="text-success"><i class="fa fa-check-circle"></i> Ready to Upload</span>
                    <?php endif; ?>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary" <?php echo e($created_counts >= $flat_limit ? 'disabled' : ''); ?>>
            <i class="fa fa-upload"></i> Upload Flats
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    
    // Fix DataTables search functionality completely
    setTimeout(function() {
        // Check if DataTables is initialized
        if (!$.fn.dataTable.isDataTable('#example1')) {
            console.log('DataTables not initialized, initializing now...');
            $('#example1').DataTable({
                "responsive": false, 
                "scrollX": true,  
                "ordering": true, 
                "lengthChange": false, 
                "autoWidth": false,
                "paging": true,
                "info": false,
                "searching": true,
                "pageLength": 10,
                "language": {
                    "search": "Search:"
                }
            });
        }
        
        // Completely disable input validation for all DataTables search inputs
        $('.dataTables_filter input').each(function() {
            var $input = $(this);
            // Remove all event handlers
            $input.off();
            // Remove all attributes that might interfere
            $input.removeAttr('maxlength').removeAttr('pattern');
            // Add back only the DataTables search functionality with case-insensitive support
            $input.on('keyup', function(e) {
                var searchTerm = $(this).val();
                var table = $('#example1').DataTable();
                
                // Enable case-insensitive search
                table.search(searchTerm, false, false, true).draw();
                
                console.log('Searching for:', searchTerm);
            });
        });
        
        console.log('DataTables search fix applied');
        
        // Fallback: Add custom search if DataTables search still doesn't work
        setTimeout(function() {
            // Add custom search input if needed
            if ($('.dataTables_filter input').length === 0) {
                $('.card-body').prepend('<div class="mb-3"><input type="text" id="customSearch" class="form-control" placeholder="Search blocks..."></div>');
                
                $('#customSearch').on('keyup', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    console.log('Custom search for:', searchTerm);
                    $('#example1 tbody tr').each(function() {
                        var rowText = $(this).text().toLowerCase();
                        var shouldShow = rowText.includes(searchTerm);
                        $(this).toggle(shouldShow);
                        console.log('Row:', rowText, 'Match:', shouldShow);
                    });
                });
            }
        }, 500);
    }, 300);
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      $('.modal-title').text('Are you sure ?');
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
      var url = "<?php echo e(route('flat.destroy','')); ?>";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'id':id,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      
      // Reset form fields
      $('#edit-id').val('');
      $('#owner_name').val('');
      $('#tanent_name').val('');
      $('#owner_email').val('');
      $('#tanent_email').val('');
      $('#owner_id').val('');
      $('#tanent_id').val('');
      $('#building_id').val('<?php echo e($building->id); ?>');
      $('#block_id').val('');
      $('#name').val('');
      $('#area').val('');
      $('#corpus_fund').val('');
      $('#corpus_fund').prop('readonly', false); // Allow editing for new flats
      $('#status').val('Active'); // Default status
      $('#sold_out').val('No');
      $('#living_status').val('Vacant');
      
      // If editing, populate fields with existing data
      if(edit_id) {
        $('.modal-title').text('Update Flat');
        $('#edit-id').val(edit_id);
        $('#owner_name').val(button.data('owner_name') || '');
        $('#tanent_name').val(button.data('tanent_name') || '');
        $('#owner_email').val(button.data('owner_email') || '');
        $('#tanent_email').val(button.data('tanent_email') || '');
        $('#owner_id').val(button.data('owner_id') || '');
        $('#tanent_id').val(button.data('tanent_id') || '');
        $('#building_id').val(button.data('building_id') || '<?php echo e($building->id); ?>');
        $('#block_id').val(button.data('block_id') || '');
        $('#name').val(button.data('name') || '');
        $('#area').val(button.data('area') || '');
        $('#corpus_fund').val(button.data('corpus_fund') || 0);
        $('#corpus_fund').prop('readonly', true); // Disable editing corpus fund for existing flats
        $('#area').prop('readonly', true);
        $('#status').val(button.data('status') || 'Active');
        $('#sold_out').val(button.data('sold_out') || 'No');
        $('#living_status').val(button.data('living_status') || 'Vacant');
      } else {
        $('.modal-title').text('Add New Flat');
      }
      var living_status = $('#living_status').val();
      var sold_out = $('#sold_out').val();
      $('.owner-email-group').hide();
      $('.owner-name-group').hide();
      $('.tanent-email-group').hide();
      $('.tanent-name-group').hide();

      $('#owner_email').attr('required',false);
      $('#owner_name').attr('required',false);
      $('#tanent_email').attr('required',false);
      $('#tanent_name').attr('required',false);

      if(sold_out == 'Yes'){
        $('.owner-email-group').show();
        $('.owner-name-group').show();

        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
      }

      if(living_status == 'Owner'){
        $('.owner-email-group').show();
        $('.owner-name-group').show();

        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
      }
      if(living_status == 'Tanent'){
        $('.tanent-email-group').show();
        $('.tanent-name-group').show();

        $('#tanent_email').attr('required',true);
        $('#tanent_name').attr('required',true);
      }
    });

    $(document).on('change', '#sold_out', function (event) {
      var sold_out = $(this).val();
      $('.owner-email-group').hide();
      $('.owner-name-group').hide();
      $('.tanent-email-group').hide();
      $('.tanent-name-group').hide();

      $('#owner_email').attr('required',false);
      $('#owner_name').attr('required',false);
      $('#tanent_email').attr('required',false);
      $('#tanent_name').attr('required',false);
      if(sold_out == 'Yes'){
        $('.owner-email-group').show();
        $('.owner-name-group').show();
        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
      }
      if(sold_out == 'No'){
        $('.living_status').val('Vacant');
      }

    });

    $(document).on('change', '.living_status', function (event) {
      var living_status = $(this).val();
      var sold_out = $('#sold_out').val();
      $('.owner-email-group').hide();
      $('.owner-name-group').hide();
      $('.tanent-email-group').hide();
      $('.tanent-name-group').hide();

      $('#owner_email').attr('required',false);
      $('#owner_name').attr('required',false);
      $('#tanent_email').attr('required',false);
      $('#tanent_name').attr('required',false);
      if(sold_out == 'Yes'){
        $('.owner-email-group').show();
        $('.owner-name-group').show();

        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
      }
      if(living_status == 'Owner'){
        $('#sold_out').val('Yes');
        $('.owner-email-group').show();
        $('.owner-name-group').show();

        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
      }
      if(living_status == 'Tanent'){
        $('#sold_out').val('Yes');
        $('.owner-email-group').show();
        $('.owner-name-group').show();
        $('.tanent-email-group').show();
        $('.tanent-name-group').show();

        $('#owner_email').attr('required',true);
        $('#owner_name').attr('required',true);
        $('#tanent_email').attr('required',true);
        $('#tanent_name').attr('required',true);
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "<?php echo e(url('update-building-status')); ?>",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      var $form = $(this);
      var $saveBtn = $form.find('#save-button');
      if ($saveBtn.prop('disabled')) {
        event.preventDefault();
        return false;
      }
      $saveBtn.prop('disabled', true);
      setTimeout(function() { $saveBtn.prop('disabled', false); }, 1000); // Re-enable after 1s
    });
    
    // Fetch owner data when clicking "Get Owner Data"
    $('#getOwnerData').on('click', function () {
      var owner_email = $('#owner_email').val().trim();
      if (owner_email === '') {
        $('.owner_error').text('Please enter an email to fetch owner data.');
        return;
      }
      
      $('.owner_error').text(''); // Clear previous errors
      
      $.ajax({
        url: '<?php echo e(url("get-user-by-email")); ?>', // Update with your actual route
        type: 'POST',
        data: {'_token':token, email: owner_email },
        success: function (response) {
          if (response.success) {
            $('#owner_name').val(response.data.name);
            $('#owner_id').val(response.data.id);
          } else {
            $('.owner_error').text('Owner not found.');
            $('#owner_name').val('');
          }
        },
        error: function () {
          $('.owner_error').text('Error fetching owner data.');
          $('#owner_name').val('');
        }
      });
    });
    
    // Fetch tanent data when clicking "Get Tanent Data"
    $('#getTanentData').on('click', function () {
      var tanent_email = $('#tanent_email').val().trim();
      if (tanent_email === '') {
        $('.tanent_error').text('Please enter an email to fetch Tenant data.');
        return;
      }
      
      $('.tanent_error').text(''); // Clear previous errors
      
      $.ajax({
        url: '<?php echo e(url("get-user-by-email")); ?>', // Update with your actual route
        type: 'POST',
        data: { '_token':token, email: tanent_email },
        success: function (response) {
          if (response.success) {
            $('#tanent_name').val(response.data.name);
            $('#tanent_id').val(response.data.id);
          } else {
            $('.tanent_error').text('Tenant not found.');
            $('#tanent_name').val('');
          }
        },
        error: function () {
          $('.tanent_error').text('Error fetching tenant data.');
          $('#tanent_name').val('');
        }
      });
    });

       $('#corpusModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      var $modal = $(this);
      $modal.find('#edit-id2').val(edit_id);
      $modal.find('#corpus_fund2').val(button.data('corpus-fund'));
      $modal.find('#is_corpus_paid').val(button.data('is_corpus_paid'));
      $modal.find('#corpus_paid_on').val(button.data('corpus_paid_on'));
      $modal.find('#payment_type').val(button.data('payment_type'));
      // Set the existing bill number if any
      var billNo = button.data('bill_no') || '';
      $modal.find('#bill_no').val(billNo);

      // Disable payment status dropdown if bill exists and payment is made
      if (billNo && button.data('is_corpus_paid') === 'Yes') {
        $modal.find('#is_corpus_paid').prop('disabled', true);
        $modal.find('#save-button').prop('disabled', true);
      } else if (button.data('is_corpus_paid') === 'Yes') {
        $modal.find('#save-button').prop('disabled', true);
        $modal.find('#is_corpus_paid').prop('disabled', false);
      } else {
        $modal.find('#is_corpus_paid').prop('disabled', false);
        $modal.find('#save-button').prop('disabled', false);
      }

      $modal.find('.modal-title').text('Corpus Fund');

    });

      // Handle Corpus Fund modal form submission
      $('#corpusModal form').off('submit').on('submit', function (event) {
        var $form = $(this);
        var $saveBtn = $form.find('#save-button');
        
        // Check if button is already disabled
        if ($saveBtn.prop('disabled')) {
          event.preventDefault();
          return false;
        }
        
        // Re-enable any disabled fields temporarily to include them in form submission
        $('#is_corpus_paid').prop('disabled', false);
        $('#payment_type').prop('disabled', false);
        
        // Validate required fields when marking as paid
        if ($('#is_corpus_paid').val() === 'Yes') {
          var corpusAmount = $('#corpus_fund2').val();
          var paymentType = $('#payment_type').val();
          var paidOn = $('#corpus_paid_on').val();
          
          if (!corpusAmount || !paymentType || !paidOn) {
            event.preventDefault();
            alert('Please fill all required fields (Corpus Amount, Payment Mode, and Paid On date) before marking as paid.');
            return false;
          }
        }
        
        // Disable save button to prevent double submission
        $saveBtn.prop('disabled', true);
        setTimeout(function() { $saveBtn.prop('disabled', false); }, 1000); // Re-enable after 1s
      });

    // Generate or clear bill number based on payment status and control form fields
    $('#is_corpus_paid').on('change', function() {
      var isPaid = $(this).val() === 'Yes';
      
      if (isPaid) {
        // Generate new unique bill number if not already present
        if (!$('#bill_no').val()) {
          var uniqueBillNo = 'BILL-' + new Date().getTime() + '-' + Math.random().toString(36).substr(2, 5).toUpperCase();
          $('#bill_no').val(uniqueBillNo);
        }
        
        // Check if this is an existing paid record (has existing data) or new payment
        var isExistingPaidRecord = $('#corpus_fund2').val() && $('#payment_type').val() && $('#corpus_paid_on').val();
        
        if (isExistingPaidRecord) {
          // This is already a paid record - disable everything including payment status
          $(this).prop('disabled', true);
          $('#corpus_fund2').prop('readonly', true).addClass('readonly-field');
           
          $('#payment_type').prop('disabled', true);
          $('#corpus_paid_on').prop('readonly', true).addClass('readonly-field');
          $('#save-button').prop('disabled', true).text('Cannot Update (Paid)');
        } else {
          // This is being changed from unpaid to paid - keep fields editable for completion
          $(this).prop('disabled', false); // Keep payment status changeable until saved
          $('#corpus_fund2').prop('readonly', true).removeClass('readonly-field');
          $('#payment_type').prop('disabled', false); // Keep payment mode selectable
          $('#corpus_paid_on').prop('readonly', false).removeClass('readonly-field');
          $('#save-button').prop('disabled', false).text('Save');
        }
        
        $('#bill_no').prop('readonly', true);
        
      } else {
        // Clear bill number and enable all fields when unpaid
        $('#bill_no').val('');
        
        // Enable all form fields when unpaid
        $(this).prop('disabled', false);
        $('#corpus_fund2').prop('readonly', true).removeClass('readonly-field');
        $('#payment_type').prop('disabled', false);
        $('#corpus_paid_on').prop('readonly', false).removeClass('readonly-field');
        $('#bill_no').prop('readonly', true); // Bill number stays readonly
        $('#save-button').prop('disabled', false).text('Save');
      }
    });
    
    // Initialize form state when modal opens
    $('#corpusModal').on('show.bs.modal', function() {
      // Reset payment status dropdown to enabled first (in case it was disabled)
      $('#is_corpus_paid').prop('disabled', false);
      
      // Trigger change event to set initial state
      setTimeout(function() {
        $('#is_corpus_paid').trigger('change');
      }, 100);
    });

    // Handle bulk upload form submission with progress indication
    $('form[action*="bulk-upload-flats"]').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $fileInput = $form.find('#bulk_file');
        
        // Check if file is selected
        if (!$fileInput[0].files.length) {
            e.preventDefault();
            alert('Please select a file to upload.');
            return false;
        }
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
        
        // Optional: Add progress indication
        var $progressDiv = $('<div class="mt-3"><div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Processing...</div></div></div>');
        $form.find('.modal-body').append($progressDiv);
        
        // Re-enable button after 30 seconds (timeout)
        setTimeout(function() {
            $submitBtn.prop('disabled', false);
            $submitBtn.html('<i class="fa fa-upload"></i> Upload Flats');
            $progressDiv.remove();
        }, 30000);
    });

    // Reset corpus modal when closed
    $('#corpusModal').on('hidden.bs.modal', function() {
        $('#is_corpus_paid').prop('disabled', false);
    });

    // Reset modal when closed
    $('#bulkUploadModal').on('hidden.bs.modal', function() {
        var $form = $(this).find('form');
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Reset form
        $form[0].reset();
        
        // Reset button
        $submitBtn.prop('disabled', false);
        $submitBtn.html('<i class="fa fa-upload"></i> Upload Flats');
        
        // Remove progress bar if exists
        $form.find('.progress').parent().remove();
    });
    
    $(document).on('click', 'button[data-no-access="true"]', function(e) {
        e.preventDefault();
        window.location.href = "<?php echo e(url('permission-denied')); ?>";
    });


  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/flat/index.blade.php ENDPATH**/ ?>