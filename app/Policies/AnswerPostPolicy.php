<?php

namespace App\Policies;

use App\Models\AnswerPost;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class AnswerPostPolicy
{
    /**
     * Determine whether the user can create an answer.
     */
    public function create(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can like an answer.
     */
    public function like(User $user, AnswerPost $answerPost)
    {
        return $user !== null;
    } 
    
    /**
     * Determine whether the user can update the model.
     */
    public function updateAnswer(User $user, AnswerPost $answerPost): bool
    {
        return Auth::check() && $user->id === $answerPost->user_id;
    }

    /**
     * Determine whether the user can delete an answer
     */
    public function deleteanswer(User $user, AnswerPost $answerPost): bool
    {
        return $user !== null && ($user->id === $answerPost->user_id || $user->is_admin || $user->is_moderator);
    }

    /**
     * Determine whether the user can report an answer
     */
    public function reportanswer(User $user, AnswerPost $answer) 
    {
        return $user !== null && $answer->user_id !== $user->id;
    }

    public function markCorrect(User $user, AnswerPost $answer) 
    {
        return $user !== null && $user->id === $answer->question->user_id && !$answer->is_correct;
    }

    public function revokeCorrect(User $user, AnswerPost $answer) 
    {
        return $user !== null && $user->id === $answer->question->user_id && $answer->is_correct;
    }
}