@foreach ($tagsbomb as $tag)
            <article>
                <div class="action-item tag"  onclick="navigateToTagsQuestions('{{ $tag->name }}')" style="background-color:<?= $tag->color; ?>; color:<?= $tag->color_text ?>;">
                    <span>{{ $tag->name }}</span>
                </div>
                <p>{{ $tag->questions_count ?? 0 }} questions</p>
                <p>{{ $tag->users_count ?? 0 }} users following</p>

                @can('follow', $tag)
                <form method="POST" action="{{ route('follow-tag', ['id' => $tag->id]) }}" enctype="multipart/form-data" class="form-group" id="follow-tag-{{ $tag->id }}">
                    @csrf
                    <button type="submit">
                        <div class="icon-text">
                            <i class="material-symbols-outlined">add</i>
                            <span>Follow</span>
                        </div>
                    </button>
                </form>
                @endcan
                @can('unfollow', $tag)
                <form method="POST" action="{{ route('unfollow-tag', ['id' => $tag->id]) }}" enctype="multipart/form-data" class="form-group" id="unfollow-tag-{{ $tag->id }}">
                    @csrf
                    <button type="submit" class="active">
                        <div class="icon-text">
                            <i class="material-symbols-outlined">add</i>
                            <span>Unfollow</span>
                        </div>
                    </button>
                </form>
                @endcan
            </article>         
        @endforeach