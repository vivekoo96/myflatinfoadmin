    

    <?php $__env->startSection('title'); ?>
        Setting
    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('content'); ?>
<?php
    $isBA = Auth::user()->role === 'BA';
?>
        <!-- Content Header (Page header) -->
        <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Setting</li>
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
                    Business Setting
                </div>
                <div class="card-body">
                    <?php if(session()->has('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session()->get('success')); ?>

                        </div>
                    <?php endif; ?>
                    <?php if(session()->has('error')): ?>
                        <div class="alert alert-danger">
                            <?php echo e(session()->get('error')); ?>

                        </div>
                    <?php endif; ?>
                    <form action="<?php echo e(route('setting.store')); ?>" method="post" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                            <div class="col-md-4">
                            <?php if($building->payment_is_active == 'Yes'): ?>
                                <div class="form-group">
                                        <label>Razorpay Key</label>
                                        <input type="text" class="form-control" name="razorpay_key" value="<?php echo e($building->razorpay_key); ?>" required <?php echo e($isBA ? '' : 'disabled'); ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Razorpay Secret</label>
                                        <input type="text" class="form-control" name="razorpay_secret" value="<?php echo e($building->razorpay_secret); ?>" required <?php echo e($isBA ? '' : 'disabled'); ?>>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                        <label>Razorpay Key</label>
                                        <input type="text" class="form-control" name="razorpay_key" value="<?php echo e($setting->razorpay_key); ?>" disabled required <?php echo e($isBA ? '' : 'disabled'); ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Razorpay Secret</label>
                                        <input type="text" class="form-control" name="razorpay_secret" value="<?php echo e($setting->razorpay_secret); ?>" disabled required <?php echo e($isBA ? '' : 'disabled'); ?>>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>GST No</label>
                                    <input type="text" class="form-control" name="gst_no" value="<?php echo e($building->gst_no); ?>" <?php echo e($isBA ? '' : 'disabled'); ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Classified Limit(Within Building)</label>
                                    <input type="number" class="form-control" name="classified_limit_within_building" value="<?php echo e($building->classified_limit_within_building); ?>" disabled required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Classified Limit(All Building)</label>
                                    <input type="number" class="form-control" name="classified_limit_all_building" value="<?php echo e($building->classified_limit_all_building); ?>" disabled required>
                                </div>
                            </div>
                            
                           <div class="col-md-4">
    <div class="form-group">
        <label>Call Support Number</label>
        <input type="tel"
            class="form-control"
            name="call_support_number"
            value="<?php echo e($building->call_support_number); ?>"
            pattern="[6-9]{1}[0-9]{9}"
            minlength="10"
            maxlength="10"
            inputmode="numeric"
            title="Enter a valid 10-digit mobile number starting with 6-9"
            required <?php echo e($isBA ? '' : 'disabled'); ?>>
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        <label>Whatsapp Support Number</label>
        <input type="tel"
            class="form-control"
            name="whatsapp_support_number"
            value="<?php echo e($building->whatsapp_support_number); ?>"
            pattern="[6-9]{1}[0-9]{9}"
            minlength="10"
            maxlength="10"
            inputmode="numeric"
            title="Enter a valid 10-digit mobile number starting with 6-9"
            required <?php echo e($isBA ? '' : 'disabled'); ?>>
    </div>
</div>

                           <div class="col-md-4">
                                <div class="form-group">
                                    <label>Treasurer Type</label>
                            
                                    <select name="treasurer_type"
                                            class="form-control"
                                            id="treasurer_type"
                                            <?php echo e(Auth::user()->role !== 'BA' ? 'disabled' : ''); ?>

                                            required>
                                        <option value="BA" <?php echo e(strtolower($building->treasurer_type) == 'ba' ? 'selected' : ''); ?>>BA</option>
                                        <option value="President" <?php echo e(in_array(strtolower($building->treasurer_type), ['president','presedent']) ? 'selected' : ''); ?>>President</option>
                                        <option value="Accounts" <?php echo e(strtolower($building->treasurer_type) == 'accounts' ? 'selected' : ''); ?>>Accounts</option>
                                    </select>
                            
                                    
                                    <?php if( Auth::user()->role !== 'BA'): ?>
                                        <input type="hidden" name="treasurer_type" value="<?php echo e($building->treasurer_type); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Treasurer Person</label>
                                    <div class="treasurer_users">
                                        <?php echo $__env->make('partials.department_users', ['role' => $role ?? null, 'building' => $building ?? null, 'building_users' => $building_users ?? collect()], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php if(Auth::User()->role == 'BA'): ?>
                                <input type="submit" class="btn btn-block bg-gradient-primary btn-flat" value="Save">
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>
            </div>
        </div>
        </section>
        

    <?php $__env->startSection('script'); ?>

     <script>
    $(document).ready(function(){
        var token = "<?php echo e(csrf_token()); ?>";
        var role = $("#treasurer_type").val() || "<?php echo e($building->treasurer_type); ?>";
        var url = "<?php echo e(url('/get-department-users')); ?>";
        var initialTreasurerId = "<?php echo e($building->treasurer_id ?? ''); ?>";
        var buildingDefaultUserId = "<?php echo e($building->user_id ?? ''); ?>";
        var buildingDefaultUserName = "<?php echo e($building->user->name ?? ''); ?>";

        get_department_users();

        $(document).on('change','#treasurer_type',function(){
            role = $(this).val();
            get_department_users();
        });

        function get_department_users()
        {
            // Clear container first to avoid stale options
            $('.treasurer_users').html('<select class="form-control"><option>Loading...</option></select>');

            $.ajax({
                url : url + '/' + encodeURIComponent(role),
                type: "post",
                 data : {'_token':token,'role':role},
                success: function(data)
                {
                    // If server returned HTML string, inject directly
                    if (typeof data === 'string' && data.trim().startsWith('<')) {
                        $('.treasurer_users').html(data);
                        return;
                    }

                    // If server returned JSON, build select
                    if (data && data.building_users) {
                        var html = '<select name="treasurer_person" id="treasurer_person" class="form-control">';

                        // If role not provided or no users, optionally include building owner
                        if (!data.role || data.building_users.length === 0) {
                            if (buildingDefaultUserId) {
                                var sel = (initialTreasurerId == buildingDefaultUserId) ? ' selected' : '';
                                html += '<option value="'+buildingDefaultUserId+'"'+sel+'>'+buildingDefaultUserName+'</option>';
                            }
                        }

                        data.building_users.forEach(function(bu){
                            var uid = bu.user_id || (bu.user && bu.user.id) || '';
                            var name = (bu.user && bu.user.name) ? bu.user.name : (bu.name || 'User');
                            var sel = (initialTreasurerId == uid) ? ' selected' : '';
                            html += '<option value="'+uid+'"'+sel+'>'+name+'</option>';
                        });

                        html += '</select>';
                        $('.treasurer_users').html(html);
                        return;
                    }

                    // Fallback: empty
                    $('.treasurer_users').html('');
                },
                error: function(xhr){
                    $('.treasurer_users').html('');
                }
            });
        }
    });
    </script>

    <?php $__env->stopSection(); ?>

    <?php $__env->stopSection(); ?>





<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/settings/index.blade.php ENDPATH**/ ?>