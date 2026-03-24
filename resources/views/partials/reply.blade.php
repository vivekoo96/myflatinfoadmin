<div class="media mt-3">
    <img src="{{ $reply->user->photo }}" class="mr-3 rounded-circle" width="40">
    <div class="media-body">
        <h6 class="mt-0">{{ $reply->user->name }}</h6>
        <p>{{ $reply->text }}</p>
        <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
    </div>
</div>
