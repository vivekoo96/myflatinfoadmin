<?php $__env->startSection('title'); ?>
    Issue Details
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- ===================== Content Header (Page header) ===================== -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Issue Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Issue Details</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== Main content ===================== -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- ===================== Left Sidebar: Issue Profile ===================== -->
          <div class="col-md-3">
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center my-3">
                  <?php if(!empty($issue->photos) && isset($issue->photos[0])): ?>
                  <img class="profile-user-img img-fluid img-circle"
                       src="<?php echo e($issue->photos[0]->photo); ?>"
                       alt="Issue picture" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; display: inline-block;">
                  <?php endif; ?>
                </div>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Building</b> <a class="float-right"><?php echo e($issue->building->name); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Block</b> <a class="float-right"><?php echo e($issue->block ? $issue->block->name : 'All'); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Flat</b> <a class="float-right"><?php echo e($issue->flat ? $issue->flat->name : 'All'); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Department</b> <a class="float-right"><?php echo e($issue->department->name); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Priority</b> <a class="float-right"><?php echo e($issue->periority); ?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b><a class="float-right"><?php echo e(($issue->status === 'Solved' || $issue->status === 'Completed') ? 'Completed' : $issue->status); ?></a>
                  </li>
                  <?php
                    $raisedByUser = $issue->user_id ? \App\Models\User::find($issue->user_id) : null;
                    $assignedToUser = $issue->role_user_id ? \App\Models\User::find($issue->role_user_id) : null;
                    
                    $raisedByRole = '';
                    if ($raisedByUser) {
                      $roleInfo = \App\Models\BuildingUser::where('building_id', $issue->building_id)
                        ->where('user_id', $raisedByUser->id)
                        ->with('role')
                        ->first();
                      $raisedByRole = $roleInfo && $roleInfo->role ? $roleInfo->role->name : 'BA';
                    }
                    
                    $assignedToRole = '';
                    if ($assignedToUser) {
                      $roleInfo = \App\Models\BuildingUser::where('building_id', $issue->building_id)
                        ->where('user_id', $assignedToUser->id)
                        ->with('role')
                        ->first();
                      $assignedToRole = $roleInfo && $roleInfo->role ? $roleInfo->role->name : 'User';
                    }
                  ?>
                  <li class="list-group-item">
                    <b>Raised By</b> 
                    <?php if($raisedByUser): ?>
                    <a class="float-right">
                      <small><?php echo e($raisedByUser->first_name); ?> <?php echo e($raisedByUser->last_name); ?></small>
                      <br>
                      <small class="text-muted">(<?php echo e($raisedByRole); ?>)</small>
                    </a>
                    <?php else: ?>
                    <a class="float-right"><small class="text-muted">Unknown</small></a>
                    <?php endif; ?>
                  </li>
                  <li class="list-group-item">
                    <b>Assigned To</b> 
                    <?php if($assignedToUser): ?>
                    <a class="float-right">
                      <small><?php echo e($assignedToUser->first_name); ?> <?php echo e($assignedToUser->last_name); ?></small>
                      <br>
                    
                    </a>
                    <?php else: ?>
                    <a class="float-right"><small class="text-muted">Not Assigned</small></a>
                    <?php endif; ?>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- ===================== Main Panel: Issue Details & Comments ===================== -->
          <div class="col-md-9">
              <div class="card">
                  <div class="card-header">
                      <span class="badge badge-default"><?php echo e($issue->department->name); ?></span>
                    <span class="badge badge-primary">
    <?php echo e(($issue->status === 'Solved' || $issue->status === 'Completed') ? 'Completed' : $issue->status); ?>

</span>

                  </div>
                  <div class="card-body">
                    <!-- ===================== Issue Description & Photos ===================== -->
                      <p><?php echo e($issue->desc); ?></p>
                      <div class="row mt-3">
                          <?php $__empty_1 = true; $__currentLoopData = $issue->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        
                          <div class="col-md-4 mb-3">
                            <div class="card shadow-sm h-100" style="cursor: pointer; overflow: hidden; border-radius: 8px; transition: transform 0.3s, box-shadow 0.3s;">
                              <div class="image-container" style="height: 200px; overflow: hidden; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                                <img src="<?php echo e($photo->photo); ?>" class="img-fluid" style="height: 100%; width: 100%; object-fit: cover;" data-toggle="modal" data-target="#imageModal" data-image="<?php echo e($photo->photo); ?>" alt="Issue photo">
                              </div>
                              <div class="card-body p-2 text-center">
                                <small class="text-muted">Click to view</small>
                              </div>
                            </div>
                          </div>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <div class="col-12">
                            <p class="text-muted text-center">No images available</p>
                          </div>
                          <?php endif; ?>
                      </div>

                      <!-- ===================== Comment Section ===================== -->
                         <?php if(Auth::User()->role == 'BA' || Auth::User()->hasRole('issue')): ?>
                      <div class="mt-4">
                          <h4>Comments</h4>
                          
                          <!-- ===================== Comment List Scrollable ===================== -->
                          <div style="max-height: 400px; min-height: 300px; overflow-y: scroll; border: 2px solid #007bff; border-radius: 8px; background: #f4f8ff; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: center;">
                            <ul id="commentList" class="list-unstyled mt-3 w-100">
                             <?php $__empty_1 = true; $__currentLoopData = $issue->comments->sortBy('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                
                                  <li class="media mb-3" data-id="<?php echo e($comment->id); ?>">
                                      <?php
                                        $commentPhoto = $comment->user->photo ?? '';
                                        if (empty($commentPhoto) && !empty($comment->role_user_id)) {
                                            $roleUser = \App\Models\User::find($comment->role_user_id);
                                            if ($roleUser && !empty($roleUser->photo)) {
                                                $commentPhoto = $roleUser->photo;
                                            }
                                        }
                                       
                                      ?>
                                      <img class="mr-3 rounded-circle" src="<?php echo e($commentPhoto); ?>" width="50" height="50">
                                      <div class="media-body">
                                         <?php
                                                      // Find role from BuildingUser
                                                      $buildingUserRole = \App\Models\BuildingUser::where('building_id', $issue->building_id)
                                                          ->where('user_id', $comment->user_id)
                                                          ->with('role')
                                                          ->first();

                                                      if ($buildingUserRole && $buildingUserRole->role) {
                                                          // Role found in BuildingUser relationship
                                                          $roleName = $buildingUserRole->role->name;
                                                      } else {
                                                          // Fallback to user's own role (works for string or relation)
                                                          $userRole = $comment->user->role ?? null;

                                                          if (is_object($userRole) && isset($userRole->name)) {
                                                              // If role is a relationship object
                                                              $roleName = $userRole->name;
                                                          } elseif (is_string($userRole)) {
                                                              // If role is stored as plain text
                                                              $roleName = $userRole;
                                                          } else {
                                                              // Final fallback
                                                              $roleName = 'BA';
                                                          }
                                                      }
                                                  ?>

                                            <?php
                                              $displayUser = $comment->user;
                                              if (!empty($comment->role_user_id)) {
                                                  $roleUser = \App\Models\User::find($comment->role_user_id);
                                                  if ($roleUser) {
                                                      $displayUser = $roleUser;
                                                  }
                                              }
                                            ?>
                                            <small class="mt-0 mb-1"><strong><?php echo e($displayUser->first_name ?? ''); ?> <?php echo e($displayUser->last_name ?? ''); ?> - <?php echo e($comment->commentedbyRole); ?></strong></small>
                                                          <div class="p-2 mt-1 mb-1 d-block" 
                                                    style="background:#f8f9fa; border:1px solid #ddd; border-radius:8px; max-width:75%; word-wrap:break-word;">
                                                    <p class="mb-1"><?php echo e($comment->text); ?></p>
                                                </div>

                                          <small class="text-muted"><?php echo e($comment->created_at->diffForHumans()); ?></small>
                                          
                                          <ul class="list-unstyled ml-4 mt-2">
                                              <?php $__empty_2 = true; $__currentLoopData = $comment->replies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reply): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                                  <li class="media mb-2">
                                                      <?php
                                                        $replyPhoto = $reply->user->photo ?? '';
                                                        if (empty($replyPhoto) && !empty($reply->role_user_id)) {
                                                            $roleUser = \App\Models\User::find($reply->role_user_id);
                                                            if ($roleUser && !empty($roleUser->photo)) {
                                                                $replyPhoto = $roleUser->photo;
                                                            }
                                                        }
                                                      ?>
                                                      <img class="mr-3 rounded-circle" src="<?php echo e($replyPhoto); ?>" width="30">
                                                      <div class="media-body">
                                                            <?php
                                                              $replyBuildingUserRole = \App\Models\BuildingUser::where('building_id', $issue->building_id)
                                                                ->where('user_id', $reply->user_id)
                                                                ->with('role')
                                                                ->first();
                                                              if ($replyBuildingUserRole && $replyBuildingUserRole->role) {
                                                                $replyRoleName = $replyBuildingUserRole->role->name;
                                                              } else {
                                                                $replyRoleName = $reply->user->role ?? 'N/A';
                                                              }
                                                            ?>
                                                            <h6 class="mt-0 mb-1"><?php echo e($reply->user->first_name ?? ''); ?> <?php echo e($reply->user->last_name ?? ''); ?> (<?php echo e($reply->user->email ?? ''); ?>) - <?php echo e($replyRoleName); ?></h6>
                                                          <p><?php echo e($reply->text); ?></p>
                                                          <small class="text-muted"><?php echo e($reply->created_at->diffForHumans()); ?></small>
                                                      </div>
                                                  </li>
                                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                              <?php endif; ?>
                                          </ul>
                                          <div class="reply-box mt-2" style="display: none;">
                                              <textarea class="form-control mb-2 replyText"></textarea>
                                              <button class="btn btn-sm btn-primary replyBtn" data-id="<?php echo e($comment->id); ?>">Reply</button>
                                          </div>
                                      </div>
                                  </li>
                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <li class="text-center text-muted w-100">
                                <p style="margin: 0; padding: 20px;">No comments yet</p>
                              </li>
                              <?php endif; ?>
                            </ul>
                          </div>
                           
                            <!-- ===================== Add Comment Form ===================== -->
                             <?php if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Issue Tracker"): ?>
                            <form method="POST" action="<?php echo e(url('add-comment')); ?>">
                              <?php echo csrf_field(); ?>
                              <input type="hidden" name="issue_id" value="<?php echo e($issue->id); ?>">
                              <textarea name="text" class="form-control mb-2" placeholder="Write a comment..." required></textarea>
                              <button type="submit" class="btn btn-primary">Post</button>
                            </form>
                            <?php endif; ?>
                          <?php endif; ?>
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
    </section>

<!-- ===================== Image Modal ===================== -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Issue Photo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="Issue Photo" class="img-fluid" style="max-height: 600px;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== Scripts ===================== -->
<?php $__env->startSection('script'); ?>

<script>
$(document).ready(function() {
  // Handle image modal click
  $(document).on('click', '[data-toggle="modal"][data-target="#imageModal"]', function(e) {
    e.preventDefault();
    var imageSrc = $(this).data('image');
    $('#modalImage').attr('src', imageSrc);
  });

  // Scroll comment list to bottom by default
  var commentListContainer = $("#commentList").parent();
  commentListContainer.scrollTop(commentListContainer[0].scrollHeight);

  // Prevent double submit on comment form
  $("form[action='<?php echo e(url('add-comment')); ?>']").on("submit", function(e) {
    let btn = $(this).find("button[type='submit']");
    // Disable button to prevent multiple clicks
    btn.prop("disabled", true);
    btn.text("Posting...");
    // Allow form submit
    return true;
  });
});
</script>
<script>
  $(document).ready(function(){
      var token = "<?php echo e(csrf_token()); ?>";

        // AJAX for posting new comment removed; now uses standard form submit with refresh.

      // Show reply box
      $(document).on('click', '.reply-link', function(){
          $(this).siblings('.reply-box').toggle();
      });

      // Post a reply
      $(document).on('click', '.replyBtn', function(){
          var commentId = $(this).data('id');
          var text = $(this).siblings('.replyText').val();
          if(text.trim() !== ''){
              $.post('<?php echo e(url("add-reply")); ?>', {_token: token, comment_id: commentId, text: text}, function(response){
                  if(response.status == 'success'){
                      $('li[data-id="'+commentId+'"] ul').append(response.html);
                      $('.replyText').val('');
                  }
              }, 'json');
          }
      });

  });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/issue/show.blade.php ENDPATH**/ ?>