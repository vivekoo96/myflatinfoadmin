

<?php $__env->startSection('title'); ?>
    Income & Expenditure
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Income & Expenditure </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Income & Expenditure</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
                <div class="">
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

            <div class="card">
                <div class="card-body">
                  <form method="GET" action="<?php echo e(url('account/statement/income-and-expenditure')); ?>">
                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="model" class="col-form-label">Type:</label>
                        <select name="model" id="model" class="form-control" required>
                            <option value="All" <?php echo e(request('model') == 'All' ? 'selected' : ''); ?>>All</option>
                            <option value="Maintenance" <?php echo e(request('model') == 'Maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                            <option value="Event" <?php echo e(request('model') == 'Event' ? 'selected' : ''); ?>>Event</option>
                            <option value="Facility" <?php echo e(request('model') == 'Facility' ? 'selected' : ''); ?>>Facility</option>
                            <option value="Essential" <?php echo e(request('model') == 'Essential' ? 'selected' : ''); ?>>Essential</option>
                            <option value="Corpus" <?php echo e(request('model') == 'Corpus' ? 'selected' : ''); ?>>Corpus</option>
                            <!--<option value="Opening" <?php echo e(request('model') == 'Opening' ? 'selected' : ''); ?>>Opening</option>-->
                        </select>
                      </div>
                      <div class="form-group col-md-6">
                        <div class="model-id"></div>
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo e(request('from_date')); ?>" max="<?php echo e(\Carbon\Carbon::now()->toDateString()); ?>">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo e(request('to_date')); ?>" max="<?php echo e(\Carbon\Carbon::now()->toDateString()); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-3">
                        <label>&nbsp;</label>
                        <a href="<?php echo e(url('account/statement/income-and-expenditure')); ?>" class="btn btn-secondary btn-block mt-2">Reset</a>
                      </div>
                      <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Filter</button>
                      </div>
                    </div>
                    </form>
                </div>
            </div>



            <div class="card">
              <div class="card-header">
                <b>Cash Balance: <?php echo e($inhand); ?></b> <b class="right">Bank Balance: <?php echo e($inbank); ?></b>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                     <th>S No</th> 
                    <th>Date</th>
                    <th>Particulars</th>
                    <th>Cash Debit</th>
                    <th>Cash Credit</th>
                    <th>Bank Debit</th>
                    <th>Bank Credit</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                     <td><?php echo e($i); ?></td> 
                    <td><?php echo e($transaction->date); ?></td>
                    <td><?php echo e($transaction->desc); ?></td>
                    <td><?php echo e($transaction->type == 'Debit' && $transaction->payment_type == 'InHand' ? $transaction->amount : ''); ?></td>
                    <td><?php echo e($transaction->type == 'Credit' && $transaction->payment_type == 'InHand' ? $transaction->amount : ''); ?></td>
                    <td><?php echo e($transaction->type == 'Debit' && $transaction->payment_type == 'InBank' ? $transaction->amount : ''); ?></td>
                    <td><?php echo e($transaction->type == 'Credit' && $transaction->payment_type == 'InBank' ? $transaction->amount : ''); ?></td>
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
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->



<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    var model = "<?php echo e(request('model')); ?>";
    var model_id = "<?php echo e(request('model_id')); ?>";
    
    $(document).on('change','#model',function(){
      var model = $(this).val();
      $('.model-id').html('');
      if(model == 'Event' || model == 'Essential' || model == 'Facility'){
        $.ajax({
          url : "<?php echo e(url('/get-model-data')); ?>",
          type: "post",
          data : {'_token':token,'model':model},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      }
    });

    if(model == 'Event' || model == 'Essential' || model == 'Facility'){
        $.ajax({
          url : "<?php echo e(url('/get-model-data')); ?>",
          type: "post",
          data : {'_token':token,'model':model,'model_id':model_id},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
    }

  });
</script>

<script src="<?php echo e(asset('public/admin/plugins/summernote/summernote-bs4.min.js')); ?>"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>

<?php $__env->stopSection(); ?>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/account/statement/income_and_expenditure.blade.php ENDPATH**/ ?>