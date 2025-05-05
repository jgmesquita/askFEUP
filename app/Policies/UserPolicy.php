<?php

namespace App\Policies;

use App\Models\User;

use Illuminate\Http\Request;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can view his profile
     */
    public function show(User $authUser): bool
    {
        if ($authUser !== null ) return true;
    }

    /**
     * Determine whether the user can view a profile
     */
    public function showUser(User $authUser, User $targetUser): bool
    {
        // Allow users to view their profile
        if ($authUser !== null) {
            return true;
        }
        return false;
    }

    /**
     * Determine if the current user can update the target user profile.
     */
    public function update(User $currentUser, User $targetUser)
    {
        // Only admins or the user themselves can update the profile
        return $currentUser->is_admin || $currentUser->id === $targetUser->id;
    }

    /**
     * Determine if the current user can delete the target user.
     */
    public function delete(User $currentUser, User $targetUser)
    {
        // Only admins can delete users and cannot delete themselves
        return $currentUser->is_admin && $currentUser->id !== $targetUser->id && !$targetUser->is_admin;
    }

    /**
     * Determine if the current user can ban a user
     */
    public function ban(User $authUser, User $targetUser) : bool
    {
        // Only admins or moderators can ban users, but not themselves
        return ($authUser->is_admin || $authUser->is_moderator) 
            && !$targetUser->is_banned && !$targetUser->is_admin && !$targetUser->is_moderator && $authUser->id !== $targetUser->id;
    }  

    /**
     * Determine if the current user can revoke the ban of a user
     */
    public function revokeBan(User $authUser, User $targetUser) : bool
    {
        // Only admins or moderators can revoke bans, but not on themselves
        return ($authUser->is_admin || $authUser->is_moderator) && $targetUser->is_banned && !$targetUser->is_admin && !$targetUser->is_moderator && $authUser->id !== $targetUser->id;
    } 
    
    /**
     * Determine if the current user can make a user moderator
     */
    public function makeModerator(User $authUser, User $targetUser) : bool
    {
        // Only admins can make a user a moderator, but not themselves
        return ($authUser->is_admin) && !$targetUser->is_moderator && !$targetUser->is_admin && !$targetUser->is_banned && $authUser->id !== $targetUser->id;
    } 
        
    /**
     * Determine if the current user can make a user moderator
     */
    public function removeModerator(User $authUser, User $targetUser) :bool
    {
        // Only admins can remove a user from moderator, but not themselves
        return ($authUser->is_admin) && $targetUser->is_moderator && !$targetUser->is_admin && !$targetUser->is_banned && $authUser->id !== $targetUser->id;
    } 

    /**
     * Determine if the current user is admin
     */
    public function showAdmin(User $authUser) : bool {
        return $authUser !== null && $authUser->is_admin;
    }

    /**
     * Determine if the current user is admin
     */
    public function showMod(User $authUser) : bool {
        return $authUser !== null && ($authUser->is_moderator);
    }

    /**
     * Determine if the user can view the profile section.
     */
    public function viewProfileSection(User $authUser, Request $request) : bool
    {
        return $request->expectsJson();
    }

    /**
     * Determine if the user can see the followed question.
     */
    public function showFollowedQuestions(User $authUser) : bool
    {
        return $authUser !== null ;
    }
}
