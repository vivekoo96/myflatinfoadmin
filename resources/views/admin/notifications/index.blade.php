@extends('layouts.admin')

@section('title')
    Send Notification
@endsection

@section('content')

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Send Notification</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Send Notification</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <div class="card" style="border-top: 3px solid #3C5795;">
      <div class="card-header" style="background-color: #3C5795; color: #fff;">
        <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Create Notification</h3>
        <div class="card-tools">
          <a href="{{ route('notification.history') }}" class="btn btn-sm" style="background-color:#fff; color:#3C5795;">
            <i class="fas fa-history mr-1"></i> Previous Notifications
          </a>
        </div>
      </div>

      <form action="{{ route('notification.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card-body">

          @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          {{-- Title --}}
          <div class="form-group">
            <label>Notification Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Enter notification title" value="{{ old('title') }}" required>
          </div>

          {{-- Description --}}
          <div class="form-group">
            <label>Description <span class="text-danger">*</span></label>
            <textarea name="body" class="form-control" rows="4" placeholder="Enter notification description" required>{{ old('body') }}</textarea>
          </div>

          {{-- Image (optional) --}}
          <div class="form-group">
            <label>Image <span class="text-muted">(Optional)</span></label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="notifImage" name="image" accept="image/*">
              <label class="custom-file-label" for="notifImage">Choose image...</label>
            </div>
            <div id="imagePreview" class="mt-2" style="display:none;">
              <img id="previewImg" src="" class="img-thumbnail" style="max-height:120px;">
            </div>
          </div>

          {{-- Role Selection --}}
          <div class="form-group">
            <label><strong>Send To <span class="text-danger">*</span></strong></label>

            <div class="card border mt-1">
              <div class="card-body pb-2">

                {{-- Flat Users --}}
                <p class="font-weight-bold mb-1">
                  <i class="fas fa-home mr-1 text-primary"></i> Flat Users
                </p>
                <div class="ml-3 mb-3">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="all_flat_users" id="role_all_flat" {{ in_array('all_flat_users', old('target_roles', [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="role_all_flat">All Flat Users</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="owners" id="role_owners" {{ in_array('owners', old('target_roles', [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="role_owners">Owners</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="tenants" id="role_tenants" {{ in_array('tenants', old('target_roles', [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="role_tenants">Tenants</label>
                  </div>
                </div>

                {{-- Security Users --}}
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="target_roles[]" value="security" id="role_security" {{ in_array('security', old('target_roles', [])) ? 'checked' : '' }}>
                  <label class="form-check-label font-weight-bold" for="role_security">
                    <i class="fas fa-shield-alt mr-1 text-warning"></i> Security Users
                  </label>
                </div>

                {{-- Issue Management --}}
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="target_roles[]" value="issue_management" id="role_issue" {{ in_array('issue_management', old('target_roles', [])) ? 'checked' : '' }}>
                  <label class="form-check-label font-weight-bold" for="role_issue">
                    <i class="fas fa-tools mr-1 text-info"></i> Issue Management Users
                  </label>
                </div>

                {{-- Accounts --}}
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="target_roles[]" value="accounts" id="role_accounts" {{ in_array('accounts', old('target_roles', [])) ? 'checked' : '' }}>
                  <label class="form-check-label font-weight-bold" for="role_accounts">
                    <i class="fas fa-calculator mr-1 text-success"></i> Accounts App Users
                  </label>
                </div>

              </div>
            </div>
            @error('target_roles')
              <span class="text-danger small">{{ $message }}</span>
            @enderror
          </div>

        </div><!-- /.card-body -->

        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane mr-1"></i> Send Notification
          </button>
        </div>
      </form>
    </div>

  </div>
</section>

@endsection

@section('script')
<script>
$(document).ready(function () {

    // Image preview
    $('#notifImage').on('change', function () {
        var file = this.files[0];
        if (file) {
            $('#imagePreview').show();
            $('#previewImg').attr('src', URL.createObjectURL(file));
            $(this).next('.custom-file-label').text(file.name);
        }
    });

    // "All Flat Users" disables Owners / Tenants
    $('#role_all_flat').on('change', function () {
        if ($(this).is(':checked')) {
            $('#role_owners, #role_tenants').prop('checked', false).prop('disabled', true);
        } else {
            $('#role_owners, #role_tenants').prop('disabled', false);
        }
    });

    // Owners / Tenants unchecks All Flat Users
    $('#role_owners, #role_tenants').on('change', function () {
        if ($(this).is(':checked')) {
            $('#role_all_flat').prop('checked', false);
        }
    });

});
</script>
@endsection
