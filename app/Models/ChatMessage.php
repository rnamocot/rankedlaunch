<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['name', 'body', 'chat_id', 'ip_hash'];

    protected $hidden = ['chat_id', 'ip_hash'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** What the browser gets: never the chat id or IP hash. */
    public function toPublic(?string $viewerChatId = null): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'body' => $this->body,
            'time' => $this->created_at->toIso8601String(),
            'mine' => $viewerChatId !== null && hash_equals($this->chat_id, $viewerChatId),
        ];
    }
}
