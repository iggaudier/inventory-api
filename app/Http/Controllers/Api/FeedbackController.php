<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Mail\FeedbackMail;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        Mail::to(config('mail.feedback_recipient'))
            ->send(new FeedbackMail(
                feedbackType: $validated['type'],
                messageBody: $validated['message'],
                senderName: $user->name,
                senderEmail: $user->email,
                organizationName: $user->organization?->name,
            ));

        return response()->json([
            'success' => true,
            'message' => 'Thank you. Your feedback has been sent.',
        ]);
    }
}
