<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:40'],
            'website' => ['prohibited'], // honeypot: bots fill hidden fields
        ]);

        Subscriber::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['source' => $data['source'] ?? null, 'unsubscribed_at' => null],
        );

        return back()->with('subscribed', true)->withFragment('newsletter');
    }
}
