<?php $__env->startSection('title'); ?>
    Profile Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <style>
    .address{
      overflow-wrap: anywhere;
    }
    .password-icon{
      float: right;
      margin-top: 6px;
      margin-left:10px;
    }
    /* Profile image styling */
    .profile-img-fixed {
      width: 120px !important;
      height: 120px !important;
      object-fit: cover;
      border: 3px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .profile-preview-img {
      width: 50px !important;
      height: 50px !important;
      object-fit: cover;
      border-radius: 8px;
      border: 2px solid #ddd;
    }
    /* Make all input, textarea, and select text bold in the profile form */
    /* .card-body input.form-control,
    .card-body textarea.form-control,
    .card-body select.form-control {
      font-weight: bold;
    } */
  </style>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Profile Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Profile Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
         <img class="profile-user-img img-fluid img-circle profile-img-fixed"
           src="<?php echo e($customer->photo); ?>"
           alt="User profile picture">
                </div>
                <h3 class="profile-username text-center"><?php echo e($customer->name); ?></h3>

                <p class="text-muted text-center"><?php echo e($customer->role); ?></p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Email</b> 
                    <a class="float-right address"><?php echo e($customer->email); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Phone</b> <a class="float-right"><?php echo e($customer->phone); ?></a>
                  </li>
                  <li class="list-group-item">
                 
                    <b>Gender</b> <a class="float-right"><?php echo e($customer->gender); ?></a>
                  </li>
                  
                  <!--<li class="list-group-item">-->
                  <!--  <b>Security Amount</b> <a class="float-right"><?php echo e($customer->security_paid == 1 ? 'Paid' : 'Pending'); ?></a>-->
                  <!--</li>-->

                  <li class="list-group-item">
                    <b>Address</b> <a class="float-right address"><?php echo e($customer->address); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right"><?php echo e($customer->status); ?></a>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-9">
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
            <div class="card">
                <div class="card-header"><strong>Edit profile details</strong></div>
                <div class="card-body">
                    <form class="form-horizontal" action="<?php echo e(url('update-profile')); ?>" method="post" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                      <div class="form-group row">
                        <div class="col-sm-6">
                          <label for="inputFirstName" class="col-form-label">First Name</label>
                          <input type="text" class="form-control" id="inputFirstName" name="first_name" placeholder="First Name" value="<?php echo e($customer->first_name); ?>" minlength="3" maxlength="20"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
                        </div>
                        <div class="col-sm-6">
                          <label for="inputLastName" class="col-form-label">Last Name</label>
                          <input type="text" class="form-control" id="inputLastName" name="last_name" placeholder="Last Name" value="<?php echo e($customer->last_name); ?>" minlength="3" maxlength="20"
                          onkeypress="return event.charCode >= 65 && event.charCode <= 90 || event.charCode >= 97 && event.charCode <= 122 || event.charCode == 32" required>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                        <div class="col-sm-10">
                          <input type="email" class="form-control" name="email" placeholder="Email" value="<?php echo e($customer->email); ?>" maxlength="50" required>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label for="inputName2" class="col-sm-2 col-form-label">Phone</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" name="phone" placeholder="Phone" value="<?php echo e($customer->phone); ?>" minlength="10" maxlength="10"  
                                      onkeypress="return event.charCode >= 48 && event.charCode <= 57" required>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label for="inputExperience" class="col-sm-2 col-form-label">Gender</label>
                        <div class="col-sm-10">
                          <select name="gender" class="form-control">
                              <option value="Male" <?php echo e($customer->gender == 'Male' ? 'selected' : ''); ?>>Male</option>
                              <option value="Female" <?php echo e($customer->gender == 'Female' ? 'selected' : ''); ?>>Female</option>
                              <option value="Others" <?php echo e($customer->gender == 'Others' ? 'selected' : ''); ?>>Others</option>
                          </select>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label for="inputName2" class="col-sm-2 col-form-label">Address</label>
                        <div class="col-sm-10">
                          <textarea name="address" class="form-control" minlength="1" placeholder="Address" maxlength="100" required><?php echo e($customer->address); ?></textarea>
                        </div>
                      </div>
                      <div class="form-group row">
                        <label for="inputSkills" class="col-sm-2 col-form-label">Profile Image (max 2 mb)
                            <img src="<?php echo e($customer->photo); ?>" class="profile-preview-img">
                        </label>
                        <div class="col-sm-10">
                          <input type="file" class="form-control" name="photo" id="profileImageInput" accept="image/jpeg,image/png" placeholder="Profile Picture">
                          <small class="form-text text-muted">Supported formats: JPEG, PNG. Max size: 2MB.</small>
                          <div id="profileImagePreview" style="margin-top:10px;"></div>
                          <div id="profileImageSize" style="margin-top:5px; color: #555; font-size: 90%;"></div>
                        </div>
                      </div>
                      <div class="form-group row">
                        <div class="offset-sm-2 col-sm-10">
                          <button type="submit" class="btn btn-sm btn-primary">Update Profile</button>
                        </div>
                      </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><strong>Change Password</strong></div>
                <div class="card-body">
                    <form action="<?php echo e(url('change-password')); ?>" method="post">
                            <?php echo csrf_field(); ?>
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" class="form-control current-password" minlength="8" maxlength="14" id="re_pass" style="width:95%;"
                                    placeholder="Current password" oncopy="return false" onpaste="return false" oncut="return false" ondrop="return false" required>
                                    <a href="javascript:void(0)" class="current-show-password password-icon"><i class="fa fa-eye-slash"></i></a>
                                    <a href="javascript:void(0)" class="current-hide-password password-icon" style="display:none;"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                </div>
                                <?php if($errors->has('current_password')): ?>
                                    <p style="color:red"><?php echo e($errors->first('current_password')); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control new-password" minlength="8" maxlength="14" id="re_pass" style="width:95%;" 
                                    placeholder="New Password" oncopy="return false" onpaste="return false" oncut="return false" ondrop="return false" autocomplete="new-password" required>
                                    <a href="javascript:void(0)" class="new-show-password password-icon"><i class="fa fa-eye-slash"></i></a>
                                    <a href="javascript:void(0)" class="new-hide-password password-icon" style="display:none;"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                </div>
                                <p style="color:grey">Enter a combination of at least eight characters and punctuation marks (such as ! and &)</p>
                                <?php if($errors->has('password')): ?>
                                    <p style="color:red"><?php echo e($errors->first('password')); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" class="form-control confirm-password" minlength="8" maxlength="14" id="re_pass" style="width:95%;" 
                                    placeholder="Confirm password" oncopy="return false" onpaste="return false" oncut="return false" ondrop="return false" autocomplete="new-password" required>
                                    <a href="javascript:void(0)" class="confirm-show-password password-icon"><i class="fa fa-eye-slash"></i></a>
                                    <a href="javascript:void(0)" class="confirm-hide-password password-icon" style="display:none;"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                </div>
                                <?php if($errors->has('password_confirmation')): ?>
                                    <p style="color:red"><?php echo e($errors->first('password_confirmation')); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-sm btn-primary" value="Change Password">
                            </div>
                        </form>
                </div>
            </div>
            
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
      $('.hide-password').hide();

      // Password show/hide logic
      $(document).on('click','.current-show-password',function(){
        $('.current-password').attr('type','text');
        $(this).hide();
        $('.current-hide-password').show();
      });
      $(document).on('click','.current-hide-password',function(){
        $('.current-password').attr('type','password');
        $(this).hide();
        $('.current-show-password').show();
      });
      $(document).on('click','.new-show-password',function(){
        $('.new-password').attr('type','text');
        $(this).hide();
        $('.new-hide-password').show();
      });
      $(document).on('click','.new-hide-password',function(){
        $('.new-password').attr('type','password');
        $(this).hide();
        $('.new-show-password').show();
      });
      $(document).on('click','.confirm-show-password',function(){
        $('.confirm-password').attr('type','text');
        $(this).hide();
        $('.confirm-hide-password').show();
      });
      $(document).on('click','.confirm-hide-password',function(){
        $('.confirm-password').attr('type','password');
        $(this).hide();
        $('.confirm-show-password').show();
      });

      // Client-side email validation
      $('form[action$="update-profile"]').on('submit', function(e) {
        var email = $(this).find('input[name="email"]').val();
        // Only check for valid email format (no uppercase required)
        var emailFormat = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailFormat.test(email)) {
          alert('Please enter a valid email address.');
          $(this).find('input[name="email"]').focus();
          e.preventDefault();
          return false;
        }

        // Phone number validation: must be exactly 10 digits and numeric
        var phone = $(this).find('input[name="phone"]').val();
        var phoneRegex = /^\d{10}$/;
        if (!phoneRegex.test(phone)) {
          alert('Please enter a valid 10-digit phone number.');
          $(this).find('input[name="phone"]').focus();
          e.preventDefault();
          return false;
        }

        // Address validation: only allow valid English address characters (letters, numbers, spaces, comma, period, dash, #, /)
        var address = $(this).find('textarea[name="address"]').val();
        var addressRegex = /^[a-zA-Z0-9\s,\.#\/-]+$/;
        if (!addressRegex.test(address)) {
          alert('Please enter the address using only valid English characters (no special or non-English characters).');
          $(this).find('textarea[name="address"]').focus();
          e.preventDefault();
          return false;
        }
      });
    });
    // Image file type and size check, preview, and size display
    $('#profileImageInput').on('change', function() {
      var file = this.files[0];
      var previewDiv = $('#profileImagePreview');
      var sizeDiv = $('#profileImageSize');
      previewDiv.empty();
      sizeDiv.empty();
      if (file) {
        var validTypes = ['image/jpeg', 'image/png'];
        if ($.inArray(file.type, validTypes) === -1) {
          alert('Only JPEG and PNG image formats are supported.');
          $(this).val('');
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          alert('File size must be less than 2MB.');
          $(this).val('');
          return;
        }
        // Show image preview
        var reader = new FileReader();
        reader.onload = function(e) {
          previewDiv.html('<img src="' + e.target.result + '" class="profile-preview-img" alt="Preview">');
        };
        reader.readAsDataURL(file);
        // Show file size
        var sizeKB = (file.size / 1024).toFixed(1);
        sizeDiv.text('File size: ' + sizeKB + ' KB');
      }
    });
    // Auto-hide Laravel alert messages after 2 seconds
    setTimeout(function() {
      $('.alert-success, .alert-danger').fadeOut('slow');
    }, 2000);
  </script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/profile.blade.php ENDPATH**/ ?>