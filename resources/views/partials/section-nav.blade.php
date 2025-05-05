<nav>
    <ul class="content-nav">
        @foreach($tabs as $tab)
            <li class="tab {{ $tab['selected'] ? 'selected' : '' }}" 
                onclick="loadSection('{{ $tab['url'] }}', '{{ $tab['type'] }}', '{{ $tab['page'] }}', this)">
                {{ $tab['label'] }}
            </li>
        @endforeach
    </ul>
</nav>
