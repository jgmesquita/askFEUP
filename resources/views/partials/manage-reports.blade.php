@php
    $currentRoute = Route::currentRouteName();
@endphp

<section id="manage-reports" class="content-container">
    <h2>Manage Reports</h2>

    @if($reportedPosts->isEmpty())
        <p>No open reports available.</p>
    @else
        <section>
            <article class="reports-page">
            @foreach($reportedPosts as $post)
                <div>
                    @php
                        $questionUrl = '';
                        switch ($post->post_type) {
                            case 'question': 
                                $questionUrl = route('questions.show', ['id' => $post->post_id]);
                                break;
                            case 'answer':
                                $questionUrl = route('questions.show', ['id' => $post->post_details?->question_id]);
                                break;
                            case 'comment':
                                $questionUrl = route('questions.show', ['id' => $post->post_details?->answer?->question_id]);
                                break;
                            default:
                                break;
                        }
                    @endphp
                    <div class="report-post" data-item-id='{{ $loop->iteration }}' onclick="navigateToQuestion('{{ $currentRoute }}', '{{ $post->post_type }}', '{{ $questionUrl }}')">
                        @switch($post->post_type)
                            @case('question')
                                @include('partials.manage-reports-post', ['type' => 'question', 'post' => $post->question])
                                @break;
                            @case('answer')
                                @include('partials.manage-reports-post', ['type' => 'answer', 'post' => $post->answer])
                                @break;
                            @case('comment')
                                @include('partials.manage-reports-post', ['type' => 'comment', 'post' => $post->comment])
                                @break;
                        @endswitch
                        <div class="reports-info">
                            <div class="reports-group">
                                <p class="reports-label">Post Type:</p>
                                <p class="reports-type">{{ $post->post_type }}</p>
                            </div>
                            <div class="reports-group">
                                <p class="reports-label">Times Reported:</p>
                                <p class="reports-number">{{ $post->report_count }}</p>
                            </div>
                            <div class="reports-group">
                                <p class="reports-label">Report Reasons:</p>
                                <div class="reports-reasons">
                                    @foreach(json_decode($post->report_reasons) as $reason)
                                    <span class="action-item">{{ $reason }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="dropdown options admin-options" onclick="toggleDropdown(event)">
                            <button><i class="material-icons">more_horiz</i></button>
                            <div class="dropdown-content hidden">
                                <ul>
                                    <li class="icon-text" onclick="resolveReport(this,'{{ $post->report_ids }}')">
                                        <i class="material-symbols-outlined">task</i>
                                        <span>Resolve</span>                          
                                    </li> 
                                    <li class="icon-text" onclick="togglePopup(this, 'report-post')">
                                        <i class="material-symbols-outlined">delete</i>
                                        <span>Delete Post</span>                          
                                    </li>  
                                </ul>
                            </div>
                        </div>
                        <div class="popup hidden">
                            <div class="overlay" onclick="event.stopPropagation()"></div>
                                <div class="content" onclick="event.stopPropagation()">
                                    <h2>Delete Post</h2>
                                    <p>Are you sure you want to delete this {{ $post->post_type }}?</p>
                                    <div class=button-group>
                                        <button onclick="togglePopup(this, 'report-post')">Cancel</button>
                                        <form method="POST" action="{{route($post->post_type . '.delete', ['id' => $post->post_id])}}">
                                            @csrf
                                            <button type="submit">Delete</button>
                                        </form>  
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </article>
        </section>
        <div class="arrow-btn-container">
                <div class="pag"></div>
                <div class="leftside">
                    <button class="arrow-btn prev" onclick="prevPageReports()" disabled>
                        <i class="material-icons">chevron_left</i>
                    </button>
                    <div class="paginator" id="paginator"></div>
                    <button class="arrow-btn next" onclick="nextPageReports()">
                        <i class="material-icons">chevron_right</i>
                    </button>
                </div>
            </div>
    @endif
</section>