<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Role | System</title>
    <link rel="stylesheet" href="<?php echo e(asset('public/admin/css/bootstrap.min.css')); ?>">
    <script src="<?php echo e(asset('public/admin/js/jquery.min.js')); ?>"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <style>
    
       .role-card:hover i.fas.fa-chevron-right {
                    color: #111 !important;
                }
        @import    url('https://fonts.googleapis.com/css2?family=Questrial&display=swap');
        *{font-family:Jost;}
        .btn-custom{background-color:black;color:white;}
        .btn-custom:hover{background-color:black;color:white;}
        a{color:black;}
        a:hover{text-decoration:none;}
        .card{box-shadow: 0px 2px red;}
        .role-card {
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: all 0.3s ease;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .role-card:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
          .role-card:hover h5 {
            color: #111 !important;
        }
        
         body {
            font-family: 'Times New Roman', Times, serif;
            background: url('<?php echo e(url("/public/admin/ChatGPT Image Nov 15_ 2025_ 04_23_23 PM.png")); ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(37, 62, 88, 0.6);
            z-index: -1;
        }
        .role-options-container {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
            width: 90%;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 40px 30px 30px 30px;
        }
    </style>
</head>

<body style="background-color:grey;">
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-12 mt-2">
                <center>
                    <div>
                        <h2 style="color: white; margin-bottom: 30px;">
                            <i class="fas fa-user-shield"></i> Select Your Role
                        </h2>
                        <p style="color: white; font-size: 14px; margin-bottom: 20px;">
                            You have multiple roles. Please select the role you want to use for this session.
                        </p>
                    </div>
                </center>
            </div>

            <div class="col-md-12">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8">
              
                      <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                         
                            <?php if($role->name !== 'User' && $role->type !== "issue"): ?>
                                <a href="/select-role/<?php echo e($role->id); ?>" style="text-decoration: none;">
                                    <div class="role-card">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="text-left">
                                                <h5 class="mb-1" style="color: #fff;"><?php echo e($role->name); ?></h5>
                                                <small class="text-muted">
                                                    <?php echo e($role->description ?? 'Click to select'); ?>

                                                </small>
                                            </div>
                                            <i class="fas fa-chevron-right fa-lg" style="color: #fff;"></i>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="alert alert-warning" role="alert">
                                No roles available. Please contact support.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-5">
                <center>
                    <a href="/building-option" class="btn btn-sm btn-secondary pt-2 pl-3 pr-3">
                        <i class="fas fa-arrow-left"></i> Back to Buildings
                    </a>
                </center>
            </div>
        </div>
    </div>
</body>

</html>
<?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/role_option.blade.php ENDPATH**/ ?>