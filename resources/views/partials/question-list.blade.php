<article class="{{ $type }} post-list">
    @forelse($posts as $post)
        @include('partials.post-item', ['type' => substr($type, 0, -1), 'post' => $post])
    @empty
        @include('partials.empty-container', ['message' => $emptyMessage ?? "Nothing else to see for now!"])
    @endforelse
</article>


