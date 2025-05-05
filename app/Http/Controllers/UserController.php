<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\QuestionPost;
use App\Models\AnswerPost;
use App\Models\CommentPost;
use App\Models\PostReport;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // Create a new user
    public function store(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            'tagname' => 'required|string|max:255|unique:user,tagname',
            'email' => 'required|string|email|max:255|unique:user,email',
            'password' => 'required|string|min:8',
            'age' => 'nullable|integer|min:18',
            'country' => 'nullable|string|max:100',
            'degree' => 'nullable|string|max:255',
            'icon' => 'nullable|string',
            'is_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'is_banned' => 'boolean',
        ]);

        // Create and save the user
        $user = User::create([
            'name' => $request->name,
            'tagname' => $request->tagname,
            'email' => $request->email,
            'password' => bcrypt($request->password), // Encrypt the password
            'age' => $request->age,
            'country' => $request->country,
            'degree' => $request->degree,
            'icon' => $request->icon,
            'is_admin' => $request->is_admin,
            'is_moderator' => $request->is_moderator,
            'is_banned' => $request->is_banned,
        ]);

        return response()->json($user, 201);
    }
    
    // Show user
    public function show($id = null)
    {
        try {
            $user = $id ? User::findOrFail($id) : Auth::user();
            
            $this->authorize('showUser', $user);

            $tags = $user->tags()
                ->take(5)
                ->get();
            
            $questions = $user->questions()
                ->withCount('answers')
                ->with('tag')
                ->orderByDesc('date')
                ->get();
    
            return view('pages.profile', compact('user', 'tags', 'questions'));
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'User not found.');
        } catch (AuthorizationException $e) {
            if (Auth::user())
                return redirect()->back()->with('error', 'You are not authorized to view this content.');
            else 
                return redirect('login');
        }
    }

    public function userTags(Request $request, $id) {
        $user = User::findOrFail($id);
        $offset = $request->query('offset');

        $tags = $user->tags()
            ->skip($offset)
            ->get();

        return response()->json($tags);
    }
 
    public function profileSection($section, Request $request) 
    {
        $page = $request->query('page', 1);
        $perPage = 5;
        $offset = ($page - 1) * $perPage;
        $id = $request->query('id', null);
        $user = $id ? User::findOrFail($id) : Auth::user();
        try {
            $this->authorize('viewProfileSection', [User::class, $request]);
            
    
            if (!$user) {
                return redirect()->route('login');
            }
            
            switch ($section) {
                case 'question':
                    $type = 'questions';
                    $posts = $user->questions()
                        ->withCount('answers')
                        ->with('tag')
                        ->orderByDesc('date')
                        ->skip($offset)
                        ->take($perPage)
                        ->get();
                    break;
                case 'answer':
                    $type = 'answers';
                    $posts = $user->answers()
                        ->withCount('comments')
                        ->with(['question.user'])
                        ->orderByDesc('date')
                        ->skip($offset)
                        ->take($perPage)
                        ->get();
                    break;
                default:
                    return response()->json('', 400);
            }
            
            $html = view('partials.question-list', compact('posts','type'))->render();
            return response()->json(['html' => $html]);
        } catch (ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'User not found.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to view this content.');
        }
    }

    public function toggleDarkMode(Request $request)
    {
        $request->validate([
            'is_dark_mode' => 'required|boolean',
        ]);

        $user = User::findOrFail(Auth::id());
        $user->is_dark_mode = $request->is_dark_mode;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Dark mode preference updated successfully.',
        ]);
    }

    public function managerSection($section, Request $request) 
    {
        switch ($section) {
            case 'users':
                $users = [];
                if (request()->has('admin-search')) {
                    $search = request()->get('admin-search');
                    $users = User::whereRaw('LOWER(tagname) LIKE ?', ['%' . strtolower($search) . '%'])->orderBy('tagname', 'asc')->take(10)->get();
                }
                else {
                    $users = User::orderBy('name', 'asc')->take(10)->get();
                }
                $html = view('partials.manage-users', compact('users'))->render();
                break;

                
            case 'statistics':
                $totalUsers = User::count();
                $totalQuestions = QuestionPost::count();
                $unansweredQuestions = QuestionPost::doesntHave('answers')->count();
                $openReports = PostReport::where('status', 'open')->count();
                $mostPopularTag = Tag::withCount('questions')->orderBy('questions_count', 'desc')->first();
                $topUser = User::getTopUser();

                $html = view('partials.manage-statistics', compact(
                    'totalUsers', 'totalQuestions', 'unansweredQuestions', 'openReports', 'mostPopularTag', 'topUser',
                ))->render();
                break;
            case 'tags':
                $tags = [];
                if (request()->has('tag-search')) {
                    $search = request()->get('tag-search');
                    $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])->take(10)->get();
                    $html = view('partials.manage-tags-list', compact('tags'))->render();
                }
                else { 
                    $tags = Tag::take(10)->get(); 
                    $html = view('partials.manage-tags', compact('tags'))->render();
                }        
                break;
            case 'reports':
                $reportedPosts = PostReport::getReportedPosts()->take(10);
                $html = view('partials.manage-reports', compact('reportedPosts'))->render();
                break;
            default:
                return response()->json('', 400);
        }        
        return response()->json(['html' => $html]);
    }

    public function loadMoreTagsadmin(Request $request) {
        $page = $request->input('page',1);
        $perPage = 10;
        $offset = ($page -1) * $perPage;
        $tags = [];
        if (request()->has('search')) {
            $search = request()->get('search');
            $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])->skip($offset)->take($perPage)->get();
            $html = view('partials.manage-tags-list-load', compact('tags'))->render();
        }
        else { 
            $tags = Tag::skip($offset)->take($perPage)->get(); 
            $html = view('partials.manage-tags-list-load', compact('tags'))->render();
        }
        return response()->json(['html' => $html]); 
    }
    public function admintagscount(Request $request){
        $page = $request->input('page',1);
 
        $tags = [];
        if (request()->has('search')) {
            $search = request()->get('search');
            $tags = Tag::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])->get();
            
        }
        else { 
            $tags = Tag::get(); 
            
        }
        $pagination= $this->renderPagination($page, ceil($tags->count()/10));
        $html1=view('partials.paginatortagsad', ['pagination' => $pagination , 'currentPage' => $page])->render();
        $html2=view('partials.pag', ['pages' => ceil($tags->count()/10) , 'currentpage' => $page])->render();
        return response()->json([    'html1' => $html1,
        'html2' => $html2,]);
    }

    public function loadMorePostsadmin(Request $request) {
        $page = $request->input('page',1);
        $perPage = 10;
        $offset = ($page -1) * $perPage;
        $reportedPosts = PostReport::getReportedPosts()->skip($offset)->take(10);
        $html = view('partials.manage-reports-load', compact('reportedPosts'))->render();
        return response()->json(['html' => $html]);
        
    }
    public function countreports(Request $request){
        $page = $request->input('page',1);

        $reportedPosts = PostReport::getReportedPosts()->count();
        $pagination = $this->renderPagination($page, ceil($reportedPosts/10));
        $html1=view('partials.paginatorrep', ['pagination' => $pagination , 'currentPage' => $page])->render();
        $html2=view('partials.pag', ['pages' => ceil($reportedPosts/10) , 'currentpage' => $page])->render();
        return response()->json([    'html1' => $html1,
        'html2' => $html2,]);
    }

    public function showEdit($id = null) {
        try {
            $this->authorize('update', Auth::user());
            return view('pages.edit-profile');
        } catch (AuthorizationException $e) {
            if (Auth::user()) return redirect()->back()->with('error', 'You are not authorized to perform this action.');
            else return redirect('login');
        }
    }

    public function showAdmin() {
        try {
            $this->authorize('showAdmin', User::class);
            if (request()->has('admin-search')) {
                $search = request()->get('admin-search');
                
                // Exact Search
                $users = User::where('is_deleted', false)
                    ->whereRaw('LOWER(tagname) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orderBy('tagname', 'asc')
                    ->take(10)
                    ->get();
            }
            else {
                $users = User::where('is_deleted', false)
                    ->orderBy('name', 'asc')
                    ->take(10)
                    ->get();
            }

            $tags = Tag::all();
            $usersCount = User::count();
            $questionsCount = QuestionPost::count();
            $answersCount = AnswerPost::count();
            $commentsCount = CommentPost::count();
            $adminsCount = User::where('is_admin', true)->count();
            $modsCount = User::where('is_moderator', true)->count();
            $bannedCount = User::where('is_banned', true)->count();
        
            return view('pages.admin-center', compact('users', 'tags', 'usersCount', 'questionsCount', 'answersCount', 'commentsCount', 'adminsCount', 'modsCount', 'bannedCount'));

        } catch (AuthorizationException $e) {
            if (Auth::user()) return redirect()->back()->with('error', 'You are not authorized to perform this action.');
            else return redirect('login');
        }
    }

    public function showContacts() {
        return view('pages.contacts');
    }

    public function showLeaderboard()
    {
        $users = DB::table('user')
            ->select(
                'user.id',
                'user.name',
                'user.tagname',
                'user.icon',
                DB::raw('
                    (
                        (SELECT COALESCE(SUM(nr_likes), 0) FROM question_post WHERE question_post.user_id = "user".id) +
                        (SELECT COALESCE(SUM(nr_likes), 0) FROM answer_post WHERE answer_post.user_id = "user".id) +
                        (SELECT COALESCE(SUM(nr_likes), 0) FROM comment_post WHERE comment_post.user_id = "user".id)
                    ) AS total_likes
                ')
            )
            ->orderByDesc('total_likes')
            ->take(10)
            ->get();
    
        return view('pages.leaderboard', compact('users'));
    }

    public function filterLeaderboard(Request $request) {
        $filter = $request->query('filter');

        switch($filter) {
            case 'month' : 
                $users = DB::table('user')
                    ->select(
                        'user.id',
                        'user.name',
                        'user.tagname',
                        'user.icon',
                        DB::raw('
                            (
                                (
                                    SELECT COALESCE(COUNT(question_like.post_id), 0) 
                                    FROM question_like
                                    JOIN question_post ON question_post.id = question_like.post_id
                                    WHERE question_post.user_id = "user".id
                                    AND question_like.date >= NOW() - INTERVAL \'1 month\'
                                ) + 
                                (
                                    SELECT COALESCE(COUNT(answer_like.post_id), 0) 
                                    FROM answer_like
                                    JOIN answer_post ON answer_post.id = answer_like.post_id
                                    WHERE answer_post.user_id = "user".id
                                    AND answer_like.date >= NOW() - INTERVAL \'1 month\'
                                ) +
                                (
                                    SELECT COALESCE(COUNT(comment_like.post_id), 0) 
                                    FROM comment_like
                                    JOIN comment_post ON comment_post.id = comment_like.post_id
                                    WHERE comment_post.user_id = "user".id
                                    AND comment_like.date >= NOW() - INTERVAL \'1 month\'
                                )
                            ) AS total_likes
                        ')
                    )
                    ->orderByDesc('total_likes')
                    ->take(10)
                    ->get();
                break;
            case 'week' : 
                $users = DB::table('user')
                    ->select(
                        'user.id',
                        'user.name',
                        'user.tagname',
                        'user.icon',
                        DB::raw('
                            (
                                (
                                    SELECT COALESCE(COUNT(question_like.post_id), 0) 
                                    FROM question_like
                                    JOIN question_post ON question_post.id = question_like.post_id
                                    WHERE question_post.user_id = "user".id
                                    AND question_like.date >= NOW() - INTERVAL \'1 week\'
                                ) + 
                                (
                                    SELECT COALESCE(COUNT(answer_like.post_id), 0) 
                                    FROM answer_like
                                    JOIN answer_post ON answer_post.id = answer_like.post_id
                                    WHERE answer_post.user_id = "user".id
                                    AND answer_like.date >= NOW() - INTERVAL \'1 week\'
                                ) +
                                (
                                    SELECT COALESCE(COUNT(comment_like.post_id), 0) 
                                    FROM comment_like
                                    JOIN comment_post ON comment_post.id = comment_like.post_id
                                    WHERE comment_post.user_id = "user".id
                                    AND comment_like.date >= NOW() - INTERVAL \'1 week\'
                                )
                            ) AS total_likes
                        ')
                    )
                    ->orderByDesc('total_likes')
                    ->take(10)
                    ->get();
                break;
            case 'year' : 
                $users = DB::table('user')
                    ->select(
                        'user.id',
                        'user.name',
                        'user.tagname',
                        'user.icon',
                        DB::raw('
                            (
                                (
                                    SELECT COALESCE(COUNT(question_like.post_id), 0) 
                                    FROM question_like
                                    JOIN question_post ON question_post.id = question_like.post_id
                                    WHERE question_post.user_id = "user".id
                                    AND question_like.date >= NOW() - INTERVAL \'1 year\'
                                ) + 
                                (
                                    SELECT COALESCE(COUNT(answer_like.post_id), 0) 
                                    FROM answer_like
                                    JOIN answer_post ON answer_post.id = answer_like.post_id
                                    WHERE answer_post.user_id = "user".id
                                    AND answer_like.date >= NOW() - INTERVAL \'1 year\'
                                ) +
                                (
                                    SELECT COALESCE(COUNT(comment_like.post_id), 0) 
                                    FROM comment_like
                                    JOIN comment_post ON comment_post.id = comment_like.post_id
                                    WHERE comment_post.user_id = "user".id
                                    AND comment_like.date >= NOW() - INTERVAL \'1 year\'
                                )
                            ) AS total_likes
                        ')
                    )
                    ->orderByDesc('total_likes')
                    ->take(10)
                    ->get();
                break;
            case 'all' :
                $users = DB::table('user')
                    ->select(
                        'user.id',
                        'user.name',
                        'user.tagname',
                        'user.icon',
                        DB::raw('
                            (
                                (SELECT COALESCE(SUM(nr_likes), 0) FROM question_post WHERE question_post.user_id = "user".id) +
                                (SELECT COALESCE(SUM(nr_likes), 0) FROM answer_post WHERE answer_post.user_id = "user".id) +
                                (SELECT COALESCE(SUM(nr_likes), 0) FROM comment_post WHERE comment_post.user_id = "user".id)
                            ) AS total_likes
                        ')
                    )
                    ->orderByDesc('total_likes')
                    ->take(10)
                    ->get();
                break;
        }
        $html = view('partials.leaderboard-users', compact('users'))->render();  
        return response()->json($html, 200);
    }
    
    public function updateName(Request $request, $id) 
    {
        try {
            $user = User::findOrFail($id);
            if (!$user) return back()->with('error', 'User not found.');

            $this->authorize('update', $user);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            if ($user->name === $validatedData['name']) {
                return back()->with('info', 'No changes detected in name.');
            }

            $user->name = $validatedData['name'];
            $user->save();

            return back()->with('success', 'Name updated successfully.');

        } catch (AuthorizationException $e) {
            if (Auth::user()) return redirect()->back()->with('error', 'You are not authorized to perform this action.');
            else return redirect('login');
        }
    }

    public function updateTagName(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        $validatedData = $request->validate([
            'tagname' => 'required|string|max:255|unique:user,tagname,' . $id
        ]);

        if ($user->tagname === $validatedData['tagname']) {
            return back()->with('info', 'No changes detected in tagname.');
        }

        $user->tagname = $validatedData['tagname'];
        $user->save();

        return back()->with('success', 'Tagname updated successfully.');
    }

    public function updateEmail(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        $validatedData = $request->validate([
            'old-email' => 'required|string|email|max:255', 
            'new-email' => 'required|string|email|max:255|unique:user,email', 
            'confirm-email' => 'required|string|email|max:255|same:new-email', 
        ]);

        if ($user->email !== $validatedData['old-email']) {
            return back()->with('error', 'The current email does not match our records.');
        }

        $user->email = $validatedData['new-email'];
        $user->save();

        return back()->with('success', 'Email updated successfully.');
    }

    public function updatePassword(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        if (!Hash::check($request['old-password'], $user->password)) {
            return back()->with('error', 'The current password is incorrect.');
        }

        $validatedData = $request->validate([
            'old-password' => 'required|string', 
            'new-password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($validatedData['new-password']); 
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateAge(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        $validatedData = $request->validate([
            'age' => 'required|integer|min:18'
        ]);

        if ($user->age == $validatedData['age']) return back()->with('info', 'No changes detected in age.');

        $user->age = $validatedData['age'];
        $user->save();

        return back()->with('success', 'Age updated successfully.');
    }

    public function updateCountry(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        $validatedData = $request->validate([
            'country' => 'required|string|max:100'
        ]);

        if ($user->country === $validatedData['country']) return back()->with('info', 'No changes detected in country.');

        $user->country = $validatedData['country'];
        $user->save();

        return back()->with('success', 'Country updated successfully.');
    }

    public function updateDegree(Request $request, $id) 
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        if ($user->degree === $request->degree) return back()->with('info', 'No changes detected in degree.');

        $validatedData = $request->validate([
            'degree' => 'required|string|max:255'
        ]);

        $user->degree = $validatedData['degree'];
        $user->save();

        return back()->with('success', 'Degree updated successfully.');
    }

    public function updateProfilePic (Request $request, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) return back()->with('error', 'User not found.');

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);
        $ppicture = $request->file('profile_picture');
        $ppicturename= 'user' . $id . '.' . $ppicture->getClientOriginalExtension();
        $destpath = public_path('images/profile');
        $ppicture->move($destpath,$ppicturename);
        
        $user->icon = 'images/profile/' . $ppicturename;
        $user->save();
        return back()->with('success', 'Profile picture updated successfully.');
    }

    // Make a user moderator
    public function makeModerator(Request $request, $id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id);

            $this->authorize('makeModerator', $user);

            // Set the user as a moderator
            $user->is_moderator = true;
            $user->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'User has been promoted to moderator successfully.', 'role' => 'moderator'], 200);
            }

            return redirect()->back()->with('success', 'User has been promoted to moderator successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    // Remove moderator permissions
    public function removeModerator(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $this->authorize('removeModerator', $user);

            $user->is_moderator = false;
            $user->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'User permissions as moderator have been removed successfully.', 'role' => 'user'], 200);
            }

            return redirect()->back()->with('success', 'User permissions as moderator have been removed successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    // Ban a user
    public function banUser(Request $request, $id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id);
            
            $this->authorize('ban', $user);

            // Set the user as banned
            $user->is_banned = true;
            $user->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'User has been banned successfully.', 'role' => 'banned'], 200);
            }
    
            return redirect()->back()->with('success', 'User has been banned successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    // Revoke ban
    public function revokeBan(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $this->authorize('revokeBan', $user);

            $user->is_banned = false;
            $user->save();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'User banning has been revoked successfully.', 'role' => 'user'], 200);
            }

            return redirect()->back()->with('success', 'User banning has been revoked successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    // Delete a user
    public function delete($id = null)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to perform this action');
        }
    
        $user = $id ? User::findOrFail($id) : Auth::user();
    
        if (!$user) {
            return redirect()->back()->with('error', 'User was not found in our system');
        }
    
        if (Auth::id() === $user->id) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
    
            $user->delete();
    
            return redirect('login')->with('success', 'Your account has been deleted from the system');
        }
    
        // Handle deletion of other users
        $user->delete();
        return redirect()->back()->with('success', 'User has been deleted from the system');
    }    

    public function getAll(Request $request)
    {
        $page = $request->query('page', 1);
        $perpage = 10;
        $offset = ($page - 1) * $perpage;
        $search = $request->query('search');
        if ($search) {     
            // Exact Search
            $users = User::whereRaw('LOWER(tagname) LIKE ?', ['%' . strtolower($search) . '%'])->orderBy('tagname', 'asc')->skip($offset)->take($perpage)->get();
        }
        else {
            $users = User::orderBy('tagname', 'asc')->skip($offset)->take($perpage)->get(); 
        }
        
        $html = '';
        //foreach ($users as $key => $user) {
            $html .= view('partials.manage-userp', compact('users'))->render();
        //}
        
        return response()->json($html, 200);
    }

    public function getfooter(Request $request){
        $page = $request->query('page', 1);

        $search = $request->query('search');
        if ($search) {     
            // Exact Search
            $users = User::whereRaw('LOWER(tagname) LIKE ?', ['%' . strtolower($search) . '%'])->orderBy('tagname', 'asc')->get();
        }
        else {
            $users = User::orderBy('tagname', 'asc')->get(); 
        }
        $pagination= $this->renderPagination($page, ceil($users->count()/10));
        $html1=view('partials.paginatioradmin', ['pagination' => $pagination , 'currentPage' => $page])->render();
        $html2=view('partials.pag', ['pages' => ceil($users->count()/10) , 'currentpage' => $page])->render();
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

    // Get admin actions UI
    function getAdminActions(Request $request, $id) {
        try {
            //$this->authorize('showAdmin');
            $user = User::findOrFail($id);

            $html = view('partials.admin-actions', compact('user'))->render();
            return response()->json(['html' => $html]);
        } catch (AuthorizationException $e) {
            return redirect('home')->with('error', 'You are not authorized to perform this action.');;
        }
    }

    public function showFollowed() {
        $user = User::find(Auth::id());
        $questions = $user->followedQuestions->take(10);

        return view('pages.questions-followed', compact('questions'));
    }
    public function showmorefollowed(Request $request) {
        $user = User::find(Auth::id());
        $page = $request->input('page');
        $perpage=10;
        $offset = ($page -1) * $perpage;
        $questions = $user->followedQuestions->skip($offset)->take($perpage);
        $questionshtml = $questions->map(function ($question) {
            // Retorna o HTML de cada resposta com a view 'partials.post-item' e passando 'answer' como 'post'
            return view('partials.post-item', ['post' => $question, 'type' => 'question'])->render();
        });
        return response()->json($questionshtml);
    }
}

