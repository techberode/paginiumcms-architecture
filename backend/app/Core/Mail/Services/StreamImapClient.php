<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Minimal IMAP4rev1 client over a TCP/TLS socket (It.93m).
 * Does not persist messages. Fail-closed on protocol errors.
 */
final class StreamImapClient implements ImapClientInterface
{
    /** @var resource|null */
    private $stream = null;

    private int $tag = 0;

    public function connect(string $host, int $port, string $encryption, string $username, string $password): void
    {
        $host = trim($host);
        $target = match ($encryption) {
            'ssl' => 'ssl://' . $host . ':' . $port,
            'tls' => 'tcp://' . $host . ':' . $port,
            default => 'tcp://' . $host . ':' . $port,
        };

        $stream = @stream_socket_client($target, $errno, $errstr, 12, STREAM_CLIENT_CONNECT);
        if (!is_resource($stream)) {
            throw new RuntimeException('Could not connect to IMAP: ' . $errstr);
        }
        stream_set_timeout($stream, 12);
        $this->stream = $stream;
        $this->readLine();

        if ($encryption === 'tls') {
            $this->command('STARTTLS');
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (!@stream_socket_enable_crypto($stream, true, $crypto)) {
                throw new RuntimeException('IMAP STARTTLS failed.');
            }
        }

        $this->command('LOGIN ' . $this->quote($username) . ' ' . $this->quote($password));
    }

    public function disconnect(): void
    {
        $stream = $this->stream;
        if (is_resource($stream)) {
            try {
                $this->command('LOGOUT');
            } catch (\Throwable) {
            }
            fclose($stream);
        }
        $this->stream = null;
    }

    public function folders(): array
    {
        $lines = $this->command('LIST "" "*"');
        $out = [];
        foreach ($lines as $line) {
            if (!str_starts_with($line, '* LIST') && !str_starts_with($line, '* LSUB')) {
                continue;
            }
            $name = $this->parseListName($line);
            if ($name === null) {
                continue;
            }
            $out[] = ['name' => $name, 'spam' => SiteMailboxGuard::isSpamFolder($name)];
        }

        return $out;
    }

    public function createFolder(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 40 || str_contains($name, '"')) {
            throw new InvalidArgumentException('Folder name is invalid.');
        }
        $this->command('CREATE ' . $this->quote($name));
    }

    public function deleteFolder(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strcasecmp($name, 'INBOX') === 0 || strlen($name) > 80 || str_contains($name, '"')) {
            throw new InvalidArgumentException('Folder cannot be deleted.');
        }
        $this->command('DELETE ' . $this->quote($name));
    }

    public function messages(string $folder, int $limit = 40): array
    {
        $this->select($folder);
        $lines = $this->command('UID SEARCH ALL');
        $uids = [];
        foreach ($lines as $line) {
            if (!str_contains($line, 'SEARCH')) {
                continue;
            }
            foreach (preg_split('/\s+/', $line) ?: [] as $token) {
                if (ctype_digit($token)) {
                    $uids[] = (int) $token;
                }
            }
        }
        $uids = array_slice($uids, -max(1, $limit));
        $out = [];
        foreach ($uids as $uid) {
            $out[] = $this->overview($uid);
        }

        return array_reverse($out);
    }

    public function message(string $folder, int $uid): array
    {
        $this->select($folder);
        $row = $this->overview($uid);
        $part1 = $this->fetchPeek($uid, '1');
        $part2 = $this->fetchPeek($uid, '2');
        $text = $this->fetchPeek($uid, 'TEXT');
        $row['body'] = $part1 !== '' ? $part1 : $text;
        $row['html'] = $part2 !== '' ? $part2 : ($text !== '' ? $text : $part1);

        return $row;
    }

    public function setTags(string $folder, int $uid, array $tags): void
    {
        $this->addFlags($folder, $uid, $tags);
    }

    public function addFlags(string $folder, int $uid, array $flags): void
    {
        $this->storeFlags($folder, $uid, $flags, true);
    }

    public function removeFlags(string $folder, int $uid, array $flags): void
    {
        $this->storeFlags($folder, $uid, $flags, false);
    }

    /**
     * @param list<string> $flags
     */
    private function storeFlags(string $folder, int $uid, array $flags, bool $add): void
    {
        $this->select($folder);
        $tokens = [];
        foreach ($flags as $flag) {
            $token = $this->imapFlagToken($flag);
            if ($token !== '') {
                $tokens[] = $token;
            }
        }
        if ($tokens === []) {
            return;
        }
        $op = $add ? '+FLAGS.SILENT' : '-FLAGS.SILENT';
        $this->command('UID STORE ' . $uid . ' ' . $op . ' (' . implode(' ', $tokens) . ')');
    }

    private function imapFlagToken(string $flag): string
    {
        $flag = strtolower(trim($flag, '\\$ '));
        if ($flag === 'seen') {
            return '\\Seen';
        }
        if ($flag === 'flagged') {
            return '\\Flagged';
        }
        if (preg_match('/^[a-z0-9_-]{1,24}$/', $flag) === 1) {
            return '$' . $flag;
        }

        return '';
    }

    public function move(string $folder, int $uid, string $target): void
    {
        $this->select($folder);
        try {
            $this->command('UID MOVE ' . $uid . ' ' . $this->quote($target));
        } catch (RuntimeException) {
            $this->command('UID COPY ' . $uid . ' ' . $this->quote($target));
            $this->command('UID STORE ' . $uid . ' +FLAGS.SILENT (\\Deleted)');
            $this->command('EXPUNGE');
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @return array<string, mixed>
     */
    private function overview(int $uid): array
    {
        $lines = $this->command('UID FETCH ' . $uid . ' (FLAGS BODY.PEEK[HEADER.FIELDS (FROM SUBJECT DATE)])');
        $blob = preg_replace("/\r\n[ \t]+/", ' ', implode("\n", $lines)) ?? implode("\n", $lines);
        $blob = preg_replace("/\n[ \t]+/", ' ', $blob) ?? $blob;
        $subject = '';
        $from = '';
        $date = '';
        if (preg_match('/^Subject:\s*(.+)$/mi', $blob, $match) === 1) {
            $subject = trim($match[1]);
        }
        if (preg_match('/^From:\s*(.+)$/mi', $blob, $match) === 1) {
            $from = trim($match[1]);
        }
        if (preg_match('/^Date:\s*(.+)$/mi', $blob, $match) === 1) {
            $date = trim($match[1]);
        }
        $tags = [];
        $seen = false;
        $flagged = false;
        if (preg_match('/FLAGS \(([^)]*)\)/', $blob, $match) === 1) {
            foreach (preg_split('/\s+/', $match[1]) ?: [] as $flag) {
                $token = strtolower(ltrim($flag, '\\$'));
                if ($token === 'seen') {
                    $seen = true;
                } elseif ($token === 'flagged') {
                    $flagged = true;
                } elseif (str_starts_with($flag, '$') && strlen($flag) > 1) {
                    $tags[] = substr($flag, 1);
                }
            }
        }

        return [
            'uid' => $uid,
            'subject' => $subject,
            'from' => $from,
            'date' => $date,
            'flags' => [],
            'tags' => $tags,
            'seen' => $seen,
            'flagged' => $flagged,
            'snippet' => $subject,
        ];
    }

    private function fetchPeek(int $uid, string $section): string
    {
        if (!in_array($section, ['1', '2', 'TEXT'], true)) {
            return '';
        }
        try {
            return $this->extractBody(
                $this->command('UID FETCH ' . $uid . ' (BODY.PEEK[' . $section . ']<0.200000>)')
            );
        } catch (RuntimeException) {
            return '';
        }
    }

    private function select(string $folder): void
    {
        $this->command('SELECT ' . $this->quote($folder));
    }

    private function parseListName(string $line): ?string
    {
        if (preg_match('/\s"((?:\\\\.|[^"\\\\])*)"\s*$/', $line, $match) === 1) {
            $name = stripcslashes($match[1]);
        } elseif (preg_match('/\s(\S+)\s*$/', $line, $match) === 1) {
            $name = $match[1];
        } else {
            return null;
        }
        if ($name === '' || str_starts_with($name, '(') || str_contains($name, ')')) {
            return null;
        }

        return $name;
    }

    /**
     * @param list<string> $lines
     */
    private function extractBody(array $lines): string
    {
        $body = [];
        $capture = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, '{') || str_contains($line, 'BODY[')) {
                $capture = true;
                continue;
            }
            if ($capture && (str_starts_with($line, 'A') || str_starts_with($line, ')'))) {
                break;
            }
            if ($capture) {
                $body[] = $line;
            }
        }

        return trim(implode("\n", $body));
    }

    /**
     * @return list<string>
     */
    private function command(string $payload): array
    {
        $stream = $this->stream;
        if (!is_resource($stream)) {
            throw new RuntimeException('IMAP is not connected.');
        }
        $this->tag++;
        $tag = 'A' . $this->tag;
        fwrite($stream, $tag . ' ' . $payload . "\r\n");
        $lines = [];
        while (true) {
            $line = $this->readLine();
            $lines[] = $line;
            if (str_starts_with($line, $tag . ' OK') || str_starts_with($line, $tag . ' NO') || str_starts_with($line, $tag . ' BAD')) {
                if (!str_starts_with($line, $tag . ' OK')) {
                    throw new RuntimeException('IMAP command failed.');
                }
                break;
            }
        }

        return $lines;
    }

    private function readLine(): string
    {
        $stream = $this->stream;
        if (!is_resource($stream)) {
            throw new RuntimeException('IMAP is not connected.');
        }
        $line = fgets($stream);
        if ($line === false) {
            throw new RuntimeException('IMAP read failed.');
        }

        return rtrim($line, "\r\n");
    }

    private function quote(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
}
