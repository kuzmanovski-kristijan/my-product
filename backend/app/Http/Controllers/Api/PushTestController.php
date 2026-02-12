<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushToUserJob;
use Illuminate\Http\Request;

class PushTestController extends Controller
{
    public function send(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'data' => ['array'],
        ]);

        SendPushToUserJob::dispatch(
            $user->id,
            $validated['title'],
            $validated['body'],
            $validated['data'] ?? []
        );

        return response()->json(['message' => 'Push queued']);
    }
}
