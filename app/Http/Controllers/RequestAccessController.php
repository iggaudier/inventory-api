<?php

namespace App\Http\Controllers;

use App\Mail\RequestAccessMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RequestAccessController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Mail::to(config('mail.request_access_recipient'))
            ->send(new RequestAccessMail(
                senderEmail: $validated['email'],
                messageBody: $validated['message'],
            ));

        return response()->json([
            'success' => true,
        ]);
    }
}