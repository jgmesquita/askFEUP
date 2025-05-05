<?php

namespace App\Http\Controllers;

use App\Models\AnswerPost;
use App\Models\PostReport;
use App\Models\QuestionPost;
use App\Jobs\ProcessQuestionNotification;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnswerPostController extends Controller
{
    public function store(Request $request, $id)
    {   
        try {
            $question = QuestionPost::findOrFail($id);
            $this->authorize('createAnswer', $question);

            $request->validate([
                'newAnswer' => 'required|string'
            ]);

            $answer = AnswerPost::create([
                'text' => $request->newAnswer,
                'date' => Carbon::now(),
                'is_edited' => false,
                'user_id' => Auth::id(),
                'question_id' => $id,
                'nr_likes' => 0
            ]);

            // Send a notification to the question followers
            ProcessQuestionNotification::dispatch([
                'user_trigger_id' => Auth::user()->id,
                'question_id' => $id, 
                'answer' => $answer,
                'comment_id' => null,
                'type' => 'question_answered',  
                'auth_id' => Auth::id(),
            ]);

            return redirect()->route('questions.show', ['id' => $id])->with('success', 'Answer created successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $answer = AnswerPost::findOrFail($id);
            
            $this->authorize('updateAnswer', [$answer, 'answer']);

            // Validate the input
            $request->validate([
                'text' => 'required|string|max:5000',
            ]);

            // Check if answer was edited
            $isUpdated = $answer->text !== $request->text;
            if (!$isUpdated) return redirect()->back()->with('info', 'No changes detected in answer.');

            // Update the answer with new data
            $answer->update([
                'text' => $request->input('text'),
                'is_edited' => true,
            ]);

            return redirect()->back()->with('success', 'Answer updated successfully.');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function delete($id)
    {
        try {
            $answer = AnswerPost::findOrFail($id);
            $this->authorize('deleteanswer', $answer);

            $currentUserId = Auth::id();
            DB::statement("SET app.current_user_id = '$currentUserId'");

            $answer->delete();
            return redirect()->back()->with('success', 'Answer deleted successfully');
        } catch (AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to perform this action.');
        }
    }

    public function likeAnswer($id) 
    {
        try {
            $answer = AnswerPost::findOrFail($id);
            
            $this->authorize('like', $answer);

            $user = auth()->user();
            $answer->toggleLike($user);

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

    public function markAnswerAsCorrect($answerId)
    {
        $answer = AnswerPost::findOrFail($answerId);

        if ($answer->is_correct) return redirect()->back()->with('info', 'Answer was already marked as correct.');

        $answer->is_correct = true;
        $answer->save();

        return redirect()->back()->with('success', 'Answer marked as correct!');
    }

    public function revokeAnswerAsCorrect($answerId)
    {
        $answer = AnswerPost::findOrFail($answerId);

        if (!$answer->is_correct) return redirect()->back()->with('info', 'Answer was not marked as correct.');

        $answer->is_correct = false;
        $answer->save();

        return redirect()->back()->with('success', 'Answer is no longer marked as correct!');
    }

    public function report(Request $request, $id) 
    {
        $question = AnswerPost::findOrFail($id);

        $validatedData = $request->validate([
            'reason_id' => 'required|string|max:1000',
        ]);
    
        $question = PostReport::create([
            'user_id' => Auth::id(),
            'post_type' => 'answer',
            'post_id' => $question->id, 
            'reason_id' => $validatedData['reason_id'],
            'date' => Carbon::now(), 
        ]);

        return redirect()->back()->with('success', 'Thank you! Your report will be reviewed shortly.');
    }
    public function getCom($answerID, Request $request){
        $answer = AnswerPost::findOrFail($answerID);
        $page= $request->input('page');
        $coms=$answer->getCom($page);
        $comshtml = view('partials.load-more-comms', ['answer' => $answer, 'page' => $page, 'comments' => $coms])->render();
        return response()->json($comshtml);

    }
    public function loadComs($answerID){
        $answer = AnswerPost::findOrFail($answerID);
        $comshtml= view('partials.load-comments', ['answer' => $answer, 'page' => 1])->render();
        return response()->json($comshtml);
    }
}