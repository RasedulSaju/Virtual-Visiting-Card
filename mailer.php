<?php
declare(strict_types=1);

/**
 * mailer.php — SMTP Email Dispatcher (PHPMailer wrapper)
 * Configure SMTP in Admin → Settings → SMTP
 */

require_once __DIR__ . '/helpers.php';

class Mailer
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = [
            'host'       => getSetting('smtp_host'),
            'port'       => getSetting('smtp_port',       '587'),
            'username'   => getSetting('smtp_username'),
            'password'   => getSetting('smtp_password'),
            'encryption' => getSetting('smtp_encryption', 'tls'),
            'from_email' => getSetting('smtp_from_email'),
            'from_name'  => getSetting('smtp_from_name',  siteName()),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->cfg['host']       !== ''
            && $this->cfg['username']   !== ''
            && $this->cfg['from_email'] !== ''
            && file_exists(__DIR__ . '/vendor/autoload.php');
    }

    /**
     * Send an email.
     *
     * @return bool  true = sent, false = SMTP not configured (dev-mode fallback)
     * @throws RuntimeException on send failure with SMTP configured
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        require_once __DIR__ . '/vendor/autoload.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Server
            $mail->isSMTP();
            $mail->Host     = $this->cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->cfg['username'];
            $mail->Password = $this->cfg['password'];
            $mail->Port     = (int)$this->cfg['port'];

            // Encryption
            $enc = strtolower($this->cfg['encryption']);
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
                $mail->SMTPSecure  = '';
            }

            // Sender & recipient
            $mail->setFrom($this->cfg['from_email'], $this->cfg['from_name']);
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            // Content
            $mail->isHTML(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Subject  = $subject;
            $mail->Body     = $htmlBody;
            $mail->AltBody  = strip_tags(
                str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody)
            );

            $mail->send();
            return true;

        } catch (PHPMailer\PHPMailer\Exception $e) {
            error_log('[Mailer] PHPMailer error: ' . $e->getMessage());
            throw new RuntimeException('Email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Build a branded transactional email HTML body.
     */
    public static function buildHtml(
        string $heading,
        string $body,
        string $ctaUrl   = '',
        string $ctaLabel = ''
    ): string {
        $cta = '';
        if ($ctaUrl && $ctaLabel) {
            $cta = '<p style="text-align:center;margin:28px 0;">
                <a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '"
                   style="background:#4f46e5;color:#fff;text-decoration:none;
                          padding:12px 28px;border-radius:8px;font-weight:600;
                          display:inline-block;font-family:-apple-system,sans-serif;">
                   ' . htmlspecialchars($ctaLabel, ENT_QUOTES) . '
                </a></p>
                <p style="text-align:center;font-size:.8rem;color:#9ca3af;">
                    Or copy this link: <a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '"
                    style="color:#4f46e5;">' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '</a>
                </p>';
        }

        $siteName = htmlspecialchars(siteName(), ENT_QUOTES);

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:24px 16px;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;">
<div style="max-width:520px;margin:0 auto;">
  <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:12px 12px 0 0;padding:28px 32px;">
    <p style="margin:0;font-size:1.2rem;font-weight:700;color:#fff;">' . $siteName . '</p>
  </div>
  <div style="background:#fff;border-radius:0 0 12px 12px;padding:32px;box-shadow:0 4px 24px rgba(15,23,42,.08);">
    <h2 style="margin:0 0 16px;color:#0f172a;font-size:1.3rem;">' . $heading . '</h2>
    <div style="color:#374151;line-height:1.7;">' . $body . '</div>
    ' . $cta . '
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0 16px;">
    <p style="margin:0;color:#9ca3af;font-size:.78rem;">
        This email was sent by ' . $siteName . '.
        If you did not request this, you can safely ignore it.
    </p>
  </div>
</div>
</body></html>';
    }
}
