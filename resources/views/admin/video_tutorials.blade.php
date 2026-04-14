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
    <div id="videoContent">
      <div class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">Loading videos...</p>
      </div>
    </div>
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
$(document).ready(function() {
  loadVideos();
});

function loadVideos() {
  $.get('/api/video_tutorials/post_video?limit=100', function(response) {
    if (response.success && response.data) {
      let html = '';

      if (response.data.length === 0) {
        html = '<div class="alert alert-info"><i class="fa fa-info-circle mr-2"></i>No videos available</div>';
      } else {
        response.data.forEach(function(category) {
          html += '<div class="mb-5">';
          html += '<h3 class="mb-4">' + category.title + '</h3>';
          html += '<div class="row">';

          category.data.forEach(function(video) {
            const videoUrl = video.videoUrl || '#';
            html += '<div class="col-md-4 col-sm-6 mb-4">';
            html += '<div class="card h-100 shadow-sm">';
            html += '<div class="position-relative" style="cursor:pointer;" onclick="openVideo(\'' + videoUrl + '\', \'' + video.title.replace(/'/g, "\\'") + '\', \'' + video.videoType + '\')">';
            html += '<img src="https://via.placeholder.com/480x270?text=' + encodeURIComponent(video.videoType.toUpperCase()) + '" class="card-img-top" alt="' + video.title + '" style="height:180px;object-fit:cover;">';
            html += '<div class="position-absolute d-flex align-items-center justify-content-center" style="top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.25);"><span style="font-size:48px;color:rgba(255,255,255,0.9);">▶</span></div>';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<h6 class="card-title font-weight-bold mb-1">' + video.title + '</h6>';
            if (video.text) {
              html += '<p class="card-text text-muted small">' + video.text.substring(0, 100) + '...</p>';
            }
            html += '<div class="mt-2"><span class="badge badge-info">' + video.videoType.toUpperCase() + '</span></div>';
            html += '</div>';
            html += '<div class="card-footer bg-white border-0 pt-0">';
            html += '<button class="btn btn-sm btn-outline-primary btn-block" onclick="openVideo(\'' + videoUrl + '\', \'' + video.title.replace(/'/g, "\\'") + '\', \'' + video.videoType + '\')">';
            html += '<i class="fa fa-play mr-1"></i> Watch Video';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
          });

          html += '</div>';
          html += '</div>';
        });
      }

      $('#videoContent').html(html);
    } else {
      $('#videoContent').html('<div class="alert alert-danger">Failed to load videos</div>');
    }
  }).fail(function() {
    $('#videoContent').html('<div class="alert alert-danger">Error loading videos from API</div>');
  });
}

function openVideo(url, title, type) {
  $('#playerTitle').text(title);

  let embedUrl = url;

  // Convert YouTube URL to embed format
  if (type === 'youtube') {
    const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})|(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
    if (youtubeMatch) {
      const videoId = youtubeMatch[1] || youtubeMatch[2];
      embedUrl = 'https://www.youtube.com/embed/' + videoId;
    }
  } else if (type === 'vimeo') {
    const vimeoMatch = url.match(/(?:vimeo\.com\/)(\d+)|(?:player\.vimeo\.com\/video\/)(\d+)/);
    if (vimeoMatch) {
      const videoId = vimeoMatch[1] || vimeoMatch[2];
      embedUrl = 'https://player.vimeo.com/video/' + videoId;
    }
  }

  $('#playerFrame').attr('src', embedUrl + '?autoplay=1');
  $('#videoPlayerModal').modal('show');
}

$('#videoPlayerModal').on('hidden.bs.modal', function () {
  $('#playerFrame').attr('src', '');
});
</script>
@endsection
