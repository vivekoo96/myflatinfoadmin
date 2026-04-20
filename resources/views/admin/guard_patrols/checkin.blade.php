@extends('layouts.admin')

@section('title')
    Guard Check-in
@endsection

@section('content')
    <!-- Content Header -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Guard Check-in</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Guard Check-in</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-8 offset-md-2">
            @if(session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session()->get('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif
            @if(session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session()->get('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Check-in Form</h3>
              </div>
              <form action="{{ route('guard-patrol.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">

                  <div class="form-group">
                    <label>Shift: <span class="text-red">*</span></label>
                    <select name="shift" id="shift" class="form-control" required>
                      <option value="">-- Select Shift --</option>
                      <option value="Day">Day</option>
                      <option value="Night">Night</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Check-in Type: <span class="text-red">*</span></label>
                    <select name="checkin_type" id="checkin_type" class="form-control" required>
                      <option value="">-- Select Type --</option>
                      <option value="photo">Photo Check-in</option>
                      <option value="qr">QR Check-in</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Patrol Location: <span class="text-red">*</span></label>
                    <select name="patrol_location_id" id="location-select" class="form-control" required>
                      <option value="">-- Select Location --</option>
                      @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                      @endforeach
                    </select>
                  </div>

                  <!-- Photo Section -->
                  <div id="photo-section" class="form-group" style="display:none;">
                    <label>Photo: <span class="text-red">*</span></label>
                    <input type="file" name="photo" id="photo-input" class="form-control" accept="image/*" capture="environment">
                    <small class="form-text text-muted">Click to take a photo or upload an image. On mobile, uses rear camera.</small>
                  </div>

                  <!-- QR Section -->
                  <div id="qr-section" class="form-group" style="display:none;">
                    <label>Scan QR Code: <span class="text-red">*</span></label>
                    <input type="text" name="qr_scanned_value" id="qr-value" class="form-control" placeholder="QR code will appear here">
                    <small class="form-text text-muted">Point camera at QR code to scan. Location will auto-fill.</small>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="start-scanner">Start Camera Scanner</button>
                    <video id="qr-video" style="display:none;width:100%;max-width:300px;margin-top:10px;border:1px solid #ccc;"></video>
                  </div>

                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit Check-in</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

@section('script')
<script>
  $(document).ready(function(){
    var token = "{{csrf_token()}}";

    // Show/hide sections based on type
    $('#checkin_type').on('change', function(){
      var type = $(this).val();
      if(type === 'photo') {
        $('#photo-section').show();
        $('#qr-section').hide();
        $('#photo-input').attr('required', true);
        $('#qr-value').removeAttr('required');
      } else if(type === 'qr') {
        $('#photo-section').hide();
        $('#qr-section').show();
        $('#photo-input').removeAttr('required');
        $('#qr-value').attr('required', true);
      } else {
        $('#photo-section').hide();
        $('#qr-section').hide();
        $('#photo-input').removeAttr('required');
        $('#qr-value').removeAttr('required');
      }
    });

    // QR auto-resolve
    $('#qr-value').on('change', function(){
      var qr_string = $(this).val().trim();
      if(qr_string === '') return;

      $.get('{{ route("guard-patrol.resolveQr") }}', {qr_string: qr_string}, function(r){
        if(r.success) {
          $('#location-select').val(r.id);
          $(this).css('border-color', '#28a745');
        } else {
          $('#location-select').val('');
          $(this).css('border-color', '#dc3545');
          alert('QR code not recognized for this building. Please check the QR code.');
        }
      });
    });

    // QR Camera Scanner (using BarcodeDetector API if available)
    $('#start-scanner').on('click', function(){
      var video = document.getElementById('qr-video');
      $(video).toggle();

      if(video.style.display !== 'none') {
        $(this).text('Stop Scanner');
        startQRScanner(video);
      } else {
        $(this).text('Start Camera Scanner');
        // Stop the stream
        if(video.srcObject) {
          var tracks = video.srcObject.getTracks();
          tracks.forEach(t => t.stop());
        }
      }
    });

    function startQRScanner(video) {
      if('BarcodeDetector' in window) {
        navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}})
          .then(stream => {
            video.srcObject = stream;
            video.play();

            const barcodeDetector = new BarcodeDetector({formats: ['qr_code']});
            const checkQR = () => {
              barcodeDetector.detect(video).then(barcodes => {
                if(barcodes.length > 0) {
                  var result = barcodes[0].rawValue;
                  $('#qr-value').val(result).trigger('change');
                  video.srcObject.getTracks().forEach(t => t.stop());
                  $('#qr-video').hide();
                  $('#start-scanner').text('Start Camera Scanner');
                } else {
                  if(video.style.display !== 'none') {
                    requestAnimationFrame(checkQR);
                  }
                }
              }).catch(err => console.log(err));
            };
            checkQR();
          }).catch(err => {
            alert('Camera access denied or not available. Please type QR code manually.');
            $('#qr-video').hide();
            $('#start-scanner').text('Start Camera Scanner');
          });
      } else {
        alert('Barcode Detection API not supported in this browser. Please enter QR code manually or use a supported browser.');
        $('#qr-video').hide();
      }
    }
  });
</script>
@endsection

@endsection
