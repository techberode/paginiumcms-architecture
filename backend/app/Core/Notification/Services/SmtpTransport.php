<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Services;

/**
 * Lightweight SMTP client (Iteration 6). Supports plain, TLS, and AUTH LOGIN.
 */
final class SmtpTransport
{
    public function __construct(
        private string $host,
        private int $port = 587,
        private string $encryption = 'tls',
        private string $username = '',
        private string $password = ''
    ) {
    }

    public function send(string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): bool
    {
        if ($this->host === '') {
            return false;
        }

        $remote = $this->encryption === 'ssl'
            ? 'ssl://' . $this->host . ':' . $this->port
            : $this->host . ':' . $this->port;

        $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            return false;
        }

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO paginiumcms.local');
            $this->expect($socket, [250]);

            if ($this->encryption === 'tls') {
                $this->command($socket, 'STARTTLS');
                $this->expect($socket, [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return false;
                }
                $this->command($socket, 'EHLO paginiumcms.local');
                $this->expect($socket, [250]);
            }

            if ($this->username !== '' && $this->password !== '') {
                $this->command($socket, 'AUTH LOGIN');
                $this->expect($socket, [334]);
                $this->command($socket, base64_encode($this->username));
                $this->expect($socket, [334]);
                $this->command($socket, base64_encode($this->password));
                $this->expect($socket, [235]);
            }

            $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>');
            $this->expect($socket, [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>');
            $this->expect($socket, [250, 251]);
            $this->command($socket, 'DATA');
            $this->expect($socket, [354]);

            $headers = [
                'From: ' . $this->formatAddress($fromEmail, $fromName),
                'To: <' . $to . '>',
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
            fwrite($socket, $message . "\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT');

            return true;
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param resource $socket
     * @param list<int> $codes
 * @param array<int|string, mixed> $codes
 */private function expect($socket, array $codes): void
    {
        $response = $this->read($socket);
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim($response));
        }
    }

    /** @param resource $socket */
    private function command($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /** @param resource $socket */
    private function read($socket): string
    {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return $data;
    }

    private function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
