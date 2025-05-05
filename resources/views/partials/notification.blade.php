<a href="
    @switch($notification->type)
        @case('question_liked')
        @case('edit_tag')
            {{ route('questions.show', ['type' => 'question', $notification->question->id]) }}
            @break
        @case('question_answered')
        @case('answer_liked')
        @case('answer_commented')
        @case('answer_correct')
            {{ route('questions.show', ['type' => 'question', $notification->answer->question_id]) }}
            @break
        @case('question_commented')
        @case('comment_liked')
            {{ route('questions.show', ['type' => 'question', $notification->comment->answer->question_id]) }}
            @break
        @case('badge_received')
            {{ route('profile') }}
            @break
        @default
            javascript:void(0);
    @endswitch
">
    <article class="notification {{ $notification->is_read ? '' : 'unread' }}" data-item-id="{{ $notification->id }}">
        <div>
            @if ($notification->userTrigger)
            <div class="notifUserPicture">
                <img src="{{ asset($notification->userTrigger?->icon ?? 'images/profile/default-profile.jpg') }}" alt="{{ $notification->userTrigger->name }}'s Profile Picture">
            </div>
            @elseif ($notification->type === 'badge_received')
            <div class='system-picture'>
                <img src="{{ asset('images/badges/' . $notification?->badge->icon) }}" alt="{{ $notification?->badge->title }}" class="badge_image" style="transform: scale(1.5);">
            </div>
            @else
            <div class="system-picture">
                <i class="material-icons">info</i>
            </div>
            @endif

            <div class="notificationContent">
                <h2>
                    @if ($notification->userTrigger)
                        {{ '@' . $notification->userTrigger->tagname }}
                    @endif

                    @switch($notification->type)
                        @case('question_liked')
                            liked your question:
                            @break
                        @case('question_answered')
                            @if ($notification->answer->question->user_id === Auth::id())
                                answered your question:
                            @else 
                                answered a question you are following:
                            @endif
                            @break
                        @case('question_commented')
                            @if ($notification?->comment?->answer?->question?->user_id === Auth::id())
                                commented your question:
                            @else
                                commented on a question you are following:
                            @endif
                            @break
                        @case('answer_liked')
                            liked your answer:
                            @break
                        @case('answer_correct')
                            marked your answer as correct:
                            @break
                        @case('answer_commented')
                            commented on your answer:
                            @break
                        @case('comment_liked')
                            liked your comment:
                            @break
                        @case('edit_tag')
                            The tag of your question has been updated:
                            @break;
                        @case('badge_received')
                            New badge unlocked: "{{ $notification->badge->title }}"!
                            @break;
                    @endswitch
                </h2>

                <p>
                    @switch($notification->type)
                        @case('question_liked')
                            {{ $notification->question->title }}
                            @break
                        @case('question_answered')
                            {{ $notification->answer->text }}
                            @break
                        @case('question_commented')
                            {{ $notification->comment->text }}
                            @break
                        @case('answer_liked')
                            {{ $notification->answer->text }}
                            @break
                        @case('answer_correct')
                            {{ $notification->answer->text }}
                            @break
                        @case('answer_commented')
                            {{ $notification->comment->text }}
                            @break
                        @case('comment_liked')
                            {{ $notification->comment->text }}
                            @break
                        @case('edit_tag')
                            {{ $notification->question->title }}
                            @break;
                        @case('badge_received')
                            You have received a new badge, congratulations!
                            @break;
                        @default
                            performed an action
                    @endswitch
                </p>
            </div>
        </div>
        <div>
            @if(!$notification->is_read)<div class="unread-indicator"></div>@endif
            <p class="time">{{ \Carbon\Carbon::parse($notification->date)->diffForHumans() }}</p>
        </div>
    </article>
</a>