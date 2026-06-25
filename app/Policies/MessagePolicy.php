<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function read(User $user, Message $message): bool
    {
        return $message->conversation->participantRecords()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function download(User $user, Message $message): bool
    {
        return $this->read($user, $message);
    }
}
