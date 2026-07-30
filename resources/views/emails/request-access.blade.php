<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #33302b;">
    <p><strong>Email:</strong> {{ $senderEmail }}</p>
    <p><strong>Message:</strong></p>
    <p>{{ nl2br(e($messageBody)) }}</p>
</body>
</html>