<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #33302b;">
    <p><strong>Type:</strong> {{ ucfirst($feedbackType) }}</p>
    <p><strong>From:</strong> {{ $senderName }} ({{ $senderEmail }})</p>
    @if ($organizationName)
        <p><strong>Organization:</strong> {{ $organizationName }}</p>
    @endif
    <p><strong>Message:</strong></p>
    <p>{!! nl2br(e($messageBody)) !!}</p>
</body>
</html>
