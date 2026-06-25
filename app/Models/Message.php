<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_size',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    public function hasAttachment(): bool
    {
        return ! is_null($this->attachment_path);
    }

    public function formattedSize(): string
    {
        if (! $this->attachment_size) {
            return '';
        }

        $kb = $this->attachment_size / 1024;

        if ($kb < 1024) {
            return round($kb, 1) . ' KB';
        }

        return round($kb / 1024, 1) . ' MB';
    }
}
