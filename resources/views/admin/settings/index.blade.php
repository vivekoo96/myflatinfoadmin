    @extends('layouts.admin')

    @section('title')
        Setting
    @endsection

    @section('content')
@php
    $isBA = Auth::user()->role === 'BA';
@endphp
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
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{ session()->get('success') }}
                        </div>
                    @endif
                    @if(session()->has('error'))
                        <div class="alert alert-danger">
                            {{ session()->get('error') }}
                        </div>
                    @endif
                    <form action="{{route('setting.store')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                            @if($building->payment_is_active == 'Yes')
                                <div class="form-group">
                                        <label>Razorpay Key</label>
                                        <input type="text" class="form-control" name="razorpay_key" value="{{$building->razorpay_key}}" required {{ $isBA ? '' : 'disabled' }}>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Razorpay Secret</label>
                                        <input type="text" class="form-control" name="razorpay_secret" value="{{$building->razorpay_secret}}" required {{ $isBA ? '' : 'disabled' }}>
                                    </div>
                                </div>
                            @else
                                <div class="form-group">
                                        <label>Razorpay Key</label>
                                        <input type="text" class="form-control" name="razorpay_key" value="{{$setting->razorpay_key}}" disabled required {{ $isBA ? '' : 'disabled' }}>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Razorpay Secret</label>
                                        <input type="text" class="form-control" name="razorpay_secret" value="{{$setting->razorpay_secret}}" disabled required {{ $isBA ? '' : 'disabled' }}>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>GST No</label>
                                    <input type="text" class="form-control" name="gst_no" value="{{$building->gst_no}}" {{ $isBA ? '' : 'disabled' }}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Classified Limit(Within Building)</label>
                                    <input type="number" class="form-control" name="classified_limit_within_building" value="{{$building->classified_limit_within_building}}" disabled required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Classified Limit(All Building)</label>
                                    <input type="number" class="form-control" name="classified_limit_all_building" value="{{$building->classified_limit_all_building}}" disabled required>
                                </div>
                            </div>
                            
                           <div class="col-md-4">
    <div class="form-group">
        <label>Call Support Number</label>
        <input type="tel"
            class="form-control"
            name="call_support_number"
            value="{{ $building->call_support_number }}"
            pattern="[6-9]{1}[0-9]{9}"
            minlength="10"
            maxlength="10"
            inputmode="numeric"
            title="Enter a valid 10-digit mobile number starting with 6-9"
            required {{ $isBA ? '' : 'disabled' }}>
    </div>
</div>

<div class="col-md-4">
    <div class="form-group">
        <label>Whatsapp Support Number</label>
        <input type="tel"
            class="form-control"
            name="whatsapp_support_number"
            value="{{ $building->whatsapp_support_number }}"
            pattern="[6-9]{1}[0-9]{9}"
            minlength="10"
            maxlength="10"
            inputmode="numeric"
            title="Enter a valid 10-digit mobile number starting with 6-9"
            required {{ $isBA ? '' : 'disabled' }}>
    </div>
</div>

                           <div class="col-md-4">
                                <div class="form-group">
                                    <label>Treasurer Type</label>
                            
                                    <select name="treasurer_type"
                                            class="form-control"
                                            id="treasurer_type"
                                            {{ Auth::user()->role !== 'BA' ? 'disabled' : '' }}
                                            required>
                                        <option value="BA" {{ strtolower($building->treasurer_type) == 'ba' ? 'selected' : '' }}>BA</option>
                                        <option value="President" {{ in_array(strtolower($building->treasurer_type), ['president','presedent']) ? 'selected' : '' }}>President</option>
                                        <option value="Accounts" {{ strtolower($building->treasurer_type) == 'accounts' ? 'selected' : '' }}>Accounts</option>
                                    </select>
                            
                                    {{-- IMPORTANT: send value even if disabled --}}
                                    @if( Auth::user()->role !== 'BA')
                                        <input type="hidden" name="treasurer_type" value="{{ $building->treasurer_type }}">
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Treasurer Person</label>
                                    <div class="treasurer_users">
                                        @include('partials.department_users', ['role' => $role ?? null, 'building' => $building ?? null, 'building_users' => $building_users ?? collect()])
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                @if(Auth::User()->role == 'BA')
                                <input type="submit" class="btn btn-block bg-gradient-primary btn-flat" value="Save">
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>
            </div>

            {{-- ===================== UPI PAYMENT SETTINGS ===================== --}}
            <div class="row mt-4">
            <div class="col-12">
                <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-qrcode mr-2"></i> UPI Payment Settings
                    </h3>
                    <small class="text-muted ml-2">Allow residents to pay maintenance via UPI without a payment gateway</small>
                </div>
                <div class="card-body">
                    @if($isBA)
                    <form action="{{ route('setting.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        {{-- Hidden fields to satisfy form validation for unchanged fields --}}
                        <input type="hidden" name="razorpay_key" value="{{ $building->razorpay_key }}">
                        <input type="hidden" name="razorpay_secret" value="{{ $building->razorpay_secret }}">
                        <input type="hidden" name="gst_no" value="{{ $building->gst_no }}">
                        <input type="hidden" name="call_support_number" value="{{ $building->call_support_number }}">
                        <input type="hidden" name="whatsapp_support_number" value="{{ $building->whatsapp_support_number }}">
                        <input type="hidden" name="treasurer_type" value="{{ $building->treasurer_type }}">
                        <input type="hidden" name="treasurer_id" value="{{ $building->treasurer_id }}">
                        <input type="hidden" name="is_bank_enabled" value="{{ $building->is_bank_enabled }}">
                        
                        <div class="row mb-4 pl-2">
                            <div class="col-md-12">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="hidden" name="is_upi_enabled" value="No">
                                    <input type="checkbox" class="custom-control-input" id="is_upi_enabled" name="is_upi_enabled" value="Yes" {{ ($building->is_upi_enabled ?? 'Yes') == 'Yes' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="is_upi_enabled" style="font-size: 1.1rem; cursor: pointer;">Enable UPI Payments</label>
                                </div>
                                <small class="text-muted d-block mt-1">If disabled, residents will not see the UPI payment option.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label><i class="fas fa-university mr-1"></i> UPI ID</label>
                                    <input type="text"
                                           class="form-control"
                                           name="upi_id"
                                           id="upi_id_input"
                                           value="{{ $building->upi_id }}"
                                           placeholder="e.g. societyname@upi"
                                           autocomplete="off">
                                    <small class="form-text text-muted">This will be displayed to residents in the app for payment.</small>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label><i class="fas fa-qrcode mr-1"></i> UPI QR Code Image</label>
                                    <div class="custom-file">
                                        <input type="file"
                                               class="custom-file-input"
                                               id="upi_qr_code_input"
                                               name="upi_qr_code"
                                               accept="image/*"
                                               onchange="previewQrCode(this)">
                                        <label class="custom-file-label" for="upi_qr_code_input">Choose QR code image...</label>
                                    </div>
                                    <small class="form-text text-muted">Upload a QR code image for UPI payment (PNG/JPG).</small>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                @if($building->upi_qr_code)
                                <div class="text-center" id="current-qr-wrapper">
                                    <p class="text-muted mb-1" style="font-size:11px;">Current QR</p>
                                    <a href="{{ asset('public/upi_qr_codes/' . $building->upi_qr_code) }}" target="_blank">
                                        <img class="mt-2 img-thumbnail shadow-sm"
                                             src="{{ asset('public/upi_qr_codes/' . $building->upi_qr_code) }}"
                                             alt="UPI QR Code"
                                             class="img-thumbnail"
                                             style="max-width:100px; cursor:pointer; border:2px solid #007bff;">
                                    </a>
                                </div>
                                @else
                                <div class="text-center" id="current-qr-wrapper" style="display:none!important;">
                                    <img id="qr-preview" src="#" alt="QR Preview" class="img-thumbnail" style="max-width:100px; display:none;">
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- QR live preview (shows after file selected) --}}
                        <div class="row" id="new-qr-preview-row" style="display:none;">
                            <div class="col-md-12">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-eye mr-2"></i>
                                    <strong>New QR Preview:&nbsp;</strong>
                                    <img id="new-qr-img" src="#" alt="New QR Preview"
                                         style="max-height:120px; max-width:120px; margin-left:10px; border:2px dashed #17a2b8; border-radius:4px;">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Save UPI Settings
                                </button>
                                @if($building->upi_id || $building->upi_qr_code)
                                <span class="badge badge-success ml-3" style="font-size:13px; padding:6px 12px;">
                                    <i class="fas fa-check-circle mr-1"></i> UPI Payment Mode Active
                                </span>
                                @endif
                            </div>
                        </div>
                    </form>
                    @else
                    <div class="row mb-4 pl-2">
                        <div class="col-md-12">
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input type="checkbox" class="custom-control-input" id="is_upi_enabled_disabled" disabled {{ ($building->is_upi_enabled ?? 'Yes') == 'Yes' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="is_upi_enabled_disabled" style="font-size: 1.1rem;">Enable UPI Payments</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label><i class="fas fa-university mr-1"></i> UPI ID</label>
                                <input type="text" class="form-control" value="{{ $building->upi_id ?? 'Not set' }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @if($building->upi_qr_code)
                            <label><i class="fas fa-qrcode mr-1"></i> UPI QR Code</label><br>
                            <a href="{{ asset('public/upi_qr_codes/' . $building->upi_qr_code) }}" target="_blank">
                                <img src="{{ asset('public/upi_qr_codes/' . $building->upi_qr_code) }}"
                                     alt="UPI QR Code"
                                     class="img-thumbnail"
                                     style="max-width:120px;">
                            </a>
                            @else
                            <p class="text-muted mt-4"><i class="fas fa-info-circle"></i> No QR code uploaded yet.</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                </div>
            </div>
            </div>

            {{-- ===================== BANK ACCOUNT SETTINGS ===================== --}}
            <div class="row mt-4">
            <div class="col-12">
                <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-university mr-2"></i> Bank Account Settings
                    </h3>
                    <small class="text-muted ml-2">Display bank account details to residents for offline/bank transfers</small>
                </div>
                <div class="card-body">
                    @if($isBA)
                    <form action="{{ route('setting.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="razorpay_key" value="{{ $building->razorpay_key }}">
                        <input type="hidden" name="razorpay_secret" value="{{ $building->razorpay_secret }}">
                        <input type="hidden" name="gst_no" value="{{ $building->gst_no }}">
                        <input type="hidden" name="call_support_number" value="{{ $building->call_support_number }}">
                        <input type="hidden" name="whatsapp_support_number" value="{{ $building->whatsapp_support_number }}">
                        <input type="hidden" name="treasurer_type" value="{{ $building->treasurer_type }}">
                        <input type="hidden" name="treasurer_id" value="{{ $building->treasurer_id }}">
                        <input type="hidden" name="is_upi_enabled" value="{{ $building->is_upi_enabled }}">
                        
                        <div class="row mb-4 pl-2">
                            <div class="col-md-12">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="hidden" name="is_bank_enabled" value="No">
                                    <input type="checkbox" class="custom-control-input" id="is_bank_enabled" name="is_bank_enabled" value="Yes" {{ ($building->is_bank_enabled ?? 'Yes') == 'Yes' ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="is_bank_enabled" style="font-size: 1.1rem; cursor: pointer;">Enable Bank Account Payments</label>
                                </div>
                                <small class="text-muted d-block mt-1">If disabled, residents will not see the bank transfer option.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-university mr-1"></i> Bank Name</label>
                                    <input type="text"
                                           class="form-control"
                                           name="bank_name"
                                           value="{{ $building->bank_name }}"
                                           placeholder="e.g. State Bank of India"
                                           autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-credit-card mr-1"></i> Bank Account Number</label>
                                    <input type="text"
                                           class="form-control"
                                           name="bank_account_number"
                                           value="{{ $building->bank_account_number }}"
                                           placeholder="e.g. 1234567890"
                                           autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fas fa-code mr-1"></i> Bank IFSC Code</label>
                                    <input type="text"
                                           class="form-control"
                                           name="bank_ifsc_code"
                                           value="{{ $building->bank_ifsc_code }}"
                                           placeholder="e.g. SBIN0001234"
                                           style="text-transform: uppercase;"
                                           autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-save mr-1"></i> Save Bank Settings
                                </button>
                            </div>
                        </div>
                    </form>
                    @else
                    <div class="row mb-4 pl-2">
                        <div class="col-md-12">
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input type="checkbox" class="custom-control-input" id="is_bank_enabled_disabled" disabled {{ ($building->is_bank_enabled ?? 'Yes') == 'Yes' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="is_bank_enabled_disabled" style="font-size: 1.1rem;">Enable Bank Account Payments</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-university mr-1"></i> Bank Name</label>
                                <input type="text" class="form-control" value="{{ $building->bank_name ?? 'Not set' }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card mr-1"></i> Bank Account Number</label>
                                <input type="text" class="form-control" value="{{ $building->bank_account_number ?? 'Not set' }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-code mr-1"></i> Bank IFSC Code</label>
                                <input type="text" class="form-control" value="{{ $building->bank_ifsc_code ?? 'Not set' }}" disabled>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                </div>
            </div>
            </div>
        </div>
        </section>
        

    @section('script')

     <script>
    $(document).ready(function(){
        var token = "{{csrf_token()}}";
        var role = $("#treasurer_type").val() || "{{$building->treasurer_type}}";
        var url = "{{url('/get-department-users')}}";
        var initialTreasurerId = "{{ $building->treasurer_id ?? '' }}";
        var buildingDefaultUserId = "{{ $building->user_id ?? '' }}";
        var buildingDefaultUserName = "{{ $building->user->name ?? '' }}";

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

    <script>
    // QR Code live preview when file is selected
    function previewQrCode(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#new-qr-img').attr('src', e.target.result);
                $('#new-qr-preview-row').show();
            };
            reader.readAsDataURL(input.files[0]);
            // Update label text
            var fileName = input.files[0].name;
            $(input).next('.custom-file-label').text(fileName);
        }
    }
    </script>

    @endsection

    @endsection




