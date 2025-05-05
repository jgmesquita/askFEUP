
    @foreach ($pagination as $page)
        @if ($page === '...')
            <span class="dots">...</span>
        @else
            <button class="page-btn {{ $page == $currentPage ? 'active' : '' }}" 
                    onclick="loadPageTags({{ $page }})">
                {{ $page }}
            </button>
        @endif
    @endforeach
