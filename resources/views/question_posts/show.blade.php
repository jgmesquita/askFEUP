@extends('layouts.app')

@section('content')
    <section id="question-view">
        @include('partials.post-item', ['type' => 'question', 'post' => $question])

        @can('createAnswer', $question)
        <form class="answer" method="POST" action="{{ route('answers.store', ['id' => $question->id]) }}">
            {{ csrf_field() }}
            <textarea id="newAnswer" name="newAnswer" placeholder="Add an answer" required></textarea>
            <div class="button-group">
                <button type="submit">Answer</button>
            </div>
        </form>
        @endcan
        @if ($question->answers->isNotEmpty())
        <section class="answers-section">
            @foreach ($question->getAnswer() as $answer)
                <article class="answer {{ $answer->is_correct ? 'correct' : '' }}">
                    @include('partials.post-item', ['post' => $answer, 'type' => 'answer'])
                    @if ($answer->comments->isNotEmpty())
                        <div class="comments-section" id="comments-container-{{ $answer->id }}">
                            @foreach ($answer->getCom() as $comment)
                                @include('partials.post-item', ['post' => $comment, 'type' => 'comment'])
                            @endforeach
                        </div>
                        @if ($answer->commentsCount()>3)
                                <button class="btn-view-more-comments show-more" id="more-{{ $answer->id }}" onclick="loadMoreComments('{{ $answer->id }}', 1)">
                                    &#x25BC; View more comments ({{ $answer->commentsCount()-3 }})
                                </button>
                        @endif
                    @endif
                </article>
            @endforeach        
      
            <div class="loadMore">
                <div class="loader hidden"></div>
            </div>
    </section>
        @else
            @include('partials.empty-container', ['message' => 'No answers found'])
        @endif
    </section>
@endsection
