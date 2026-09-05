<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;color:#161921;max-width:560px;margin:0 auto;padding:24px">
    <h2 style="margin-top:0">Nuevo mensaje de contacto</h2>
    <p><strong>Nombre:</strong> {{ $msg->name }}</p>
    <p><strong>Correo:</strong> {{ $msg->email }}</p>
    @if($msg->subject)<p><strong>Asunto:</strong> {{ $msg->subject }}</p>@endif
    <p><strong>Mensaje:</strong></p>
    <p style="white-space:pre-wrap;background:#f7f5ef;padding:16px;border-radius:8px">{{ $msg->message }}</p>
    <p style="color:#888;font-size:12px;margin-top:24px">IP: {{ $msg->ip }} · {{ $msg->created_at }}</p>
</body>
</html>
