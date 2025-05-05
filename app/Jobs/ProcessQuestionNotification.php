<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Notification;
use App\Models\User;

class ProcessQuestionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $interaction;

    public function __construct($interaction)
    {
        $this->interaction = $interaction; // Pass interaction details
    }

    public function handle()
    {
        // User creating the notification
        $authId = $this->interaction['auth_id'];

        // Find users who follow the question
        $followers = User::whereHas('followedQuestions', function ($query) {
            $query->where('question_id', $this->interaction['question_id']);
        })->get();

        // Create a notification for each follower
        foreach ($followers as $user) {
            if (
                $user->id === $authId || 
                ($this->interaction['comment_id'] !== null && 
                 $user->id === $this->interaction['answer']->user_id)
            ) 
                continue; 
            
            Notification::create([
                'user_receive_id' => $user->id,
                'user_trigger_id' => $this->interaction['user_trigger_id'],
                'type' => $this->interaction['type'], 
                'answer' => $this->interaction['answer']->id,
                'comment_id' => $this->interaction['comment_id'],
                'is_read' => false
            ]);
        }
    }
}
