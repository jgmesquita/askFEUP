@foreach ($pagination as $page)
        @if ($page === '...')
            <span class="dots">...</span>
        @else
            <button class="page-btn {{ $page == $currentPage ? 'active' : '' }}" 
                    onclick="loadcurrentpageadmintags({{ $page }})">
                {{ $page }}
            </button>
        @endif
    @endforeach