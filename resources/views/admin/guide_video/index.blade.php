@extends('layouts.admin')

@section('title') Video Tutorials @endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>Video Tutorials</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Video Tutorials</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('error') }}
      </div>
    @endif

    @if($modules->isEmpty())
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fa fa-play-circle fa-3x text-muted mb-3"></i>
          <p class="text-muted">No tutorial videos available yet.</p>
        </div>
      </div>
    @else
      @foreach($modules as $module)
        @if($module->videos->isNotEmpty())
          <h4 class="mb-3">{{ $module->title }}</h4>
          <div class="row mb-5">
            @foreach($module->videos as $video)
              @php
                $url = $video->video_url;
                $embedUrl = $url;
                if ($video->video_type === 'youtube') {
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m);
                    $videoId = $m[1] ?? null;
                    $embedUrl = $videoId ? 'https://www.youtube.com/embed/' . $videoId : $url;
                    $thumbnail = $videoId ? 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg' : null;
                } elseif ($video->video_type === 'vimeo') {
                    preg_match('/vimeo\.com\/(\d+)/', $url, $m);
                    $videoId = $m[1] ?? null;
                    $embedUrl = $videoId ? 'https://player.vimeo.com/video/' . $videoId : $url;
                    $thumbnail = null;
                } else {
                    $thumbnail = null;
                }
              @endphp
              <div class="col-md-4 col-sm-6 mb-4">
                <div class="card h-100 shadow-sm">
                  <div class="position-relative" style="cursor:pointer;"
                    onclick="openVideo('{{ $embedUrl }}', '{{ addslashes($video->title) }}')">
                    <img src="{{ $thumbnail ?: 'https://via.placeholder.com/480x270?text=Video' }}"
                      class="card-img-top" alt="{{ $video->title }}"
                      style="height:180px;object-fit:cover;">
                    <div class="position-absolute d-flex align-items-center justify-content-center"
                      style="top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.25);">
                      <span style="font-size:48px;color:rgba(255,255,255,0.9);">&#9654;</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <h6 class="card-title font-weight-bold mb-1">{{ $video->title }}</h6>
                    @if($video->text)
                      <p class="card-text text-muted small">{{ Str::limit($video->text, 100) }}</p>
                    @endif
                  </div>
                  <div class="card-footer bg-white border-0 pt-0">
                    <button class="btn btn-sm btn-outline-primary btn-block"
                      onclick="openVideo('{{ $embedUrl }}', '{{ addslashes($video->title) }}')">
                      <i class="fa fa-play mr-1"></i> Watch Video
                    </button>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      @endforeach
    @endif

  </div>
</section>

{{-- Video Player Modal --}}
<div class="modal fade" id="videoPlayerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="playerTitle"></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-0">
        <div style="position:relative;padding-bottom:56.25%;height:0;">
          <iframe id="playerFrame" src="" frameborder="0"
            style="position:absolute;top:0;left:0;width:100%;height:100%;"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
function openVideo(embedUrl, title) {
  $('#playerTitle').text(title);
  $('#playerFrame').attr('src', embedUrl + '?autoplay=1');
  $('#videoPlayerModal').modal('show');
}

$('#videoPlayerModal').on('hidden.bs.modal', function () {
  $('#playerFrame').attr('src', '');
});
</script>
@endsection
