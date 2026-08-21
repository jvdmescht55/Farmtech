<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\SupportRequestMailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function index()
    {
        return view('storefront.support');
    }

    /** Real submission — sent (not stored) to config('farmtech.admin_notification_email'), same as other admin alert mail. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Mail::to(config('farmtech.admin_notification_email'))->send(new SupportRequestMailable(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            topic: $validated['topic'],
            message: $validated['message'],
        ));

        return back()->with('status', "Thanks — we've received your message and will reply to {$validated['email']} shortly.");
    }
}
