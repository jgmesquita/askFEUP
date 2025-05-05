<div class="extraInfo">
    <section class="badge_container">
        <h4>Badges</h4>
        <div class="badges">
            @if($badges->count() === 0)
                <p class='badge_name'>You haven't earned any badge yet.</p>
            @endif 
            @foreach($badges as $badge)
                <span class="badge_item" onclick="openBadge('{{ $badge->title }}', '{{ $badge->description }}', '{{ $badge->icon }}'); event.stopPropagation()">
                <img src="{{ asset('images/badges/' . $badge->icon) }}" alt="{{ $badge->title }}" class="badge_image">
                <span class="badge_name">{{ $badge->title }}</span>        
                <!-- @if($badge->title === 'Rising Star')
                        <img src="{{ asset('images/badges/risingStar.png') }}" alt="Rising Star" class="badge_image">
                    @elseif($badge->title === 'Contributor')
                        <img src="{{ asset('images/badges/contributor.png') }}" alt="Contributor" class="badge_image">
                    @elseif($badge->title === 'Expert')
                                <img src="{{ asset('images/badges/expert.png') }}" alt="Expert" class="badge_image">
                            @elseif($badge->title === 'Top Answerer')
                                <img src="{{ asset('images/badges/topAnswerer.png') }}" alt="Top Answerer" class="badge_image">
                            @elseif($badge->title === 'Community Leader')
                                <img src="{{ asset('images/badges/communityLeader.png') }}" alt="Community Leader" class="badge_image">
                            @else
                                <img src="{{ asset('images/badges/defaultBadge.png') }}" alt="Nothing Badge" class="badge_image">
                            @endif
                            -->
                        </span>

                        <!-- Badge Popup -->
            @endforeach
        </div>
    </section>
    <section class="user-statistics">
        <h4>Statistics</h4>
        <div class="statistics-content">
            <div class="statistics-item">
                <p class="statistics-count">{{ $user->questions()->count() }}</p>
                <p class="statistics-description">Questions made</p>
            </div>
            <div class="statistics-item">
                <p class="statistics-count">{{ $user->answers()->count() }}</p>
                <p class="statistics-description">Answers made</p>
            </div>
            <div class="statistics-item">
                <p class="statistics-count">{{ $user->comments()->count() }}</p>
                <p class="statistics-description">Comments made</p>
            </div>
            <div class="statistics-item">
                <p class="statistics-count">{{ $user->totalLikes() }}</p>
                <p class="statistics-description">Likes received</p>
            </div>
        </div>
    </section>
</div>

<script>
    function openBadge(title, description, icon) {
        const html = `
            <div class="popup"> 
                <div class="overlay"></div>
                <div class="content" onclick="event.stopPropagation()">
                    <img src="/images/badges/${icon}" alt="${title}" class="badge_image_popup">
                    <h2>${title}</h2>
                    <p>${description ? description : 'No description available for this badge.'}</p>
                </div>
            </div>`;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        document.body.appendChild(tempDiv);
        
        // Close popup when clicking on the overlay
        const overlay = tempDiv.querySelector('.overlay');
        overlay.addEventListener('click', () => {
            tempDiv.remove(); 
        });
    }
</script>