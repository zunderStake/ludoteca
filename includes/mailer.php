<?php
/**
 * Envío de notificaciones por email. Usa mail() de PHP (Hestia ya deja cada dominio
 * listo para enviar correo, sin cuentas ni tokens externos) — no depende de ningún
 * servicio de terceros. Un fallo de envío nunca debe romper la acción que lo originó,
 * así que ludoteca_send_mail() siempre devuelve bool en vez de lanzar.
 */

function ludoteca_mail_from(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    $host = preg_replace('/:\d+$/', '', $host) ?? $host; // sin puerto
    return 'Ludoteca <noreply@' . $host . '>';
}

function ludoteca_mail_headers(): string
{
    return "From: " . ludoteca_mail_from() . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
}

/**
 * Registro de cada intento de envío (a quién, con qué asunto, si mail() dijo que sí
 * y qué error de PHP hubo si dijo que no) en includes/mail.log — la carpeta includes/
 * ya está bloqueada por completo por su .htaccess, así que el fichero no es
 * descargable, pero se puede abrir por FTP/gestor de archivos sin entrar por SSH.
 * "mail() devolvió true" solo significa que el servidor lo aceptó para entregarlo,
 * no que haya llegado — para eso hay que mirar el log de correo del propio Hestia.
 */
function ludoteca_log_mail(string $to, string $subject, bool $ok, ?string $phpError): void
{
    $line = sprintf(
        "[%s] to=%s ok=%s subject=%s%s\n",
        date('Y-m-d H:i:s'),
        $to,
        $ok ? 'true' : 'false',
        $subject,
        $phpError ? ' error="' . $phpError . '"' : ''
    );
    try {
        @file_put_contents(__DIR__ . '/mail.log', $line, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // Ni el propio registro debe romper nada.
    }
}

function ludoteca_send_mail(string $to, string $subject, string $body): bool
{
    try {
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        error_clear_last();
        $ok = @mail($to, $encodedSubject, $body, ludoteca_mail_headers());
        $error = $ok ? null : (error_get_last()['message'] ?? 'mail() devolvió false');
        ludoteca_log_mail($to, $subject, $ok, $error);
        return $ok;
    } catch (Throwable $e) {
        ludoteca_log_mail($to, $subject, false, $e->getMessage());
        return false;
    }
}

/** URL absoluta a index.php a partir del script actual (pensado para llamarse desde api/). */
function ludoteca_app_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $root = str_replace('\\', '/', dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))));
    $root = $root === '/' ? '' : $root;
    return $scheme . '://' . $host . $root . '/index.php';
}

function ludoteca_notify_want_to_play(string $to, string $targetNombre, string $requesterNombre, string $juegoNombre): bool
{
    $subject = 'Ludoteca — te han propuesto jugar a ' . $juegoNombre;
    $body = "Hola {$targetNombre},\n\n"
        . "{$requesterNombre} te ha propuesto jugar a \"{$juegoNombre}\".\n\n"
        . "Entra en Ludoteca para aceptar o descartar la propuesta:\n" . ludoteca_app_url() . "\n";
    return ludoteca_send_mail($to, $subject, $body);
}
