@if ($answer->comments->isNotEmpty())
    <div class="comments-section" id="comments-container-{{ $answer->id }}">
        @foreach ($answer->getCom() as $comment)
            @include('partials.post-item', ['post' => $comment, 'type' => 'comment'])
        @endforeach
    </div>
    @if ($answer->commentsCount()>3*$page )
            <button class="btn-view-more-comments show-more" id="more-{{ $answer->id }}" onclick="loadMoreComments('{{ $answer->id }}', '{{ $page }}')">
                &#x25BC; View more comments ({{ $answer->commentsCount()-3*$page }})
            </button>
    @endif
@endif