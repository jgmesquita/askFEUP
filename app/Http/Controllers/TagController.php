<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    function showCreateForm() {
        $tags = Tag::all();
        return view('question_posts.create', compact('tags'));
    }

    function index(Request $request) {
        try {
        $this->authorize('viewAny', [Tag::class, $request]);

        $tags = Tag::all();
        return response()->json($tags);
        } catch (AuthorizationException $e) {
            return redirect('home')->with('error', 'You are not authorized to perform this action.');;
        }
    }

    public function followTag(Request $request, $id)
    {
        $user = Auth::user();
        $tag = Tag::findOrFail($id);

        if (!$user->followsTag($tag)) {
            $user->tags()->attach($id);
            return redirect()->back()->with('success', 'You are now following the tag: ' . $tag->name);
        }
        return redirect()->back()->with('error', 'You are already following this tag.');
    }

    public function unfollowTag(Request $request, $id) 
    {
        $user = Auth::user();
        $tag = Tag::findOrFail($id);

        if ($user->followsTag($tag)) {
            $user->tags()->detach($id);
            return redirect()->back()->with('success', 'You are no longer following the tag: ' . $tag->name);
        }
        return redirect()->back()->with('error', 'You are not following this tag.');
    }

    function showTags(Request $request) {
        $search = $request->query('tag-search');

        if ($search) {
            $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                ->withCount('questions', 'users')
                ->take(10)
                ->get();
        } else {
            $tags = Tag::withCount('questions', 'users')
                ->take(10)
                ->get();
        }
        
        return view('pages.show-tags', compact('tags'));
    }

    function showMoreTags(Request $request) {
        $page = $request->input('page',1);
        $perpage= 10;
        $offset = ($page -1) * $perpage;
        $search = $request->input('tag-search','');
        $filter = $request->input('filter', 'All');
        $user = Auth::user();
        $tagsQuery = Tag::query(); // Base da query

    // Aplica o filtro de pesquisa, se existir
    if ($search) {
        $tagsQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
    }

    // Aplica o filtro de associação ao usuário
    if ($filter === 'Following') {
        $tagsQuery->whereIn('id', $user->tags()->pluck('tag.id'));
    } elseif ($filter === 'Not Following') {
        $tagsQuery->whereNotIn('id', $user->tags()->pluck('tag.id'));
    }

    // Adiciona contagem de relações e paginação
    $tags = $tagsQuery->withCount('questions', 'users')
        ->skip($offset)
        ->take($perpage)
        ->get();

    // Renderiza o HTML das tags
    $tagshtml = view('partials.load-tags', ['tagsbomb' => $tags])->render();
    return response()->json($tagshtml);

        /*if ($search) {
            $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                ->withCount('questions', 'users')
                ->skip($offset)
                ->take($perpage)
                ->get();
        } else {
            $tags = Tag::withCount('questions', 'users')
                ->skip($offset)
                ->take($perpage)
                ->get();
        }

        $tagshtml= view('partials.load-tags', ['tagsbomb' => $tags])->render();
        return response()->json($tagshtml);*/
    }

    public function createTag(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tag,name',
            'color' => 'string',
            'textColor' => 'string'
        ]);
        $tag = Tag::create([
            'name' => $request->name,
            'color' => $request->color,
            'color_text' => $request->textColor
        ]);
        return redirect()->back()->with('success', 'Tag "' . $tag->name . '" created successfully!');
    }

    public function updateTag(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:tag,name,' . $id,
            'color' => 'string',
            'textColor' => 'string'
        ]);

        $tag->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'color_text' => $request->input('textColor')
        ]);
        return redirect()->back()->with('success', 'Tag updated successfully!');
    }

    public function deleteTag(Request $request, $id) {
        $tag = Tag::findOrFail($id);
        $currentUserId = Auth::id();

        DB::statement("SET app.current_user_id = '$currentUserId'");
        $tag->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully'
        ], 200);
    }
    public function contadora(){
        return Tag::all()->count();
    }
    public function getTagCount(Request $request)
    {
        $page = $request->input('page',1);

        $search = $request->input('tag-search','');
        $filter = $request->input('filter', 'All');
        $user = Auth::user();
        $tagsQuery = Tag::query(); // Base da query

        // Aplica o filtro de pesquisa, se existir
        if ($search) {
            $tagsQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        // Aplica o filtro de associação ao usuário
        if ($filter === 'Following') {
            $tagsQuery->whereIn('id', $user->tags()->pluck('tag.id'));
        } elseif ($filter === 'Not Following') {
            $tagsQuery->whereNotIn('id', $user->tags()->pluck('tag.id'));
        }
        
        $pagination=$this->renderPagination($page,$tagsQuery->count()/10);
        $html1=view('partials.paginator', ['pagination' => $pagination , 'currentPage' => $page])->render();
        $html2=view('partials.pag', ['pages' => ceil($tagsQuery->count()/10) , 'currentpage' => $page])->render();
        return response()->json([    'html1' => $html1,
        'html2' => $html2,]);
        
    }
    public function renderPagination($currentPage, $totalPages) {
        $maxVisiblePages = 6;
        $pagination = [];
    
        if ($totalPages <= $maxVisiblePages) {
            // Se o total de páginas for menor ou igual ao número máximo visível, mostra todas
            for ($i = 1; $i < $totalPages + 1; $i++) {
                $pagination[] = $i;
            }
        } else {
            // Lógica para mais de 7 páginas
            if ($currentPage <= 3) {
                // Exibe os primeiros números com "..." no final
                for ($i = 1; $i <= 4; $i++) {
                    $pagination[] = $i;
                }
                $pagination[] = '...';
                $pagination[] = $totalPages - 1;
                $pagination[] = $totalPages;
            } elseif ($currentPage >= $totalPages - 2) {
                // Exibe os últimos números com "..." no início
                $pagination[] = 1;
                $pagination[] = 2;
                $pagination[] = '...';
                for ($i = $totalPages - 3; $i <= $totalPages; $i++) {
                    $pagination[] = $i;
                }
            } else {
                // Exibe o padrão intermediário
                $pagination[] = 1;
                $pagination[] = '...';
                $pagination[] = $currentPage - 1;
                $pagination[] = $currentPage;
                $pagination[] = $currentPage + 1;
                $pagination[] = '...';
                $pagination[] = $totalPages;
            }
        }
    
        return $pagination;
    }
}