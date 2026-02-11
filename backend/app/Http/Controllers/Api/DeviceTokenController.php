<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'platform' => ['required', 'in:fcm,apns'],
            'token' => ['required', 'string', 'max:4096'],
        ]);

        DeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $user->id, 'platform' => $validated['platform']]
        );

        return response()->json(['message' => 'Device token saved']);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Device token removed']);
    }
}
