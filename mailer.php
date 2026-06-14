<?php
declare(strict_types=1);

/**
 * mailer.php — SMTP Email Dispatcher
 *
 * Requires PHPMailer installed via Composer:
 *   composer require phpmailer/phpmailer
 *
 * Falls back to dev-mode (returns false) when:
 *   - vendor/autoload.php is not found, OR
 *   - SMTP host/credentials are not configured in Admin > Settings > SMTP
 */

require_once __DIR__ . '/helpers.php';

class Mailer
{
    /** @var array<string,string> */
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
            'from_name'  => getSetting('smtp_from_name', siteName()),
        ];
    }

    /**
     * Returns true if SMTP is fully configured and PHPMailer is available.
     */
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
     * @param  string $toEmail  Recipient address
     * @param  string $toName   Recipient display name
     * @param  string $subject  Email subject
     * @param  string $htmlBody HTML body (plain-text fallback auto-generated)
     * @return bool             true = sent, false = SMTP not configured
     * @throws RuntimeException on send failure
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

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host        = $this->cfg['host'];
        $mail->SMTPAuth    = true;
        $mail->Username    = $this->cfg['username'];
        $mail->Password    = $this->cfg['password'];
        $mail->Port        = (int)$this->cfg['port'];

        $enc = strtolower($this->cfg['encryption']);
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom($this->cfg['from_email'], $this->cfg['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Subject  = $subject;
        $mail->Body     = $htmlBody;
        $mail->AltBody  = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    }

    /**
     * Build a standard transactional email HTML wrapper.
     */
    public static function buildHtml(string $heading, string $body, string $ctaUrl = '', string $ctaLabel = ''): string
    {
        $cta = '';
        if ($ctaUrl && $ctaLabel) {
            $cta = '<p style="text-align:center;margin:32px 0;">
                <a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES) . '"
                   style="background:#4f46e5;color:#fff;text-decoration:none;
                          padding:12px 28px;border-radius:8px;font-weight:600;
                          display:inline-block;">'
                   . htmlspecialchars($ctaLabel, ENT_QUOTES) .
                '</a></p>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body
            style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;
                   background:#f8fafc;margin:0;padding:32px 16px;">
            <div style="max-width:520px;margin:0 auto;background:#fff;
                        border-radius:12px;padding:40px;box-shadow:0 2px 16px rgba(0,0,0,.08);">
                <div style="margin-bottom:24px;">
                    <span style="font-size:1.4rem;font-weight:700;color:#4f46e5;">'
                        . htmlspecialchars(siteName(), ENT_QUOTES) .
                    '</span>
                </div>
                <h2 style="color:#0f172a;margin:0 0 16px;">' . $heading . '</h2>
                <div style="color:#374151;line-height:1.7;">' . $body . '</div>
                ' . $cta . '
                <hr style="border:none;border-top:1px solid #e2e8f0;margin:32px 0 16px;">
                <p style="color:#9ca3af;font-size:.8rem;margin:0;">
                    This email was sent by ' . htmlspecialchars(siteName(), ENT_QUOTES) . '.
                    If you did not request this, please ignore it.
                </p>
            </div></body></html>';
    }
}
