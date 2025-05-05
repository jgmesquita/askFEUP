@extends('layouts.app')

@section('content')    
    <section id="questions-followed">        
        <h2>Followed Questions</h2>

        @include('partials.question-list', ['type' => 'questions', 'posts' => $questions, 'emptyMessage' => "Start following questions and they will be displayed here!"])
    </section>
    <div class="loadMore">
                <div class="loader hidden"></div>
    </div>
@endsection