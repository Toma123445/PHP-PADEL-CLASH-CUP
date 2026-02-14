<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Sends email using PHP mail() function.
 * For production, consider using PHPMailer with SMTP.
 */
function send_email(
    string $to,
    string $subject,
    string $message,
    string $from_email = null,
    string $from_name = null
): bool {
    $from_email = $from_email ?? SMTP_FROM_EMAIL;
    $from_name = $from_name ?? SMTP_FROM_NAME;

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . phpversion()
    ];

    $message_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars(APP_NAME) . '</h1>
        </div>
        <div class="content">
            ' . nl2br(htmlspecialchars($message)) . '
        </div>
        <div class="footer">
            <p>Acest email a fost trimis automat de catre ' . htmlspecialchars(APP_NAME) . '</p>
        </div>
    </div>
</body>
</html>';

    return mail($to, $subject, $message_html, implode("\r\n", $headers));
}

/**
 * Sends contact form email.
 */
function send_contact_email(array $data): bool
{
    $subject = 'Mesaj nou de contact - ' . htmlspecialchars($data['subiect'] ?? 'Fara subiect');
    $message = "Ai primit un mesaj nou de contact:\n\n";
    $message .= "Nume: " . ($data['nume'] ?? '') . "\n";
    $message .= "Email: " . ($data['email'] ?? '') . "\n";
    $message .= "Subiect: " . ($data['subiect'] ?? '') . "\n\n";
    $message .= "Mesaj:\n" . ($data['mesaj'] ?? '');

    return send_email(
        SMTP_FROM_EMAIL,
        $subject,
        $message,
        $data['email'] ?? null,
        $data['nume'] ?? null
    );
}

/**
 * Sends team registration confirmation email.
 */
function send_registration_email(string $to, string $team_name): bool
{
    $subject = 'Confirmare inscriere echipa - ' . APP_NAME;
    $message = "Buna ziua,\n\n";
    $message .= "Inscrierea echipei '" . htmlspecialchars($team_name) . "' a fost inregistrata cu succes!\n\n";
    $message .= "Echipa ta este in asteptarea validarii de catre administrator.\n";
    $message .= "Vei primi un email de confirmare odata ce echipa va fi validata.\n\n";
    $message .= "Mult succes in competitie!\n\n";
    $message .= "Echipa " . APP_NAME;

    return send_email($to, $subject, $message);
}

/**
 * Sends order/notification email.
 */
function send_order_email(string $to, string $order_details): bool
{
    $subject = 'Notificare comanda - ' . APP_NAME;
    $message = "Buna ziua,\n\n";
    $message .= "Ai primit o notificare despre comanda ta:\n\n";
    $message .= $order_details . "\n\n";
    $message .= "Echipa " . APP_NAME;

    return send_email($to, $subject, $message);
}

