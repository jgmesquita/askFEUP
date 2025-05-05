<section class="section-user content-container">
    <h2>Manage Users</h2>
    <form class="search input-btn-form" action="{{ route('admin-center') }}" method="GET">
        <button>
            <span><i class="material-icons">search</i></span>
        </button>
        <input 
            type="search" 
            name="admin-search" 
            class="tagname-search"
            value="{{ request()->get('admin-search') }}" 
            class="search-field" 
            placeholder="Search tagname..."
            autocomplete="off"
        />
    </form>
    
    <section class="pagina">
        @include('partials.manage-userp', ['users' => $users])
    </section>

    <div class="arrow-btn-container">
        <div class="pag"></div>
        <div class="leftside">
            <button class="arrow-btn prev" onclick="prevPage()" disabled>
                <i class="material-icons">chevron_left</i>
            </button>
            <div class="paginator" id="paginator"></div>
            <button class="arrow-btn next" onclick="nextPage()" disabled>
                <i class="material-icons">chevron_right</i>
            </button>
        </div>
    </div>
</section>