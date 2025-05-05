<?php

namespace App\Policies;

use App\Models\CommentPost;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class CommentPostPolicy
{
    public function create(User $user): bool
    {
        return $user !== null;
    }
    public function like(User $user, CommentPost $commentPost)
    {
        return $user !== null;
    } 
    
    /**
     * Determine whether the user can update the model.
     */
    public function updateComment(User $user, CommentPost $commentPost): bool
    {
        return Auth::check() && $user->id === $commentPost->user_id;
    }

    /**
     * Determine whether the user can delete an comment
     */
    public function deletecomment(User $user, CommentPost $commentPost): bool
    {
        return $user !== null && ($user->id === $commentPost->user_id || $user->is_admin || $user->is_moderator);
    }

    /**
     * Determine whether the user can report a comment
     */
    public function reportcomment(User $user, CommentPost $comment) 
    {
        return $user !== null && $comment->user_id !== $user->id;
    }
}