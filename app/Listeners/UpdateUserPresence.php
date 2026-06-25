<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\DB;

class UpdateUserPresence
{
    public function handle(Authenticated $event): void
    {
        DB::table('users')
            ->where('id', $event->user->id)
            ->update(['last_seen_at' => now()]);
    }
}
