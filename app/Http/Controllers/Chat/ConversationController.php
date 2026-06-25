<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'       => 'required|in:private,group',
            'name'       => 'nullable|string|max:255|required_if:type,group',
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id|different:' . Auth::id(),
        ]);

        $userIds = collect($validated['user_ids'])->unique()->values();

        if ($validated['type'] === 'private') {
            $otherUserId = $userIds->first();

            $existing = Conversation::where('type', 'private')
                ->whereHas('participants', fn ($q) => $q->where('users.id', Auth::id()))
                ->whereHas('participants', fn ($q) => $q->where('users.id', $otherUserId))
                ->whereDoesntHave('participants', fn ($q) => $q->whereNotIn('users.id', [Auth::id(), $otherUserId]))
                ->first();

            if ($existing) {
                return response()->json(['conversation_id' => $existing->id]);
            }
        }

        $conversation = DB::transaction(function () use ($validated, $userIds) {
            $conversation = Conversation::create([
                'type'       => $validated['type'],
                'name'       => $validated['name'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $allIds = $userIds->push(Auth::id())->unique()->values();

            $conversation->participantRecords()->createMany(
                $allIds->map(fn ($id) => [
                    'user_id'   => $id,
                    'joined_at' => now(),
                ])->all()
            );

            return $conversation;
        });

        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $users = User::where('id', '!=', Auth::id())
            ->where('name', 'ilike', '%' . $validated['q'] . '%')
            ->select('id', 'name', 'email', 'last_seen_at')
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'is_online' => $u->isOnline(),
            ]);

        return response()->json($users);
    }
}
