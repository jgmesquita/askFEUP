<div class="post-item">    
    <div class="{{ $type }}">
        <div class="content-post" data-item-id="{{ $post->id }}" onclick="navigateToQuestion('{{ $currentRoute }}', '{{ $type }}', '{{ $questionUrl }}')">   
            <div class="text-post"> 
                <div class="user-details">
                    <div class="user-picture">
                        <img src="{{ asset($post->user->icon ? $post->user->icon : 'images/profile/default-profile.jpg') }}" alt="{{ $post->user->name }}'s Profile Picture">
                    </div>
                    <small>
                        @php
                            $displayName = $post->user_id === Auth::id() ? 'You' : '@' . $post->user->tagname;
                        @endphp
                        <a class="user-name" href="/profile/{{ $post->user_id }}" onclick="event.stopPropagation()">{{ $displayName }} </a>
                        •
                        <span class="post-date">{{ \Carbon\Carbon::parse($post->date)->diffForHumans() }}</span>                       
                        @if($post->is_edited) 
                        •
                        <span class="post-edited">Edited</span>
                        @endif
                        @if (request()->is('*profile*') && $type === 'answer' && $post->is_correct ? 'correct' : '')
                            <span class="correct action-item">Correct Answer!</span>
                        @endif
                    </small>
                </div>

                @if($type === 'question')
                <h3>{{ $post->title }}</h3>
                @endif
                <p class="text">{{ $post->text }}</p>   
            </div>

            @if($post->tag)
            <div class="action-item tag" style="background-color:<?= $post->tag->color; ?>; color:<?= $post->tag->color_text ?>;">
                {{ $post->tag->name }}
            </div>
            @endif
        </div>
    </div>
</div>