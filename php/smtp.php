<?php
/**
 * SimpleSMTP — Client SMTP minimal sans dépendance externe.
 * Compatible PHP 7.4+ · supporte SSL (port 465) et STARTTLS (port 587).
 * Clinique Achifaa Oujda · 2026
 */

class SimpleSMTPException extends Exception {}

class SimpleSMTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $socket;
    private $timeout = 30;
    private $log = [];

    public function __construct(string $host, int $port, string $user, string $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Envoie un mail HTML UTF-8.
     *
     * @param string $fromEmail   adresse expéditeur
     * @param string $fromName    nom affiché de l'expéditeur
     * @param string $to          destinataire
     * @param string $subject     sujet (UTF-8, sera encodé Base64)
     * @param string $htmlBody    corps HTML (UTF-8)
     * @param string $replyTo     adresse pour reply (optionnel)
     */
    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody, string $replyTo = ''): void
    {
        $this->connect();
        $this->expect(220, 'greeting');

        $this->cmd('EHLO ' . $this->hostname());
        $this->expect(250, 'EHLO');

        $this->cmd('AUTH LOGIN');
        $this->expect(334, 'AUTH LOGIN');
        $this->cmd(base64_encode($this->user));
        $this->expect(334, 'AUTH user');
        $this->cmd(base64_encode($this->pass));
        $this->expect(235, 'AUTH pass');

        $this->cmd('MAIL FROM:<' . $fromEmail . '>');
        $this->expect(250, 'MAIL FROM');

        $this->cmd('RCPT TO:<' . $to . '>');
        $this->expect(250, 'RCPT TO');

        $this->cmd('DATA');
        $this->expect(354, 'DATA');

        $boundary = '=_b_' . bin2hex(random_bytes(8));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers  = "From: $encodedFromName <$fromEmail>\r\n";
        $headers .= "To: <$to>\r\n";
        if ($replyTo) {
            $headers .= "Reply-To: <$replyTo>\r\n";
        }
        $headers .= "Subject: $encodedSubject\r\n";
        $headers .= 'Date: ' . date('r') . "\r\n";
        $headers .= "Message-ID: <" . bin2hex(random_bytes(12)) . "@" . $this->hostname() . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "X-Mailer: ClinicalForm/1.0\r\n";

        // Lignes commençant par "." doivent être doublées (RFC 5321)
        $body = preg_replace('/^\./m', '..', $htmlBody);

        // Envoi du message terminé par CRLF.CRLF
        fwrite($this->socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $this->expect(250, 'message body');

        $this->cmd('QUIT');
        @fclose($this->socket);
    }

    private function connect(): void
    {
        $url = ($this->port === 465 ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            $url,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$this->socket) {
            throw new SimpleSMTPException("Connexion SMTP impossible à $url : [$errno] $errstr");
        }
        stream_set_timeout($this->socket, $this->timeout);
    }

    private function cmd(string $command): void
    {
        $this->log[] = '> ' . $command;
        fwrite($this->socket, $command . "\r\n");
    }

    private function expect(int $code, string $context): string
    {
        $response = '';
        while (($line = fgets($this->socket, 1024)) !== false) {
            $response .= $line;
            // Format de fin : XYZ<sp>... (continuation : XYZ-)
            if (strlen($line) < 4) {
                break;
            }
            if ($line[3] === ' ') {
                break;
            }
        }
        $this->log[] = '< ' . trim($response);
        $actual = (int)substr($response, 0, 3);
        if ($actual !== $code) {
            throw new SimpleSMTPException("Erreur SMTP ($context) : attendu $code, reçu : " . trim($response));
        }
        return $response;
    }

    private function hostname(): string
    {
        return $_SERVER['SERVER_NAME'] ?? 'cliniqueachifaaoujda.com';
    }

    public function getLog(): array
    {
        return $this->log;
    }
}
