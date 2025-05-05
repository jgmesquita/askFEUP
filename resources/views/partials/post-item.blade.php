<div class="post-item">
    @php
        $questionUrl = '';
        switch ($type) {
            case 'question': 
                $questionUrl = route('questions.show', ['id' => $post->id]);
                break;
            case 'answer': 
                $questionUrl = route('questions.show', ['id' => $post->question_id]);
                break;
            default:
                break;
        }
        $currentRoute = Route::currentRouteName();
    @endphp
    
        <div class="{{ $type }}">
            @if (request()->is('*questions*') && $type === 'answer' && $post->is_correct ? 'correct' : '')
                <span class="correct action-item">Correct Answer!</span>
            @endif
            @if(
                Gate::allows('markCorrect', [$post]) ||
                Gate::allows('unfollowQuestion', [$post]) ||
                Gate::allows('followQuestion', [$post]) ||
                Gate::allows('report'.$type, [$post]) || 
                Gate::allows('editTag', [$post]) || 
                Gate::allows('updateQuestion', [$post, 'question']) || 
                Gate::allows('updateAnswer', [$post, 'answer']) ||
                Gate::allows('updateComment', [$post, 'comment']) ||
                Gate::allows('delete' . $type, [$post])
            )
            <div class="dropdown options" onclick="toggleDropdown(event)">
                <button><i class="material-icons">more_horiz</i></button>
                <div class="dropdown-content hidden">
                    <ul>
                        @can('markCorrect', [$post])
                            @isset($answer)
                            <li>
                                <form action="{{ route('answers.mark-correct', ['id' => $answer?->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit">
                                        <i class="material-symbols-outlined">check</i>
                                        <span>Mark as correct</span>
                                    </button>
                                </form>
                            </li>
                            @endisset
                        @endcan
                        @can('revokeCorrect', [$post])
                            @isset($answer)
                            <li>
                                <form action="{{ route('answers.revoke-correct', ['id' => $answer?->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit">
                                        <i class="material-symbols-outlined">remove</i>
                                        <span>Remove as correct</span>
                                    </button>
                                </form>
                            </li>
                            @endisset
                        @endcan
                        @can('unfollowQuestion', [$post])
                        <li>
                            <form action="{{ route('questions.unfollow', $post->id) }}" method="POST">
                                @csrf
                                <button type="submit">
                                    <i class="material-symbols-outlined">remove</i> 
                                    <span>Unfollow</span>
                                </button>
                            </form>
                        </li>
                        @endcan
                        @can('followQuestion', [$post])
                        <li>
                            <form action="{{ route('questions.follow', $post->id) }}" method="POST">
                                @csrf                              
                                <button type="submit">
                                    <i class="material-symbols-outlined">add</i>
                                    <span>Follow</span> 
                                </button>
                            </form>
                        </li>
                        @endcan
                        @can('report'.$type, [$post]) 
                        <li class="icon-text" onclick="openReportPopup('{{ $type }}', '{{ $post->id }}')">
                            <i class="material-symbols-outlined">flag</i>
                            <span>Report</span>
                        </li>
                        @endcan
                        @can('editTag', [$post])
                        <li class="icon-text" onclick="editTag('{{ $post->id }}', '{{ $post->tag }}')">
                            <i class="material-symbols-outlined">edit</i>
                            <span>Edit Tag</span>
                        </li>
                        @endcan
                        @can('updateQuestion', [$post, 'question'])
                        <li class="icon-text" onclick="editFunction('{{ $type }}', '{{ $post->id }}', '{{ $post->title}}', '{{ $post->text }}', '{{ $post->tag }}')">
                            <i class="material-symbols-outlined">edit</i>
                            <span>Edit</span>
                        </li>
                        @endcan
                        @can('updateAnswer', [$post, 'answer'])
                        <li class="icon-text" onclick="editFunction('{{ $type }}', '{{ $post->id }}', '', '{{ $post->text }}', '')">
                            <i class="material-symbols-outlined">edit</i>
                            <span>Edit</span>
                        </li>
                        @endcan
                        @can('updateComment', [$post, 'comment'])
                        <li class="icon-text" onclick="editFunction('{{ $type }}', '{{ $post->id }}', '', '{{ $post->text }}', '')">
                            <i class="material-symbols-outlined">edit</i>
                            <span>Edit</span>
                        </li>
                        @endcan
                        @can('delete'.$type, [$post])                       
                        <li class="icon-text" onclick="togglePopup(this, 'post-item')">
                            <i class="material-symbols-outlined">delete</i>
                            <span>Delete</span>                          
                        </li>  
                        @endcan   
                    </ul>
                </div>
            </div>  
            @endif

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
                            @if ($type === 'question' && $post->isFollowedBy(Auth::id()))
                            <span class="action-item">Following</span>
                            @endif
                            @if (request()->is('*profile*') && $type === 'answer' && $post->is_correct ? 'correct' : '')
                                <span class="correct action-item">Correct Answer!</span>
                            @endif
                        </small>
                    </div>
                    @if($type === 'answer')
                        @if(request()->is('api/profile/*') || request()->is('profile/*'))    
                            <p>                  
                                <a href="/questions/{{ $post->question->id }}">
                                    {{ $post->question->title }}
                                </a>
                                <a class="owner-name" href="/profile/{{ $post->question->user->id }}">
                                    by <strong>{{ '@' . $post->question->user->tagname }}</strong>
                                </a>
                            </p>
                        @endif    
                    @endif

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

                <div class="action-items">
                    <span class="action-item like {{ $post->isLikedBy(Auth::id()) ? 'liked' : '' }}" onclick="event.stopPropagation()">
                        <i class="material-{{ $post->isLikedBy(Auth::id()) ? 'icons' : 'symbols-outlined' }}" onclick="toggleLikePost('{{ $type }}', '{{ $post->id }}')">thumb_up</i> 
                        <span class="like-count">{{ $post->nr_likes }}</span>
                    </span>

                    @if($type === 'question')
                        <span class="action-item"><i class="material-symbols-outlined">chat_bubble</i> {{ $post->answers_count ?? 0 }}</span>
                        <span class="action-item"><i class="material-symbols-outlined">person</i>{{ $post->followersCount() ?? 0 }} Followers</span>
                    @elseif($type === 'answer')
                    <span class="action-item add-comment"
                        @if(Auth::check()) 
                            onclick="toggleCreateComment('{{ $type }}', '{{ $post->id }}'); event.stopPropagation()" 
                        @else 
                            onclick="window.location.href = '/login'" 
                        @endif>
                        <i class="material-symbols-outlined">chat_bubble</i>
                        Comment
                    </span>
                    @endif
                </div>
            </div>
            <div class="popup hidden">
                <div class="overlay"></div>
                    <div class="content" onclick="event.stopPropagation()">
                        <h2>Delete {{ $type }}</h2>
                        <p>Are you sure you want to delete this {{ $type }}?</p>
                        <div class=button-group>
                            <button onclick="togglePopup(this)">Cancel</button>
                            <form method="POST" action="{{route($type . '.delete', ['id' => $post->id])}}">
                                @csrf
                                <button type="submit">Delete</button>
                            </form>  
                        <div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

