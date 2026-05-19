<?php
/**
 * submit.php — Handler de formulaire RDV
 * Clinique Achifaa Oujda · 2026
 *
 * Reçoit le POST du formulaire de l'index.html,
 * valide les données et envoie un email via SMTP Hostinger.
 *
 * Anti-spam : honeypot _honey + validation stricte.
 */

declare(strict_types=1);
require_once __DIR__ . '/php/smtp.php';

/* ========== 1. Chargement .env ========== */
function loadEnv(string $path): array
{
    $env = [];
    if (!is_file($path)) {
        return $env;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
    return $env;
}
$env = loadEnv(__DIR__ . '/.env');

$siteUrl    = $env['SITE_URL']        ?? 'https://cliniqueachifaaoujda.com';
$mailTo     = $env['MAIL_TO']         ?? 'info@cliniqueachifaaoujda.com';
$mailFrom   = $env['SMTP_USER']       ?? 'info@cliniqueachifaaoujda.com';
$mailName   = $env['MAIL_FROM_NAME']  ?? 'Clinique Achifaa Oujda';
$mailSubj   = $env['MAIL_SUBJECT']    ?? 'Nouvelle demande de RDV — Clinique Achifaa Oujda';
$smtpHost   = $env['SMTP_HOST']       ?? 'smtp.hostinger.com';
$smtpPort   = (int)($env['SMTP_PORT'] ?? 465);
$smtpUser   = $env['SMTP_USER']       ?? '';
$smtpPass   = $env['SMTP_PASSWORD']   ?? '';

/* ========== 2. Vérification méthode ========== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

/* ========== 3. Honeypot anti-spam ========== */
if (!empty($_POST['_honey'])) {
    // Bot détecté → on simule un succès silencieux
    header('Location: ' . $siteUrl . '/?rdv=ok#appointment');
    exit;
}

/* ========== 4. Validation ========== */
function clean(string $s, int $max = 500): string
{
    $s = trim($s);
    if (mb_strlen($s) > $max) {
        $s = mb_substr($s, 0, $max);
    }
    return $s;
}

$errors = [];

$nom      = clean((string)($_POST['Nom'] ?? ''),       80);
$tel      = clean((string)($_POST['Téléphone'] ?? ''), 20);
$email    = clean((string)($_POST['Email'] ?? ''),     120);
$spec     = clean((string)($_POST['Spécialité'] ?? ''), 80);
$date     = clean((string)($_POST['Date'] ?? ''),      10);
$creneau  = clean((string)($_POST['Créneau'] ?? ''),   40);
$message  = clean((string)($_POST['Message'] ?? ''),   1500);
$consent  = $_POST['Consentement'] ?? '';

if (mb_strlen($nom) < 2)                                                  $errors[] = 'Nom requis (min 2 caractères).';
if (!preg_match('/^[+0-9 ()\-]{8,20}$/', $tel))                           $errors[] = 'Numéro de téléphone invalide.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))          $errors[] = 'Email invalide.';
if ($spec === '')                                                         $errors[] = 'Spécialité requise.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))                          $errors[] = 'Date invalide.';
if (empty($consent))                                                      $errors[] = 'Consentement requis.';

if ($errors) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit("Formulaire invalide :\n- " . implode("\n- ", $errors));
}

/* ========== 5. Construction du corps HTML ========== */
function escape(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$rows = [
    'Nom complet'   => $nom,
    'Téléphone'     => $tel,
    'Email'         => $email !== '' ? $email : '—',
    'Spécialité'    => $spec,
    'Date souhaitée'=> $date,
    'Créneau'       => $creneau !== '' ? $creneau : '— Indifférent —',
    'Message'       => $message !== '' ? nl2br(escape($message)) : '—',
];

$html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="font-family:Arial,Helvetica,sans-serif;background:#F9FAFB;padding:30px 0;margin:0">'
      . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px -6px rgba(15,23,42,0.15)">'
      . '<tr><td style="background:linear-gradient(135deg,#1E3A8A,#2563EB);padding:24px 28px;color:white">'
      . '<h2 style="margin:0;font-size:18px;font-weight:700">Nouvelle demande de rendez-vous</h2>'
      . '<p style="margin:6px 0 0;font-size:13px;opacity:0.85">Reçue le ' . date('d/m/Y à H:i') . ' depuis cliniqueachifaaoujda.com</p>'
      . '</td></tr>'
      . '<tr><td style="padding:28px"><table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;font-size:14px;color:#374151">';

foreach ($rows as $label => $value) {
    $val = ($label === 'Message') ? $value : escape($value);
    $html .= '<tr>'
           . '<th style="text-align:left;padding:12px 14px;background:#F3F4F6;font-weight:700;color:#111827;width:170px;border-bottom:1px solid #E5E7EB">' . escape($label) . '</th>'
           . '<td style="padding:12px 14px;border-bottom:1px solid #E5E7EB">' . $val . '</td>'
           . '</tr>';
}

$html .= '</table></td></tr>'
       . '<tr><td style="padding:18px 28px;background:#F9FAFB;border-top:1px solid #E5E7EB;font-size:12px;color:#6B7280">'
       . 'Confirmez le rendez-vous sous 24h en appelant le client ou via WhatsApp.<br>'
       . 'IP : ' . escape($_SERVER['REMOTE_ADDR'] ?? '?') . ' · UA : ' . escape(mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? '?'), 0, 120))
       . '</td></tr></table></body></html>';

/* ========== 6. Envoi via SMTP ========== */
try {
    if ($smtpUser === '' || $smtpPass === '') {
        throw new Exception('SMTP credentials manquants dans .env (SMTP_USER / SMTP_PASSWORD).');
    }
    $smtp = new SimpleSMTP($smtpHost, $smtpPort, $smtpUser, $smtpPass);
    $smtp->send(
        $mailFrom,
        $mailName,
        $mailTo,
        $mailSubj . ' — ' . $nom,
        $html,
        $email !== '' ? $email : ''
    );
} catch (Throwable $e) {
    error_log('[submit.php] Erreur envoi : ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit("Une erreur est survenue lors de l'envoi du formulaire.\n"
       . "Merci de nous appeler directement au +212 5 36 70 00 00 ou via WhatsApp.\n\n"
       . "Détail technique : " . $e->getMessage());
}

/* ========== 7. Redirection succès ========== */
header('Location: ' . $siteUrl . '/?rdv=ok#appointment');
exit;
