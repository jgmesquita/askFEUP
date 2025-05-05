<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagPolicy
{
    /**
     * Determine whether the user can view any tags.
     */
    public function viewAny(User $user, Request $request) : bool
    {
        return $request->expectsJson();
    }

    /**
     * Determine if user can follow a tag.
     */
    public function follow(User $user, Tag $tag) : bool
    {
        return $user !== null && !$user->tags()->where('id', $tag->id)->exists();
    }

    /**
     * Determine if user can follow a tag.
     */
    public function unfollow(User $user, Tag $tag) : bool
    {
        return $user !== null && $user->tags()->where('id', $tag->id)->exists();
    }
}