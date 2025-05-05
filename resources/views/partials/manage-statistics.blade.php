<section class="statistics content-container">
    <h2>Statistics</h2>
    <div class="statistics-content">
        <article class='container'>
            <p class='type'>Users</p>
            <p class='value'>{{ $totalUsers ?? 0 }}</p>
        </article>
        <article class='container'>
            <p class='type'>Questions</p>
            <p class='value'>{{ $totalQuestions ?? 0 }}</p>
            <p class='extra'>{{ $unansweredQuestions }} Unanswered</p>
        </article>
        <article class='container'>
            <p class='type'>Reports</p>
            <p class='value'>{{ $openReports ?? 0 }}</p>
            <p class='extra'>Open Reports</p>
        </article>
        <article class='container'>
            <p class='type'>Most Popular Tag</p>
            <p class='value'>{{ $mostPopularTag->name }}</p>
            <p class='extra'>{{ $mostPopularTag->questions_count }} questions</p>
        </article>
        <article class='container'>
            <p class='type'>Top Contributor</p>
            <p class='value'>{{ '@' . $topUser->tagname }}</p>
            <p class='extra'>{{ $topUser->posts_count ?? 0 }} posts made</p>
        </article>
    </div>
</section>