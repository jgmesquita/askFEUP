<?php

namespace App\Http\Controllers;

use App\Models\QuestionPost;
use App\Models\Tag;
use App\Models\PostReport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;

use Carbon\Carbon;

class QuestionPostController extends Controller
{
    public function index()
    { 
        try {
            $query = QuestionPost::with('user', 'tag')->withCount('answers');

            // Check if there is a search
            if (request()->has('question-search')) {
                $search = request()->get('question-search');

                if (empty($search)) {
                    return redirect('/home');
                }
                
                // Exact Search
                if (str_starts_with($search, '"') && str_ends_with($search, '"')) {
                    $search = trim($search, '"');
                    $query = $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%'])->take(10);

                }
                else if (str_starts_with($search, "(") && str_ends_with($search, ")")){
                    $search = trim($search,"()");
                    
                    $query = $query->join('tag', 'question_post.tag_id', '=', 'tag.id')
                                    ->whereRaw('LOWER(tag.name) = LOWER(?)', [$search])
                                    ->take(10)->orderByDesc('date');
                }
                // Full Text Search
                else {
                    $query = QuestionPost::with('user', 'tag')->withCount('answers')
                        ->whereRaw("tsvectors @@ plainto_tsquery('english', ?)", [$search])->take(10);
                }
                $questions = $query->get();               
            }
            else {
                $questions = $this->getTrendingQuestions(1,4,3);
            }
        } catch (\Exception $e) {            
            Log::error('Database connection error: ' . $e->getMessage());
            $questions = collect();
        }
        return view('home', compact('questions'));
    }

    public function renderSection($section, Request $request) {
        $page=$request->query('page',1);
        $search=$request->query('search','');
        $filter=$request->query('filter', 4);
        $filterans=$request->query('filterans', 3);
        $type = 'questions';
        switch ($section) {
            case 'new':
                $posts = $this->getNewQuestions($page, $filter, $filterans);
                break;
            case 'trending':
                $posts = $this->getTrendingQuestions($page, $filter, $filterans);
                break;
            case 'foryou':
                $posts = $this->getFYPosts($page, $filter, $filterans);
                break;
            default:
                if ($search) {
                    $posts = $this->getsearchQuestions($page,$search, $filter, $filterans);
                    
                }
                /*$posts = $this->getTrendingQuestions($page);
                return response()->json(['error' => 'Section is not valid.'], 400);*/
        }

    
        $html = view('partials.question-list', compact('posts', 'type'))->render();
        return response()->json(['html' => $html]);
        
    }    

    protected function getTrendingQuestions($page, $filter, $filterans)
    {
        $loadPerTime = 10;
        // Calcular o número de itens a partir da página (isso simula a paginação manualmente)
        $offset = ($page - 1) * $loadPerTime;
    
        $dateThreshold = null;
        switch ($filter) {
            case 1: 
                $dateThreshold = 1;
                break;
            case 2: 
                $dateThreshold = 7;
                break;
            case 3: 
                $dateThreshold = 31;
                break;
            case 4: 
                $dateThreshold = null;
                break;
            default:
                
                $dateThreshold = null;
                break;
        }

        $questions = QuestionPost::with('user', 'tag')
            ->withCount('answers')
            ->withCount('comments')
            ->selectRaw(
                '(nr_likes + (SELECT COUNT(*) FROM answer_post WHERE answer_post.question_id = question_post.id) + (SELECT COUNT(*) FROM comment_post WHERE comment_post.answer_id IN (SELECT id FROM answer_post WHERE answer_post.question_id = question_post.id))) / (EXTRACT(DAY FROM CURRENT_DATE - question_post.date) + 1)
                            AS trending_score'
            )
            ->selectRaw(
                'EXISTS (
                    SELECT 1 
                    FROM question_like 
                    WHERE question_like.post_id = question_post.id 
                    AND question_like.user_id = ?
                ) AS is_liked',
                [Auth::id()]
            )
            ->when($dateThreshold, function ($query) use ($dateThreshold) {
                return $query->whereRaw(
                    'EXTRACT(DAY FROM CURRENT_DATE - question_post.date) < ?',
                    [$dateThreshold]
                );
            })
            ->when($filterans, function ($query) use ($filterans) {
                if ($filterans == 1) {
                    
                    return $query->has('answers');
                } elseif ($filterans == 2) {
                    
                    return $query->doesntHave('answers');
                }
                
            })
            ->orderByDesc('trending_score') // Ordenar pelos mais relevantes
            ->skip($offset) // Simula a "página" para obter os itens corretos
            ->take($loadPerTime) // Limita a quantidade de itens (10 por página)
            ->get(); // Retorna uma Collection
    
        return $questions;
    }

    protected function getsearchQuestions($page,$search, $filter, $filterans){
        $loadPerTime = 10;

    $offset = ($page - 1) * $loadPerTime;

    $dateThreshold = null;
    switch ($filter) {
        case 1: 
            $dateThreshold = 1;
            break;
        case 2: 
            $dateThreshold = 7;
            break;
        case 3: 
            $dateThreshold = 31;
            break;
        case 4: 
        default:
            $dateThreshold = null;
            break;
    }

    
    $query = QuestionPost::with('user', 'tag')
        ->withCount('answers');

    
    if (str_starts_with($search, '"') && str_ends_with($search, '"')) {
        // exact search
        $search = trim($search, '"');
        $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%']);
    } elseif (str_starts_with($search, '(') && str_ends_with($search, ')')) {
        // tag search
        $search = trim($search, '()');
        $query->join('tag', 'question_post.tag_id', '=', 'tag.id')
              ->whereRaw('LOWER(tag.name) = LOWER(?)', [$search]);
    } else {
        // Full-Text search
        $query->whereRaw("tsvectors @@ plainto_tsquery('english', ?)", [$search]);
    }

    
    if ($dateThreshold) {
        $query->whereRaw(
            'EXTRACT(DAY FROM CURRENT_DATE - question_post.date) < ?',
            [$dateThreshold]
        );
    }


    $questions = $query->orderBy('date', 'desc')
        ->when($filterans, function ($query) use ($filterans) {
            if ($filterans == 1) {
                
                return $query->has('answers');
            } elseif ($filterans == 2) {
                
                return $query->doesntHave('answers');
            }
            
        })
        ->skip($offset)
        ->take($loadPerTime)
        ->get();

    return $questions;
        
    }

    protected function getNewQuestions($page, $filter, $filterans)
    {
        $loadPerTime = 10;

        // Calcular o número de itens a partir da página (isso simula a paginação manualmente)
        $offset = ($page - 1) * $loadPerTime;
        
        $dateThreshold = null;
        switch ($filter) {
            case 1: 
                $dateThreshold = 1;
                break;
            case 2: 
                $dateThreshold = 7;
                break;
            case 3: 
                $dateThreshold = 31;
                break;
            case 4: 
                $dateThreshold = null;
                break;
            default:
                
                $dateThreshold = null;
                break;
            }

        $questions = QuestionPost::with('user', 'tag')
        ->withCount('answers')
        ->selectRaw(
            'EXISTS (
                SELECT 1 
                FROM question_like 
                WHERE question_like.post_id = question_post.id 
                AND question_like.user_id = ?
            ) AS is_liked',
            [Auth::id()]
        )
        ->when($dateThreshold, function ($query) use ($dateThreshold) {
            return $query->whereRaw(
                'EXTRACT(DAY FROM CURRENT_DATE - question_post.date) < ?',
                [$dateThreshold]
            );
        })
        ->when($filterans, function ($query) use ($filterans) {
            if ($filterans == 1) {
                
                return $query->has('answers');
            } elseif ($filterans == 2) {
                
                return $query->doesntHave('answers');
            }
            
        })
        ->orderBy('date', 'desc')
        ->skip($offset)
        ->take($loadPerTime)
        ->get(); 
        return $questions;
    }

    protected function getFYPosts($page, $filter, $filterans)
    {
        $loadPerTime = 10;
        $offset = ($page - 1) * $loadPerTime;

        $dateThreshold = null;
        switch ($filter) {
            case 1: 
                $dateThreshold = 1;
                break;
            case 2: 
                $dateThreshold = 7;
                break;
            case 3: 
                $dateThreshold = 31;
                break;
            case 4: 
                $dateThreshold = null;
                break;
            default:
                $dateThreshold = null;
                break;
        }

        $user = Auth::user();
        $tagsFollowed = $user->tags()->pluck('id');
        return QuestionPost::with('user', 'tag')
            ->withCount('answers')
            ->selectRaw(
                'EXISTS (
                    SELECT 1 
                    FROM question_like 
                    WHERE question_like.post_id = question_post.id 
                    AND question_like.user_id = ?
                ) AS is_liked',
                [Auth::id()]
            )
            ->whereIn('tag_id', $tagsFollowed)
            ->when($dateThreshold, function ($query) use ($dateThreshold) {
                return $query->whereRaw(
                    'EXTRACT(DAY FROM CURRENT_DATE - question_post.date) < ?',
                    [$dateThreshold]
                );
            })
            ->when($filterans, function ($query) use ($filterans) {
                if ($filterans == 1) {
                    
                    return $query->has('answers');
                } elseif ($filterans == 2) {
                    
                    return $query->doesntHave('answers');
                }
                
            })
            ->orderBy('date', 'desc')
            ->skip($offset) 
            ->take($loadPerTime)
            ->get();
        
    }

    public function create()
    {
        try {
            $this->authorize('viewAny', QuestionPost::class);
            $tags = Tag::all();
            return view('question_posts.create', compact('tags'));
        } catch (AuthorizationException $e) {
            return redirect('login');
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'body' => 'required|string|max:1000',
            'title' => 'required|string|max:255|unique:question_post,title', 
            'tag' => 'nullable|integer', 
        ]);
    
        $question = QuestionPost::create([
            'text' => $validatedData['body'], 
            'user_id' => Auth::id(),
            'title' => $validatedData['title'],
            'tag_id' => $validatedData['tag'], 
            'date' => Carbon::now(), 
            'is_edited' => false,
        ]);
    
        return redirect()->route('questions.show', ['id' => $question->id])
                         ->with('success', 'Question post created successfully.');
    }
    

    public function show($id)
    {
        $question = QuestionPost::with('user', 'tag')
        ->withCount('answers')
        ->findOrFail($id); 
        
        $question->is_liked = auth()->check() ? $question->isLikedBy(auth()->id()) : false;

        return view('question_posts.show', compact('question'));
    }

    //json enconde para lazy loading
    public function loadMore(Request $request)
    {
        $loadPerTime = 10; // Número de perguntas carregadas por vez
        $page = $request->query('page', 1); // Página atual
    
        $questions = QuestionPost::with(['user', 'tag'])
            ->withCount('answers')
            ->orderBy('date', 'desc')
            ->paginate($loadPerTime, ['*'], 'page', $page);
    
        return response()->json([
            'html' => view('partials.question-list', ['type' => 'questions', 'posts' => $questions])->render(),
            'hasMorePages' => $questions->hasMorePages(),
        ]);
    }

    public function showWithAnswers($id)
    {
        $question = QuestionPost::with([
            'answers' => function ($query) {
                $query->orderBy('is_correct', 'desc')
                      ->orderBy('date', 'desc'); // Ordena as respostas pela data mais recente
            },
            'answers.comments' => function ($query) {
                $query->orderBy('date', 'desc'); // Ordena os comentários pela data mais recente
            },
            'user',
            'tag'
        ])
            ->findOrFail($id);

        return view('question_posts.show', compact('question'));
    }

    public function edit(QuestionPost $questionPost)
    {
        return view('question_posts.edit', compact('questionPost'));
    }

    public function update(Request $request, $id)
    {
        try {
            $post = QuestionPost::findOrFail($id);

            $this->authorize('updateQuestion', [$post, 'question']);

            $request->validate([
                'text' => 'required|string',
                'title' => 'required|string|max:255|unique:question_post,title,' . $post->id,
                'tag_id' => 'nullable|exists:tags,id',
            ]);

            $isUpdated = $post->text !== $request->text || $post->title !== $request->title || $post->tag_id !== (int)$request->tag;

            if (!$isUpdated) return redirect()->back()->with('info', 'No changes detected.');

            $currentUserId = Auth::id();
            DB::statement("SET app.current_user_id = '$currentUserId'");

            $post->update([
                'text' => $request->text,
                'title' => $request->title,
                'is_edited' => true,
                'tag_id' => $request->tag
            ]);

            return redirect()->back()->with('success', 'Question post updated successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function updateTag(Request $request, $id)
    {
        try {
            $post = QuestionPost::findOrFail($id);

            $this->authorize('editTag', [$post]);

            $request->validate([
                'tag_id' => 'nullable|exists:tags,id',
            ]);

            $isUpdated = $post->tag_id !== (int)$request->tag;

            if (!$isUpdated) return redirect()->back()->with('info', 'No changes detected.');

            $currentUserId = Auth::id();
            DB::statement("SET app.current_user_id = '$currentUserId'");

            $post->update([
                'tag_id' => $request->tag
            ]);

            return redirect()->back()->with('success', 'Tag was updated successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function delete($id)
    {
        try {
            $question = QuestionPost::findOrFail($id);
            
            $this->authorize('deleteQuestion', [$question, 'question']);

            $currentUserId = Auth::id();
            DB::statement("SET app.current_user_id = '$currentUserId'");
            
            $question->delete();
            
            $prevUrl = url()->previous(); // Url of the previous page
            if (strpos($prevUrl, "questions/{$id}") !== false) {
                return redirect()->route('home')->with('success', 'Question post deleted successfully.');
            }
            return redirect()->back()->with('success', 'Question post deleted successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        } 
    }

    public function getQuestion($id)
    {
        $question = QuestionPost::findOrFail($id);
        return response()->json($question);
    }

    public function getAnswers($questionId, $page = 1)
    {
        
        $question = QuestionPost::findOrFail($questionId);

        // Pega as respostas para a questão com a paginação
        $answers = $question->getAnswer($page);

        // Gera o HTML das respostas usando a view 'partials.post-item' para cada resposta
        $answersHtml = $answers->map(function ($answer) {
            // Retorna o HTML de cada resposta com a view 'partials.post-item' e passando 'answer' como 'post'
            return view('partials.post-item', ['post' => $answer, 'type' => 'answer'])->render();
        });

        // Retorna o array de views como resposta JSON para o frontend (ou pode retornar como quiser)
        return response()->json($answersHtml);
    }

    public function likeQuestion(Request $request, $id) 
    {
        try {
            $question = QuestionPost::findOrFail($id);
            
            $this->authorize('like', $question);

            $user = auth()->user();
            $question->toggleLike($user);

            return response()->json([
                'success' => true,
            ]);
        } catch (AuthorizationException $e) {
            if (!auth()->check()) {
                // Redirect to the login page
                return response()->json([
                    'success' => false,
                    'message' => 'You need to log in to perform this action.',
                    'redirect' => route('login'), 
                ], 401); // 401 Unauthorized
            }
    
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403); // 403 Forbidden
        }
    }
    public function followQuestion($id)
    {
        $user = auth()->user();
        $question = QuestionPost::findOrFail($id);

        if (!$user->followedQuestions()->where('question_id', $id)->exists()) {
            $user->followedQuestions()->attach($id);
            return redirect()->back()->with('success', 'You are now following this question.');
        }

        return redirect()->back()->with('info', 'You are already following this question.');
    }
    public function unfollowQuestion($id)
    {
        $user = auth()->user();
        $user->followedQuestions()->detach($id);

        return redirect()->back()->with('success', 'You have unfollowed the question.');
    }

    public function report(Request $request, $id) 
    {
        $question = QuestionPost::findOrFail($id);

        $validatedData = $request->validate([
            'reason_id' => 'required|string|max:1000',
        ]);
    
        $question = PostReport::create([
            'user_id' => Auth::id(),
            'post_type' => 'question',
            'post_id' => $question->id, 
            'reason_id' => $validatedData['reason_id'],
            'date' => Carbon::now(), 
        ]);

        return redirect()->back()->with('success', 'Thank you! Your report will be reviewed shortly.');
    }
}
 
