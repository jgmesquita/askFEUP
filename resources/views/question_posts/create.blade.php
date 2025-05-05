
@extends('layouts.app')

@section('content')
    <section id="create-question">
        <h1>New Question</h1>
        <form method="POST" id="create-question-form" action="{{ route('questions.store') }}">
            {{ csrf_field() }}
            
            <div class="form-group">              
                <input id="title" type="text" name="title" placeholder="Question Title" required>
                <label for="title">Question Title<span class="mandatory">*</span></label>     
            </div>
            <div class="custom-dropdown" id="customDropdown">
                <button type="button" class="dropdown-button" onclick="toggleTagDropdown()">Select a tag</button>
                <ul class="dropdown-options" id="dropdownOptions">
                    @foreach($tags as $tag)
                        <li onclick="selectOption('{{ $tag->id }}', '{{ $tag->name }}', 'tag')">{{ $tag->name }}</li>
                    @endforeach
                </ul>
                <input type="hidden" id="tag" name="tag" required>
            </div>

            <textarea id="body" name="body" placeholder="Enter your question here..." required></textarea>
            <button type="submit">Post</button>
        </form>      
    </section>
@endsection

