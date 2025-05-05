<?php

namespace App\Http\Controllers;

use App\Models\CommentPost;
use App\Models\AnswerPost;
use App\Models\PostReport;
use App\Jobs\ProcessQuestionNotification;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommentPostController extends Controller
{
    public function store(Request $request, $id)
    {   
        try {
            $answer = AnswerPost::findOrFail($id);

            $validated = $request->validate([
                'newComment' => 'required|string'
            ]);

            $comment = CommentPost::create([
                'text' => $validated['newComment'],
                'date' => Carbon::now(),
                'is_edited' => false,
                'user_id' => Auth::id(),
                'answer_id' => $id,
                'nr_likes' => 0
            ]);

            // Send a notification to the question followers
            ProcessQuestionNotification::dispatch([
                'user_trigger_id' => Auth::user()->id,
                'question_id' => $answer->question_id,
                'answer' => $answer, 
                'comment_id' => $comment->id,
                'type' => 'question_commented',  
                'auth_id' => Auth::id(),
            ]);

            return redirect()->back()
                ->with('id', $answer->question_id)
                ->with('success', 'Comment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $comment = CommentPost::findOrFail($id);
            
            $this->authorize('updateComment', [$comment, 'comment']);

            // Validate the input
            $request->validate([
                'text' => 'required|string|max:5000',
            ]);

            // Check if comment was edited
            $isUpdated = $comment->text !== $request->text;
            if (!$isUpdated) return redirect()->back()->with('info', 'No changes detected in comment.');

            // Update the answer with new data
            $comment->update([
                'text' => $request->input('text'),
                'is_edited' => true,
            ]);

            return redirect()->back()->with('success', 'Comment updated successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function delete($id)
    {
        try {
            $comment = CommentPost::findOrFail($id);
            $this->authorize('deletecomment', $comment);

            $currentUserId = Auth::id();
            DB::statement("SET app.current_user_id = '$currentUserId'");

            $comment->delete();
            return redirect()->back()->with('success', 'Comment deleted successfully');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function likeComment($id) 
    {
        try {
            $comment = CommentPost::findOrFail($id);
            
            $this->authorize('like', $comment);

            $user = auth()->user();
            $comment->toggleLike($user);

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

    public function report(Request $request, $id) 
    {
        $question = CommentPost::findOrFail($id);

        $validatedData = $request->validate([
            'reason_id' => 'required|string|max:1000',
        ]);
    
        $question = PostReport::create([
            'user_id' => Auth::id(),
            'post_type' => 'comment',
            'post_id' => $question->id, 
            'reason_id' => $validatedData['reason_id'],
            'date' => Carbon::now(), 
        ]);

        return redirect()->back()->with('success', 'Thank you! Your report will be reviewed shortly.');
    }
}