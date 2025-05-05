<section class="section-add-tag content-container">
    <h2>Manage Tags</h2>

    <section class='flex-container'>
        <form class="search input-btn-form" id="tag-search-form" method="GET" onsubmit="submitSearch(event)">
            <button>
                <span><i class="material-icons">search</i></span>
            </button>
            <input 
                type="search" 
                id="tag-search-input"
                name="tag-search" 
                value="{{ request()->get('tag-search') }}" 
                class="search-field" 
                placeholder="Search tag..."
                autocomplete="off"
            />
        </form>
        <button onclick="openAddTag()">+ New Tag</button>
    </section>
    
    @include('partials.manage-tags-list', ['tags' => $tags])
    <div class="arrow-btn-container">
        <div class="pag"></div>
        <div class="leftside">
            <button class="arrow-btn prev" onclick="prevPageTagsAdmin()" disabled>
                <i class="material-icons">chevron_left</i>
            </button>
            <div class="paginator" id="paginator"></div>
            <button class="arrow-btn next" onclick="nextPageTagsAdmin()">
                <i class="material-icons">chevron_right</i>
            </button>
        </div>
    </div>
</section>