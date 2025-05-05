<?php

namespace App\Policies;

use App\Models\QuestionPost;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class QuestionPostPolicy
{
    /**
     * Determine whether the user can view all
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view a specific question.
     */
    public function view(User $user, QuestionPost $questionPost): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can create questions.
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function updateQuestion(User $user, QuestionPost $questionPost, string $type): bool
    {
        return $user !== null && ($user->id === $questionPost->user_id && $type === 'question');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deletequestion(User $user, QuestionPost $questionPost): bool
    {
        return $user !== null && ($user->id === $questionPost->user_id || $user->is_admin || $user->is_moderator);
    }

    /**
     * Determine whether the user can like a question
     */
    public function like(User $user, QuestionPost $questionPost) : bool
    {
        return $user !== null;
    } 
    
    /**
     * Determine whether the user can create an answer
     */
    public function createAnswer(User $user, QuestionPost $question): bool
    {
        return $user !== null && $user->id !== $question->user_id;
    }

    /**
     * Determine whether the user can report a question
     */
    public function reportquestion(User $user, QuestionPost $question) 
    {
        return $user !== null && $question->user_id !== $user->id;
    }

    /**
     * Determine whether the user can edit the tag of a question
     */
    public function editTag(User $user, QuestionPost $question )
    {
        return $user !== null && $question->user_id != $user->id && ($user->is_moderator || $user->is_admin);
    }

    /**
     * Determine if the user can follow a question
     */
    public function followQuestion(User $user, QuestionPost $question) : bool 
    {
        return $user !== null 
            && $question->user_id != $user->id 
            && !$user->followedQuestions()->where('question_id', $question->id)->exists();
    }

    /**
     * Determine if the user can unfollow a question
     */
    public function unfollowQuestion(User $user, QuestionPost $question) : bool 
    {
        return $user !== null 
            && $question->user_id != $user->id 
            && $user->followedQuestions()->where('question_id', $question->id)->exists();
    }
}
