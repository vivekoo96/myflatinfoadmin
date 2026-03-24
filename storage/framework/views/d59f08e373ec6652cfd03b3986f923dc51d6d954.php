<?php $__env->startSection('title'); ?>
    Facility Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- Ensure FontAwesome is loaded -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
.is-invalid {
    border-color: #dc3545 !important;
}
.invalid-feedback {
    display: block !important;
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

/* Action buttons styling */
.btn-group .btn,
.btn-group-vertical .btn {
    margin-right: 2px;
    margin-bottom: 2px;
}
.btn-group .btn:last-child,
.btn-group-vertical .btn:last-child {
    margin-right: 0;
}
.btn-group .btn i,
.btn-group-vertical .btn i {
    font-size: 14px;
    font-weight: bold;
    margin-right: 4px;
}

/* Ensure FontAwesome icons are visible and properly loaded */
.btn i.fas,
.btn i.fa {
    display: inline-block !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    font-family: "Font Awesome 5 Free" !important;
    font-weight: 900 !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Specific icon fixes */
.fas.fa-check::before { content: "\f00c"; }
.fas.fa-times::before { content: "\f00d"; }
.fas.fa-pause::before { content: "\f04c"; }
.fas.fa-play::before { content: "\f04b"; }
.fas.fa-ban::before { content: "\f05e"; }
.fas.fa-eye::before { content: "\f06e"; }
.fas.fa-edit::before { content: "\f044"; }

/* Action column styling */
.action-column {
    min-width: 200px !important;
    max-width: 250px;
    text-align: center;
    padding: 8px !important;
}

.action-buttons-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
}

.approval-actions,
.standard-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    justify-content: center;
}

/* Button styling */
.action-buttons-container .btn {
    font-size: 10px !important;
    padding: 3px 6px !important;
    border-radius: 3px !important;
    margin: 1px !important;
    min-width: 30px;
    white-space: nowrap;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
}

/* Icon styling - compatible with both fa and fas */
.action-buttons-container .btn i {
    font-size: 10px !important;
    margin-right: 2px !important;
    display: inline-block !important;
    font-family: FontAwesome, "Font Awesome 5 Free", "Font Awesome 4" !important;
    font-weight: normal !important;
    font-style: normal !important;
}

/* Ensure icons are visible with fallback */
.action-buttons-container .btn i.fa:before,
.action-buttons-container .btn i.fas:before {
    font-family: FontAwesome, "Font Awesome 5 Free" !important;
    font-weight: 900 !important;
}

/* Specific button colors with high specificity */
.action-buttons-container .approve-booking { 
    background-color: #28a745 !important; 
    color: white !important; 
    border-color: #28a745 !important;
}
.action-buttons-container .deny-booking { 
    background-color: #dc3545 !important; 
    color: white !important; 
    border-color: #dc3545 !important;
}
.action-buttons-container .pause-booking { 
    background-color: #ffc107 !important; 
    color: #212529 !important; 
    border-color: #ffc107 !important;
}
.action-buttons-container .resume-booking { 
    background-color: #28a745 !important; 
    color: white !important; 
    border-color: #28a745 !important;
}
.action-buttons-container .cancel-booking { 
    background-color: #dc3545 !important; 
    color: white !important; 
    border-color: #dc3545 !important;
}
.action-buttons-container .view-booking { 
    background-color: #17a2b8 !important; 
    color: white !important; 
    border-color: #17a2b8 !important;
}
.action-buttons-container .edit-booking { 
    background-color: #007bff !important; 
    color: white !important; 
    border-color: #007bff !important;
}

/* Action column width */
.table th:last-child,
.table td:last-child {
    min-width: 150px;
    text-align: center;
}

/* Tooltip styling */
.btn[data-toggle="tooltip"] {
    position: relative;
}

/* Booking Amount column styling */
.booking-amount-column {
    min-width: 100px;
    max-width: 120px;
    text-align: center;
    font-size: 12px;
}
.booking-amount-column .badge {
    font-size: 11px;
    font-weight: bold;
}
.booking-amount-column small {
    font-size: 9px;
    color: #666;
    display: block;
    margin-top: 2px;
}

/* Refunded Amount column styling */
.refunded-amount-column {
    min-width: 110px;
    max-width: 130px;
    text-align: center;
    font-size: 12px;
}
.refunded-amount-column .badge {
    font-size: 11px;
    font-weight: bold;
}
.refunded-amount-column small {
    font-size: 9px;
    color: #666;
    display: block;
    margin-top: 2px;
}

/* Cancelled Time column styling */
.cancelled-time-column {
    min-width: 100px;
    max-width: 120px;
    text-align: center;
    font-size: 12px;
}
.cancelled-time-column .badge {
    font-size: 10px;
    font-weight: bold;
}
.cancelled-time-column small {
    font-size: 9px;
    color: #666;
    display: block;
    margin-top: 2px;
}

/* Cancellation column styling */
.cancellation-column {
    min-width: 120px;
    max-width: 150px;
    font-size: 12px;
}
.cancellation-info {
    font-size: 12px;
    line-height: 1.4;
}
.cancellation-info div {
    margin-bottom: 2px;
}
.cancellation-info small {
    font-size: 10px;
    font-weight: normal;
    color: #666;
}
.cancellation-info .fw-bold {
    font-weight: 600;
    color: #333;
}

/* Custom table styling to replace Bootstrap table class */
.custom-table-init {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
    background-color: transparent;
}

.custom-table-init th,
.custom-table-init td {
    padding: 0.75rem;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    text-align: left;
}

.custom-table-init thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.custom-table-init tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}

.custom-table-init tbody + tbody {
    border-top: 2px solid #dee2e6;
}

/* Specific column alignments */
.custom-table-init td:nth-child(1),
.custom-table-init td:nth-child(2),
.custom-table-init td:nth-child(3) {
    text-align: left;
}

.custom-table-init td:nth-child(4),
.custom-table-init td:nth-child(5),
.custom-table-init td:nth-child(6),
.custom-table-init td:nth-child(7),
.custom-table-init td:nth-child(8),
.custom-table-init td:nth-child(9) {
    text-align: center;
}

.custom-table-init td:nth-child(10) {
    text-align: left;
}

.custom-table-init td:nth-child(11),
.custom-table-init td:nth-child(12),
.custom-table-init td:nth-child(13),
.custom-table-init td:nth-child(14),
.custom-table-init td:nth-child(15) {
    text-align: center;
}

/* DataTable wrapper styling */
#example1_wrapper {
    margin-top: 1rem;
}

#example1_wrapper .row {
    margin: 0;
}

#example1_wrapper .col-md-6 {
    padding: 0.5rem;
}

/* Ensure proper table alignment */
.table-responsive {
    border: none;
}

.dataTables_wrapper .dataTables_paginate {
    text-align: center;
    margin-top: 1rem;
}

/* Dates column styling */
.dates-column {
    min-width: 150px;
    max-width: 200px;
    text-align: left;
    padding: 8px !important;
}

.dates-display {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 12px;
    line-height: 1.4;
    word-wrap: break-word;
    cursor: help;
}

.dates-display strong {
    color: #495057;
    font-weight: 600;
}

.dates-display:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
}

/* Timing columns styling */
.timing-column {
    min-width: 80px;
    text-align: center;
    font-weight: bold;
    color: #007bff;
    background-color: #f8f9fa;
}

.timing-column strong {
    font-size: 14px;
    color: #495057;
}

/* Table header styling for dates and timing columns */
.table thead th:nth-child(2) {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: center;
    color: #495057;
    border: 1px solid #dee2e6;
}

.table thead th:nth-child(3),
.table thead th:nth-child(4) {
    background-color: #e9ecef;
    font-weight: bold;
    text-align: center;
    color: #495057;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .cancellation-info {
        font-size: 11px;
    }
    .cancellation-info small {
        font-size: 9px;
    }
    
    #example1_wrapper .col-md-6 {
        padding: 0.25rem;
    }
    
    .timing-column {
        min-width: 70px;
        font-size: 12px;
    }
}
</style>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Facility Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Facility Details</li>
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

             <!--Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle"
                       src="<?php echo e($facility->image); ?>"
                       alt="User profile picture">
                </div>
                <h3 class="profile-username text-center"><?php echo e($facility->name); ?></h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Max Booking</b> <a class="float-right"><?php echo e($facility->max_booking); ?></a>
                  </li>
                 
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right"><?php echo e($facility->status); ?></a>
                  </li>
                </ul>
              </div>
               <!--/.card-body -->
            </div>
             <!--/.card -->

          </div>
           <!--/.col -->
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
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#timings" data-toggle="tab">Timings</a></li>
                  <li class="nav-item"><a class="nav-link" href="#bookings" data-toggle="tab">Bookings</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="timings">
                   <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Facility"): ?>
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Timing</button>
                    <?php endif; ?>
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Dates</th>
                              <th>From Time</th>
                              <th>To Time</th>
                              <th>Booking Option</th>
                              <th>Booking Type</th>
                              <th>Price</th>
                              <th>Cancellation Type</th>
                              <th>Cancellation Value</th>
                              <th>Status</th>.
                               <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Facility"): ?>
                         
                              <th>Action</th> 
                              <?php endif; ?>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0; ?>
                        <?php $__empty_1 = true; $__currentLoopData = $facility->timings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $i++ ?>
                        <tr>
                          <td><?php echo e($i); ?></td>
                          <td class="dates-column">
                            <?php
                              $dates = $timing->dates;
                              $displayDates = '';
                              
                              try {
                                if (is_string($dates)) {
                                  // Try to parse as JSON first
                                  if (str_starts_with($dates, '[') && str_ends_with($dates, ']')) {
                                    $dateArray = json_decode($dates, true);
                                  } else {
                                    // Fallback to comma-separated parsing
                                    $dateArray = explode(',', $dates);
                                  }
                                  
                                  if (is_array($dateArray)) {
                                    $formattedDates = array_map(function($date) {
                                      return \Carbon\Carbon::parse(trim($date))->format('M d, Y');
                                    }, $dateArray);
                                    $displayDates = implode(', ', array_slice($formattedDates, 0, 3));
                                    $allDatesFormatted = implode(', ', $formattedDates);
                                    $hasMoreDates = count($formattedDates) > 3;
                                    $moreCount = count($formattedDates) - 3;
                                  } else {
                                    $displayDates = $dates;
                                    $allDatesFormatted = $dates;
                                    $hasMoreDates = false;
                                    $moreCount = 0;
                                  }
                                } else {
                                  $displayDates = $dates;
                                }
                              } catch (Exception $e) {
                                // Fallback to raw display if parsing fails
                                $displayDates = $timing->dates;
                              }
                            ?>
                            <div class="dates-display" title="<?php echo e($timing->dates); ?>">
                              <span class="dates-short">
                                <strong><?php echo e($displayDates); ?></strong>
                                <?php if($hasMoreDates): ?>
                                  <span class="more-dates-btn" data-timing-id="<?php echo e($timing->id); ?>" data-all-dates="<?php echo e($allDatesFormatted); ?>">
                                    <strong style="color: #007bff; cursor: pointer; text-decoration: underline;">
                                      +<?php echo e($moreCount); ?> more
                                    </strong>
                                  </span>
                                <?php endif; ?>
                              </span>
                              <span class="dates-full" style="display: none;">
                                <strong><?php echo e($allDatesFormatted ?? $displayDates); ?></strong>
                                <?php if($hasMoreDates): ?>
                                  <span class="less-dates-btn" data-timing-id="<?php echo e($timing->id); ?>" style="color: #007bff; cursor: pointer; text-decoration: underline; margin-left: 5px;">
                                    <strong>show less</strong>
                                  </span>
                                <?php endif; ?>
                              </span>
                            </div>
                          </td>
                          <td class="timing-column"><strong><?php echo e(\Carbon\Carbon::parse($timing->from)->format('h:i A')); ?></strong></td>
                          <td class="timing-column"><strong><?php echo e(\Carbon\Carbon::parse($timing->to)->format('h:i A')); ?></strong></td>
                          <td><?php echo e($timing->booking_option); ?></td>
                          <td>
                            <span class="badge badge-<?php echo e($timing->booking_type == 'Free' ? 'info' : 'success'); ?>">
                              <?php echo e($timing->booking_type); ?>

                            </span>
                          </td>
                          <td>
                            <?php if($timing->booking_type == 'Free'): ?>
                              <span class="text-muted">Free</span>
                            <?php else: ?>
                              ₹<?php echo e($timing->price); ?>

                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if($timing->booking_type == 'Free'): ?>
                              <span class="text-muted">N/A</span>
                            <?php else: ?>
                              <?php echo e($timing->cancellation_type); ?>

                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if($timing->booking_type == 'Free'): ?>
                              <span class="text-muted">N/A</span>
                            <?php else: ?>
                              <?php if($timing->cancellation_type == 'Percentage'): ?>
                                <?php echo e($timing->cancellation_value); ?>%
                              <?php elseif($timing->cancellation_type == 'Fixed'): ?>
                                ₹<?php echo e($timing->cancellation_value); ?>

                              <?php else: ?>
                                <?php echo e($timing->cancellation_value); ?>

                              <?php endif; ?>
                            <?php endif; ?>
                          </td>
                          <td><?php echo e($timing->status); ?></td>
                          <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Facility"): ?>
                          <td>
                   
                        <div class="btn-group" role="group">
                          <button class="btn btn-sm btn-primary edit-timing"
                            data-toggle="modal"
                            data-target="#addModal"
                            data-id="<?php echo e($timing->id); ?>"
                            data-from="<?php echo e(\Carbon\Carbon::parse($timing->from)->format('H:i')); ?>"
                            data-to="<?php echo e(\Carbon\Carbon::parse($timing->to)->format('H:i')); ?>"
                            data-dates="<?php echo e($timing->dates); ?>"
                            data-status="<?php echo e($timing->status); ?>"
                            data-booking_option="<?php echo e($timing->booking_option); ?>"
                            data-booking_type="<?php echo e($timing->booking_type); ?>"
                            data-price="<?php echo e($timing->price); ?>"
                            data-cancellation_type="<?php echo e($timing->cancellation_type); ?>"
                            data-cancellation_value="<?php echo e($timing->cancellation_value); ?>"
                            title="Edit Timing">
                            <i class="fas fa-edit"></i>
                          </button>
                          <?php if($timing->deleted_at): ?>
                              <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($timing->id); ?>" data-action="restore" title="Restore Timing"><i class="fas fa-undo"></i></button>
                          <?php else: ?>
                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?php echo e($timing->id); ?>" data-action="delete" title="Delete Timing"><i class="fas fa-trash"></i></button>
                          <?php endif; ?>
                          <button class="btn btn-sm btn-warning open-cancel-modal" data-toggle="modal" data-target="#cancelModal" data-id="<?php echo e($timing->id); ?>" data-dates="<?php echo e($timing->dates); ?>" title="Cancel Slot Bookings"><i class="fas fa-ban"></i></button>
                        </div>
                        <?php else: ?>
                          <span class="text-muted">No actions available</span>
                        </td>
                        </tr>
                         <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                  <div class="tab-pane" id="bookings">
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Booking</button>-->
                    <div class="table-responsive">
                    <!-- Table -->
                    <table class="table table-bordered table-striped custom-datatable">
                  <thead>
                  <tr>
                    <!-- <th>S No</th> -->
                    <!-- <th>Facility</th> -->
                    <th >Booked By</th>
                    <th>Flat</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Members</th>
                    <th>Booking Amount</th>
                    <th>Refunded Amount</th>
                    <th>Booked on</th>
                    <th>Cancelled Time</th>
                    <th>Status</th>
                    <th>Cancellation Policy</th>
                    <!--<th>Action</th>-->
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $facility->bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                    <!-- <td><?php echo e($i); ?></td> -->
                    <!--  <td><a href="<?php echo e(url('facility',$booking->facility_id)); ?>" target="_blank"><?php echo e($booking->facility->name); ?></a></td> -->
                    <td><a href="<?php echo e(url('user',$booking->user_id)); ?>" target="_blank"><?php echo e($booking->user->name); ?></a></td>
                    <td><a href="<?php echo e(url('flat',$booking->flat_id)); ?>" target="_blank"><?php echo e($booking->flat->name); ?></a></td>
                    <td><?php echo e($booking->date); ?></td>
                    <td><?php echo e($booking->timing->from); ?></td>
                    <td><?php echo e($booking->timing->to); ?></td>
                    <td><?php echo e($booking->members); ?></td>
                    <td class="booking-amount-column">
                      <?php if($booking->timing && $booking->timing->booking_type == 'Paid'): ?>
                        <span class="badge badge-success">₹<?php echo e($booking->timing->price ?? 0); ?></span>
                        <br><small class="text-muted"><?php echo e($booking->timing->booking_option ?? 'N/A'); ?></small>
                      <?php elseif($booking->timing && $booking->timing->booking_type == 'Free'): ?>
                        <span class="badge badge-info">Free</span>
                        <br><small class="text-muted"><?php echo e($booking->timing->booking_option ?? 'N/A'); ?></small>
                      <?php else: ?>
                        <span class="badge badge-secondary">N/A</span>
                      <?php endif; ?>
                    </td>
                    <td class="refunded-amount-column">
                      <?php if($booking->status == 'Cancelled' && $booking->timing && $booking->timing->booking_type == 'Paid'): ?>
                        <?php
                          $originalPrice = $booking->timing->price ?? 0;
                          $refundAmount = 0;
                          
                          if($booking->timing->cancellation_type == 'Percentage') {
                            $cancellationPercentage = $booking->timing->cancellation_value ?? 0;
                            $refundAmount = $originalPrice - ($originalPrice * $cancellationPercentage / 100);
                          } elseif($booking->timing->cancellation_type == 'Fixed') {
                            $cancellationFee = $booking->timing->cancellation_value ?? 0;
                            $refundAmount = max(0, $originalPrice - $cancellationFee);
                          } elseif($booking->timing->cancellation_type == 'Manual') {
                            $refundAmount = $originalPrice; // Full refund for manual cancellation
                          }
                        ?>
                        <span class="badge badge-warning">₹<?php echo e(number_format($refundAmount, 2)); ?></span>
                        <br><small class="text-muted"><?php echo e($booking->timing->cancellation_type); ?> Policy</small>
                      <?php elseif($booking->status == 'Cancelled' && $booking->timing && $booking->timing->booking_type == 'Free'): ?>
                        <span class="badge badge-info">Free Booking</span>
                        <br><small class="text-muted">No Refund</small>
                      <?php elseif($booking->status == 'Cancelled'): ?>
                        <span class="badge badge-secondary">N/A</span>
                      <?php else: ?>
                        <span class="badge badge-light">-</span>
                        <br><small class="text-muted">Not Cancelled</small>
                      <?php endif; ?>
                    </td>
                    <td><?php echo e($booking->created_at->diffForHumans()); ?></td>
                    <td class="cancelled-time-column">
                      <?php if($booking->status == 'Cancelled'): ?>
                        <?php if($booking->updated_at && $booking->updated_at != $booking->created_at): ?>
                          <span class="badge badge-danger"><?php echo e($booking->updated_at->format('M d, Y')); ?></span>
                          <br><small class="text-muted"><?php echo e($booking->updated_at->format('h:i A')); ?></small>
                        <?php else: ?>
                          <span class="badge badge-secondary">N/A</span>
                          <br><small class="text-muted">No Record</small>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge badge-light">-</span>
                        <br><small class="text-muted">Not Cancelled</small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if($booking->status == 'Pending' && (Auth::User()->role == 'BA' || Auth::User()->hasRole('facility'))): ?>
                        <span class="badge badge-warning"><?php echo e($booking->status); ?></span>
                        <br><small class="text-muted">Awaiting Approval</small>
                      <?php else: ?>
                        <span class="badge badge-<?php echo e($booking->status == 'Active' ? 'success' : ($booking->status == 'Cancelled' ? 'danger' : ($booking->status == 'Paused' ? 'warning' : 'secondary'))); ?>">
                          <?php echo e($booking->status); ?>

                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="cancellation-column" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                      <?php if(isset($booking->timing) && $booking->timing): ?>
                        <div class="cancellation-info">
                          <div class="mb-1">
                            <small class="text-muted">Type:</small> 
                            <span class="badge badge-info"><?php echo e($booking->timing->cancellation_type ?? 'N/A'); ?></span>
                          </div>
                          <div>
                            <small class="text-muted">Value:</small> 
                            <span class="badge badge-warning">
                              <?php if($booking->timing->cancellation_type == 'Percentage'): ?>
                                <?php echo e($booking->timing->cancellation_value ?? 0); ?>%
                              <?php elseif($booking->timing->cancellation_type == 'Fixed'): ?>
                                ₹<?php echo e($booking->timing->cancellation_value ?? 0); ?>

                              <?php elseif($booking->timing->cancellation_type == 'Manual'): ?>
                                Manual
                              <?php else: ?>
                                <?php echo e($booking->timing->cancellation_value ?? 'N/A'); ?>

                              <?php endif; ?>
                            </span>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="badge badge-secondary">No Policy</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <?php endif; ?>
                  </tbody>
                </table>

                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    
<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><strong>Add New Timing</strong></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(route('timing.store')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          

        <div class="error text-danger"></div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label>Select Dates (click to select/unselect):</label>
                  <input type="text" id="calendar" class="form-control" placeholder="Pick Dates" readonly>
                  <input type="hidden" name="selected_dates" id="selected_dates" required>
                </div>
                
                <div class="row">
                  <div class="col-md-6 form-group">
                    <label>From Date:</label>
                    <input type="date" id="from_date" class="form-control">
                  </div>
                  <div class="col-md-6 form-group">
                    <label>To Date:</label>
                    <input type="date" id="to_date" class="form-control">
                  </div>
                </div>
                
                <button type="button" class="btn btn-info btn-sm mb-2" id="applyRange">Apply Range</button>
                    
                    <div id="time-slots-wrapper">
                      <div class="time-slot-row mb-3">
                        <div class="row">
                          <div class="col-md-6">
                            <label for="from" class="col-form-label">From Time:</label>
                            <input type="time" name="from_times[]" class="form-control from-time" id="from" required>
                          </div>
                          <div class="col-md-6">
                            <label for="to" class="col-form-label">To Time:</label>
                            <input type="time" name="to_times[]" class="form-control to-time" id="to" required>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <button type="button" class="btn btn-success btn-sm mb-2 d-none" id="add-time-slot">+ Add More Time Slot</button>

            </div>

            <div class="col-md-6 pl-5">
          
              <div class="form-group">
                <label for="booking_option" class="col-form-label">Booking Option:</label>
                <select name="booking_option" class="form-control" id="booking_option">
                  <option value="daily">Per Day</option>
                  <option value="slotwise">Per Slot</option>
                  <option value="both">Both</option>
                </select>
              </div>
              <div class="form-group">
                <label for="name" class="col-form-label">Booking Type:</label>
                <select name="booking_type" id="booking_type" class="form-control" required>
                    <option value="" disabled selected>Select Booking Type</option>
                    <option value="Free">Free</option>
                    <option value="Paid">Paid</option>
                </select>
              </div>
              <div class="form-group price">
                <label for="name" class="col-form-label">Price:</label>
                <input type="number" name="price" id="price" class="form-control" placeholder="Price" min="1" required>
              </div>
              <div class="form-group cancellation_type">
                <label for="name" class="col-form-label">Cancellation Type:</label>
                <select name="cancellation_type" id="cancellation_type" class="form-control" required>
                    <!--<option value="N/A">Free Cancellation</option>-->
                    <option value="Fixed">Fixed</option>
                    <option value="Percentage">Percentage</option>
                    <option value="Manual">Manual</option>
                </select>
              </div>
              <div class="form-group cancellation_value">
                <label for="name" class="col-form-label">Cancellation Value:</label>
                <input type="number" name="cancellation_value" id="cancellation_value" class="form-control" placeholder="Enter value" min="0" required>
                <small class="form-text text-muted" id="cancellation_help">Not applicable for free bookings</small>
              </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="status" class="col-form-label">Status:</label>
                <select name="status" class="form-control" id="status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>
        </div>
          
          <input type="hidden" name="timing_id" id="edit-id">
          <input type="hidden" name="facility_id" id="facility_id" value="<?php echo e($facility->id); ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelModalLabel">Cancel Slot Bookings</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form action="<?php echo e(url('cancel-slot-booking')); ?>" method="post" class="cancel-form">
        <?php echo csrf_field(); ?>
        <div class="modal-body">
          <div class="error text-danger mb-2"></div>

          <div class="row">
            <div class="col-md-12">
              <!-- Select Date -->
              <div class="form-group">
                <label for="booking_dates" class="col-form-label">Select Date:(Selected date is no longer available) </label>
                <select name="date" class="form-control" id="booking_dates" required>
                  <option value="">-- Select Date --</option>
                </select>
              </div>

              <!-- Reason -->
              <div class="form-group">
                <label for="reason" class="col-form-label">Reason:(This will cancel all bookings with 100% refund) </label>
                <textarea name="reason" class="form-control" id="reason" placeholder="Enter reason for cancellation" required></textarea>
              </div>
            </div>
          </div>

          <input type="hidden" name="timing_id" id="cancel-edit-id">
          <input type="hidden" name="facility_id" id="cancel-facility_id" value="<?php echo e($facility->id); ?>">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-danger" id="save-button">Cancel Slot Booking</button>
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

<?php $__env->startSection('script'); ?>

<!-- Prevent global DataTable initialization for our custom table -->
<script>
// Mark our table to prevent global initialization but keep Bootstrap styling
$(function() {
    // Add a flag to prevent duplicate initialization
    $('#example1').attr('data-prevent-global-init', 'true');
    
    // Temporarily hide the table during initialization to prevent visual glitches
    $('#example1').css('visibility', 'hidden');
});
</script>

<script>

  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    let selectedDates = [];
    let calendar = null;
    
    // Prevent global DataTable initialization for our custom table
    $('#example1').addClass('no-global-datatable');
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
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
      if (!id) {
        alert('No timing selected for deletion');
        return;
      }
      
      var url = "<?php echo e(url('timing')); ?>/" + id;
      console.log('Attempting to delete timing with ID:', id, 'Action:', action, 'URL:', url);
      
      $.ajax({
        url : url,
        type: "DELETE",
        data : {'_token':token,'action':action},
        success: function(data)
        {
          console.log('Delete successful:', data);
          window.location.reload();
        },
        error: function(xhr, status, error) {
          console.error('Delete failed:', xhr.responseText);
          alert('Failed to delete timing: ' + (xhr.responseJSON?.message || error));
        }
      });
    });
    
    $('#cancelModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const edit_id = button.data('id');
        let dates = button.data('dates');
    
        $("#cancel-edit-id").val(edit_id);
    
        const $select = $("#booking_dates");
        $select.empty().append('<option value="">-- Select Date --</option>');
        
        if (Array.isArray(dates)) {
            dates.forEach(date => {
                $select.append(`<option value="${date}">${date}</option>`);
            });
        }
    });


    $('#addModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const edit_id = button.data('id') || '';
        const modal = $(this);
    
        // Reset form fields
        $('.price, .cancellation_type, .cancellation_value').hide();
        $('#price, #cancellation_type, #cancellation_value').attr('required', false);
        $('#price').attr('min', 0);
        $('#edit-id').val('');
        $('#calendar').val('');
        $("#selected_dates").val('');
        modal.find('.modal-title').text(edit_id ? 'Update Timing' : 'Add New Timing');
        modal.find('#icon').attr('required', !edit_id);
    
        if (edit_id) {
            modal.find('#edit-id').val(edit_id);
            modal.find('#from').val(button.data('from'));
            modal.find('#to').val(button.data('to'));
            modal.find('#status').val(button.data('status'));
            modal.find('#booking_type').val(button.data('booking_type'));
            modal.find('#booking_option').val(button.data('booking_option'));
            modal.find('#price').val(button.data('price'));
            modal.find('#cancellation_type').val(button.data('cancellation_type'));
            modal.find('#cancellation_value').val(button.data('cancellation_value'));
            
            var booking_type = button.data('booking_type');
            if(booking_type == 'Paid'){
                $('.price, .cancellation_type, .cancellation_value').show();
                $('#price, #cancellation_type, #cancellation_value').attr('required', true);
                $('#cancellation_value').attr('readonly', false);
            } else {
                // Free booking type
                $('.price').hide();
                $('.cancellation_type, .cancellation_value').show();
                $('#price').attr('required', false).val(0);
                $('#cancellation_type').attr('required', true);
                $('#cancellation_value').attr('required', true).attr('readonly', true);
            }
            
            var cancellation_type = button.data('cancellation_type');
            if(cancellation_type == 'Manual'){
                $('.cancellation_value').hide();
                $('#cancellation_value').attr('required', false);
            } else if(cancellation_type == 'N/A' || booking_type == 'Free'){
                $('#cancellation_value').attr('readonly', true);
            }
        }
    
        let dates = button.data('dates');
        if (typeof dates === 'string') {
            try {
                dates = JSON.parse(dates);
            } catch (e) {
                dates = dates.split(',');
            }
        }
        if (!Array.isArray(dates)) {
            dates = [];
        }
    
        // ✅ Always use plain strings (not Date objects)
        const dateStrings = dates.map(d => {
            if (typeof d === 'string') return d;
            return flatpickr.formatDate(d, "Y-m-d");
        });
    
        modal.find('#selected_dates').val(dateStrings.join(','));
    
        // ✅ Destroy previous instance safely
        if (calendar && typeof calendar.destroy === 'function') {
            calendar.destroy();
            calendar = null;
        }
    
        calendar = flatpickr("#calendar", {
            mode: "multiple",
            dateFormat: "Y-m-d",
            defaultDate: dateStrings,
            onChange: function (selectedDatesArr, dateStr, instance) {
                const selected = selectedDatesArr.map(d =>
                    instance.formatDate(d, "Y-m-d")
                );
                $('#selected_dates').val(selected.join(','));
            }
        });
    
        $('.modal-backdrop').slice(1).remove();
    });




    // Handle booking_type change dynamically
    $('#booking_type').on('change', function () {
        let type = $(this).val();
        if (type === 'Paid') {
            $('.price, .cancellation_type, .cancellation_value').show();
            $('#price').attr('required', true).attr('min', 1).val('');
            $('#cancellation_type, #cancellation_value').attr('required', true);
            $('#cancellation_type').val('Fixed').attr('disabled', false);
            $('#cancellation_value').val('').attr('min', 0).attr('readonly', false);
            $('#cancellation_help').text('Enter cancellation fee or percentage');
        } else {
            // For Free booking type
            $('.price').hide();
            $('.cancellation_type, .cancellation_value').show();
            $('#price').attr('required', false).attr('min', 0).val(0);
            $('#cancellation_type').attr('required', false).val('N/A').attr('disabled', true);
            $('#cancellation_value').attr('required', false).val(0).attr('readonly', true);
            $('#cancellation_help').text('Not applicable for free bookings');
        }
    });
    
    $('#cancellation_type').on('change', function () {
        let type = $(this).val();
        let bookingType = $('#booking_type').val();
        
        if (type === 'N/A' || bookingType === 'Free') {
            $('#cancellation_value').val(0).attr('readonly', true).attr('required', true);
            $('#cancellation_help').text('Not applicable for free bookings');
        } else if (type === 'Manual') {
            $('.cancellation_value').hide();
            $('#cancellation_value').attr('required', false).attr('readonly', false);
        } else {
            $('.cancellation_value').show();
            $('#cancellation_value').attr('required', true).attr('readonly', false);
            if (type === 'Percentage') {
                $('#cancellation_help').text('Enter percentage (0-100)');
            } else if (type === 'Fixed') {
                $('#cancellation_help').text('Enter fixed amount in rupees');
            }
        }
    });

    // Real-time validation for cancellation value
    $('#cancellation_value').on('input change', function () {
        const type = $('#cancellation_type').val();
        const value = parseFloat($(this).val());
        
        if (type === 'Percentage') {
            if (value > 100) {
                $(this).addClass('is-invalid');
                if (!$(this).siblings('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Percentage cannot exceed 100%</div>');
                }
            } else if (value < 0) {
                $(this).addClass('is-invalid');
                if (!$(this).siblings('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Percentage cannot be negative</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        } else if (type === 'Fixed') {
            if (value < 0) {
                $(this).addClass('is-invalid');
                if (!$(this).siblings('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Fixed amount cannot be negative</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        }
    });

    $('#applyRange').on('click', function() {
        const from = $('#from_date').val();
        const to = $('#to_date').val();
    
        if (from && to && from <= to) {
            const fromDate = new Date(from);
            const toDate = new Date(to);
            const datesToAdd = [];
    
            while (fromDate <= toDate) {
                const formatted = fromDate.toISOString().split('T')[0];
                if (!selectedDates.includes(formatted)) {
                    selectedDates.push(formatted);
                    datesToAdd.push(new Date(fromDate)); // keep actual Date object
                }
                fromDate.setDate(fromDate.getDate() + 1);
            }
    
            calendar.setDate(selectedDates, false);
            $('#selected_dates').val(selectedDates.join(','));
        } else {
            alert("Invalid date range.");
        }
    });

    // Show/hide "Add More" button and control time slot fields
    $('#booking_option').on('change', function () {
      const val = $(this).val();
      const wrapper = $('#time-slots-wrapper');
      
      // Clear all validation errors
      wrapper.find('.is-invalid').removeClass('is-invalid');
      wrapper.find('.invalid-feedback').remove();
    
      if (val === 'daily') {
        $('#add-time-slot').addClass('d-none');
        // Reset to one slot
        wrapper.html(`
          <div class="time-slot-row mb-3">
            <div class="row">
              <div class="col-md-6">
                <label for="from" class="col-form-label">From Time:</label>
                <input type="time" name="from_times[]" class="form-control from-time" id="from" required>
              </div>
              <div class="col-md-6">
                <label for="to" class="col-form-label">To Time:</label>
                <input type="time" name="to_times[]" class="form-control to-time" id="to" required>
              </div>
            </div>
          </div>
        `);
      } else {
        $('#add-time-slot').removeClass('d-none');
      }
    });


    // Add more time slots
    $('#add-time-slot').on('click', function () {
      const wrapper = $('#time-slots-wrapper');
    
      // Check existing slots
      const newFrom = '';
      const newTo = '';
    
      // Append blank slot to let user fill
      const newRow = `
        <div class="time-slot-row mb-3">
          <div class="row">
            <div class="col-md-6">
              <label class="col-form-label">From Time:</label>
              <input type="time" name="from_times[]" class="form-control from-time" required>
            </div>
            <div class="col-md-6">
              <label class="col-form-label">To Time:</label>
              <input type="time" name="to_times[]" class="form-control to-time" required>
              <button type="button" class="btn btn-danger btn-sm mt-1 remove-slot">× Remove</button>
            </div>
          </div>
        </div>
      `;
      wrapper.append(newRow);
    });

    // Remove a time slot
    $(document).on('click', '.remove-slot', function () {
      $(this).closest('.time-slot-row').remove();
    });

    // Real-time validation for time inputs (only for slotwise/both options)
    $(document).on('change', '.from-time, .to-time', function () {
        const bookingOption = $('#booking_option').val();
        
        // Skip validation for daily booking option
        if (bookingOption === 'daily') {
            $(this).removeClass('is-invalid');
            $(this).closest('.time-slot-row').find('.invalid-feedback').remove();
            return;
        }
        
        const row = $(this).closest('.time-slot-row');
        const fromTime = row.find('.from-time').val();
        const toTime = row.find('.to-time').val();
        
        if (fromTime && toTime) {
            const fromMin = timeToMinutes(fromTime);
            const toMin = timeToMinutes(toTime);
            
            if (toMin <= fromMin) {
                row.find('.from-time, .to-time').addClass('is-invalid');
                if (!row.find('.invalid-feedback').length) {
                    row.append('<div class="invalid-feedback">Error: To Time must be after From Time</div>');
                }
            } else {
                row.find('.from-time, .to-time').removeClass('is-invalid');
                row.find('.invalid-feedback').remove();
            }
        }
    });

    $('.add-form').on('submit', function (e) {
        const bookingType = $('#booking_type').val();
        const bookingOption = $('#booking_option').val();
        
        // Validate cancellation value if booking type is Paid
        if (bookingType === 'Paid') {
            const cancellationType = $('#cancellation_type').val();
            const cancellationValue = parseFloat($('#cancellation_value').val());
            
            if (cancellationType === 'Percentage') {
                if (cancellationValue > 100) {
                    alert('Error: Cancellation percentage cannot exceed 100%. Please enter a value between 0-100.');
                    $('#cancellation_value').addClass('is-invalid').focus();
                    e.preventDefault();
                    return false;
                } else if (cancellationValue < 0) {
                    alert('Error: Cancellation percentage cannot be negative. Please enter a value between 0-100.');
                    $('#cancellation_value').addClass('is-invalid').focus();
                    e.preventDefault();
                    return false;
                }
            } else if (cancellationType === 'Fixed' && cancellationValue < 0) {
                alert('Error: Fixed cancellation amount cannot be negative. Please enter a positive value.');
                $('#cancellation_value').addClass('is-invalid').focus();
                e.preventDefault();
                return false;
            }
        }

        // Skip time slot validation for daily booking option
        if (bookingOption === 'daily') {
            // Enable disabled fields before submission
            $('#cancellation_type').prop('disabled', false);
            return true;
        }

        // For 'Both' booking option, proceed without validation
        if (bookingOption === 'both') {
            // Enable disabled fields before submission
            $('#cancellation_type').prop('disabled', false);
            return true;
        }

        // Time slot validation only for 'slotwise' option
        let slots = [];
        let isValid = true;
        let slotNumber = 0;

        $('#time-slots-wrapper .time-slot-row').each(function () {
            slotNumber++;
            const from = $(this).find('.from-time').val();
            const to = $(this).find('.to-time').val();

            if (!from || !to) return;

            let fromMin = timeToMinutes(from);
            let toMin = timeToMinutes(to);

            // Validate that "To Time" must be after "From Time"
            if (toMin <= fromMin) {
                alert(`Error in Slot ${slotNumber}: "To Time" (${to}) must be after "From Time" (${from}). Please correct the time range.`);
                $(this).find('.from-time, .to-time').addClass('is-invalid');
                if (!$(this).find('.invalid-feedback').length) {
                    $(this).append('<div class="invalid-feedback">Error: To Time must be after From Time</div>');
                }
                isValid = false;
                return false;
            }

            // Overlap check
            for (let i = 0; i < slots.length; i++) {
                const slot = slots[i];
                if (isOverlapping(fromMin, toMin, slot.from, slot.to)) {
                    alert(`Error: Slot ${slotNumber} (${from} to ${to}) overlaps with Slot ${i + 1}. Please adjust the time ranges.`);
                    $(this).find('.from-time, .to-time').addClass('is-invalid');
                    if (!$(this).find('.invalid-feedback').length) {
                        $(this).append('<div class="invalid-feedback">Error: This slot overlaps with another slot</div>');
                    }
                    isValid = false;
                    return false;
                }
            }

            slots.push({ from: fromMin, to: toMin });
        });

        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Enable disabled fields before submission
        $('#cancellation_type').prop('disabled', false);
    });

    // Booking action handlers
    $(document).on('click', '.pause-booking', function() {
        const bookingId = $(this).data('id');
        if (confirm('Are you sure you want to pause this booking?')) {
            updateBookingStatus(bookingId, 'Paused');
        }
    });

    $(document).on('click', '.resume-booking', function() {
        const bookingId = $(this).data('id');
        if (confirm('Are you sure you want to resume this booking?')) {
            updateBookingStatus(bookingId, 'Active');
        }
    });

    $(document).on('click', '.cancel-booking', function() {
        const bookingId = $(this).data('id');
        if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            updateBookingStatus(bookingId, 'Cancelled');
        }
    });

    // BA Approval/Denial functionality
    $(document).on('click', '.approve-booking', function() {
        const bookingId = $(this).data('id');
        if (confirm('Are you sure you want to approve this booking?')) {
            updateBookingStatus(bookingId, 'Active');
        }
    });

    $(document).on('click', '.deny-booking', function() {
        const bookingId = $(this).data('id');
        if (confirm('Are you sure you want to deny this booking? This will cancel the booking.')) {
            updateBookingStatus(bookingId, 'Cancelled');
        }
    });

    // View and Edit booking functionality
    $(document).on('click', '.view-booking', function() {
        const bookingId = $(this).data('id');
        // Add view booking functionality here
        alert('View booking details for ID: ' + bookingId);
    });

    $(document).on('click', '.edit-booking', function() {
        const bookingId = $(this).data('id');
        // Add edit booking functionality here
        alert('Edit booking for ID: ' + bookingId);
    });

    function updateBookingStatus(bookingId, status) {
        $.ajax({
            url: "<?php echo e(url('booking/update-status')); ?>",
            type: "POST",
            data: {
                '_token': token,
                'booking_id': bookingId,
                'status': status
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Failed to update booking status: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Booking status update failed:', xhr.responseText);
                alert('Failed to update booking status: ' + (xhr.responseJSON?.message || error));
            }
        });
    }

    // Initialize tooltips for action buttons
    $('[data-toggle="tooltip"]').tooltip({
        placement: 'top',
        trigger: 'hover'
    });

    // Ensure action buttons are visible and functional
    function ensureActionButtonsVisible() {
        // Force visibility of all action buttons
        $('.action-buttons-container .btn').each(function() {
            var $btn = $(this);
            var $icon = $btn.find('i');
            
            // Ensure button is visible
            $btn.css({
                'display': 'inline-flex',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            // Ensure icon is visible if present
            if ($icon.length > 0) {
                $icon.css({
                    'display': 'inline-block',
                    'font-family': 'FontAwesome, "Font Awesome 5 Free"',
                    'font-weight': '900',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        });
        
        // Add debug info to console
        console.log('Action buttons initialized:', $('.action-buttons-container .btn').length);
    }

    // Run button check after page load
    setTimeout(ensureActionButtonsVisible, 500);
    
    // Also run when DataTable is initialized
    $(document).on('draw.dt', '#example1', function() {
        setTimeout(ensureActionButtonsVisible, 100);
    });

    // Initialize DataTable for bookings table with delay to ensure global initialization is complete
    setTimeout(function() {
        // Clean up any existing DataTable and button containers
        if ($.fn.DataTable.isDataTable('#example1')) {
            $('#example1').DataTable().destroy();
        }
        
        // Remove any existing button containers
        $('.dt-buttons').remove();
        $('#example1_wrapper').remove();
        
        // Wait a moment for cleanup
        setTimeout(function() {
            var table = $('#example1').DataTable({
                "responsive": true,
                "scrollX": true,
                "ordering": true,
                "lengthChange": false,
                "autoWidth": false,
                "bPaginate": true,
                "bInfo": false,
                "searching": true,
                "pageLength": 10,
                "columnDefs": [
                    { "orderable": false, "targets": [-1, -2] }, // Disable sorting for Action and Cancellation columns
                    { "width": "120px", "targets": -2 }, // Set width for Cancellation column
                    { "width": "220px", "targets": -1 }, // Set width for Action column (increased for better button display)
                    { "width": "100px", "targets": 6 }, // Set width for Booking Amount column
                    { "width": "110px", "targets": 7 }, // Set width for Refunded Amount column
                    { "width": "100px", "targets": 9 }, // Set width for Cancelled Time column
                    { "className": "text-center", "targets": [-1, -2, 6, 7, 9, 10] }, // Center align Action, Cancellation, Booking Amount, Refunded Amount, Cancelled Time, and Status columns
                    { "className": "text-center", "targets": [2, 3, 4, 5] }, // Center align Date, From, To, Members columns
                    { "responsivePriority": 1, "targets": 0 }, // Booked By column priority
                    { "responsivePriority": 2, "targets": 1 }, // Flat column priority
                    { "responsivePriority": 4, "targets": 6 }, // Booking Amount column priority
                    { "responsivePriority": 5, "targets": 7 }, // Refunded Amount column priority
                    { "responsivePriority": 6, "targets": 9 }, // Cancelled Time column priority
                    { "responsivePriority": 7, "targets": 10 }, // Status column priority
                    { "responsivePriority": 3, "targets": -1 } // Action column priority
                ],
                order: [[0, 'asc']],
                buttons: [
                    {
                        extend: 'csvHtml5',
                        exportOptions: {
                            columns: ':visible:not(:last-child)' // Exclude Action column from export
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    'colvis'
                ]
            });
            
            // Append buttons to the wrapper
            table.buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
            
            // Show the table after initialization is complete
            $('#example1').css('visibility', 'visible');
            
            // Final verification of action buttons
            setTimeout(function() {
                ensureActionButtonsVisible();
                
                // Verify functionality
                console.log('=== ACTION BUTTON VERIFICATION ===');
                console.log('Total buttons found:', $('.action-buttons-container .btn').length);
                console.log('Approve buttons:', $('.approve-booking').length);
                console.log('Deny buttons:', $('.deny-booking').length);
                console.log('View buttons:', $('.view-booking').length);
                console.log('Edit buttons:', $('.edit-booking').length);
                console.log('Cancel buttons:', $('.cancel-booking').length);
                console.log('Pause buttons:', $('.pause-booking').length);
                console.log('Resume buttons:', $('.resume-booking').length);
                console.log('=== END VERIFICATION ===');
            }, 200);
        }, 100);
    }, 1000); // Wait 1 second for global initialization to complete
    
});

    // Convert HH:mm to minutes since 00:00
    function timeToMinutes(t) {
        const [hours, minutes] = t.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    function isOverlapping(from1, to1, from2, to2) {
        return from1 < to2 && to1 > from2;
    }

    // Interactive dates functionality
    $(document).ready(function() {
        // Handle "more" button click
        $(document).on('click', '.more-dates-btn', function(e) {
            e.preventDefault();
            var timingId = $(this).data('timing-id');
            var $container = $(this).closest('.dates-display');
            
            // Hide short version and show full version
            $container.find('.dates-short').hide();
            $container.find('.dates-full').show();
            
            // Add smooth transition effect
            $container.find('.dates-full').hide().fadeIn(300);
        });
        
        // Handle "show less" button click
        $(document).on('click', '.less-dates-btn', function(e) {
            e.preventDefault();
            var timingId = $(this).data('timing-id');
            var $container = $(this).closest('.dates-display');
            
            // Hide full version and show short version
            $container.find('.dates-full').hide();
            $container.find('.dates-short').show();
            
            // Add smooth transition effect
            $container.find('.dates-short').hide().fadeIn(300);
        });
    });

</script>

<script>
    document.querySelectorAll(".calendar-input").forEach(function(input) {
        // If already initialized, destroy it first
        if (input._flatpickr) {
            input._flatpickr.destroy();
        }

        const dates = input.dataset.dates?.split(',') || [];
        flatpickr(input, {
            mode: "multiple",
            dateFormat: "Y-m-d",
            defaultDate: dates
        });
    });
</script>


<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/facility/show.blade.php ENDPATH**/ ?>