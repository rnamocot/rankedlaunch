<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ChatMessage;
use App\Services\ChatModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Moderate the anonymous chat: delete messages, mute people, clear everything. */
class ChatController extends Controller
{
    public function index()
    {
        return view('admin.chat', [
            'messages' => ChatMessage::latest('id')->paginate(60),
            'mutes' => DB::table('chat_mutes')->where('until', '>', now())->latest('id')->limit(50)->get(),
            'today' => ChatMessage::where('created_at', '>=', today())->count(),
            'online' => app(\App\Http\Controllers\ChatController::class)->onlineCount(),
        ]);
    }

    public function destroy(ChatMessage $message)
    {
        $message->delete();

        return back()->with('status', 'Message deleted.');
    }

    /** Mute the author of a message for 7 days (by browser id and IP hash) and remove their recent messages. */
    public function mute(Request $request, ChatMessage $message, ChatModerator $moderator)
    {
        $moderator->mute($message->chat_id, $message->ip_hash, now()->addDays(7), 'Muted by admin');
        $removed = ChatMessage::where('chat_id', $message->chat_id)->where('created_at', '>=', now()->subDay())->delete();
        ActivityLog::record('moderation', "Muted chat user “{$message->name}” for 7 days and removed {$removed} messages", user: $request->user());

        return back()->with('status', "“{$message->name}” muted for 7 days. {$removed} recent messages removed.");
    }

    /** Ban the author permanently (browser id + IP hash) and remove all their messages. */
    public function ban(Request $request, ChatMessage $message, ChatModerator $moderator)
    {
        $moderator->ban($message->chat_id, $message->ip_hash);
        $removed = ChatMessage::where('chat_id', $message->chat_id)->orWhere('ip_hash', $message->ip_hash)->delete();
        ActivityLog::record('moderation', "Banned chat user “{$message->name}” and removed {$removed} messages", user: $request->user());

        return back()->with('status', "“{$message->name}” banned from chat. {$removed} messages removed.");
    }

    public function unmute(int $mute)
    {
        DB::table('chat_mutes')->where('id', $mute)->update(['until' => now()]);

        return back()->with('status', 'Unmuted.');
    }

    public function clear(Request $request)
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm.']);
        $count = ChatMessage::count();
        ChatMessage::query()->delete();
        ActivityLog::record('moderation', "Cleared the chat ({$count} messages)", user: $request->user());

        return back()->with('status', 'Chat cleared.');
    }
}
