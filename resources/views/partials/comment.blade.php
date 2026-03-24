<div class="media mb-3">
    <img src="{{ $comment->user->photo ?? ''}}" class="mr-3 rounded-circle" width="50">
    <div class="media-body">
        <h5 class="mt-0">{{ $comment->user->name  ?? ''}}</h5>
        <p>{{ $comment->text }}</p>
        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
    </div>
</div>
