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
        $raw = $this->fetchPeek($uid, 'FULL');
        if ($raw === '') {
            $part1 = $this->fetchPeek($uid, '1');
            $text = $this->fetchPeek($uid, 'TEXT');
            $raw = $part1 !== '' ? $part1 : $text;
        }
        $row['mime'] = $raw;

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

    public function appendMessage(string $folder, string $rfc822, array $flags = []): void
    {
        $folder = trim($folder);
        if ($folder === '') {
            throw new InvalidArgumentException('Folder is invalid.');
        }
        $rfc822 = str_replace(["\r\n", "\r"], "\n", $rfc822);
        $rfc822 = str_replace("\n", "\r\n", $rfc822);
        $size = strlen($rfc822);
        $flagList = '';
        foreach ($flags as $flag) {
            $flag = trim($flag);
            if ($flag !== '') {
                $flagList = $flagList === '' ? '(' . $flag : $flagList . ' ' . $flag;
            }
        }
        if ($flagList !== '') {
            $flagList .= ')';
        }
        $payload = 'APPEND ' . $this->quote($folder);
        if ($flagList !== '') {
            $payload .= ' ' . $flagList;
        }
        $payload .= ' {' . $size . '}';
        $this->commandWithLiteral($payload, $rfc822);
    }

    public function folderStatus(string $folder): array
    {
        $lines = $this->command('STATUS ' . $this->quote($folder) . ' (MESSAGES UNSEEN)');
        $blob = implode("\n", $lines);
        $messages = 0;
        $unseen = 0;
        if (preg_match('/MESSAGES\s+(\d+)/i', $blob, $match) === 1) {
            $messages = (int) $match[1];
        }
        if (preg_match('/UNSEEN\s+(\d+)/i', $blob, $match) === 1) {
            $unseen = (int) $match[1];
        }

        return ['messages' => $messages, 'unseen' => $unseen];
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
        $imapSection = match ($section) {
            'FULL' => '[]',
            '1', '2', 'TEXT' => '[' . $section . ']',
            default => '',
        };
        if ($imapSection === '') {
            return '';
        }
        try {
            return $this->extractBody(
                $this->command('UID FETCH ' . $uid . ' (BODY.PEEK' . $imapSection . '<0.524288>)')
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

    private function commandWithLiteral(string $payload, string $literal): void
    {
        $stream = $this->stream;
        if (!is_resource($stream)) {
            throw new RuntimeException('IMAP is not connected.');
        }
        $this->tag++;
        $tag = 'A' . $this->tag;
        fwrite($stream, $tag . ' ' . $payload . "\r\n");
        while (true) {
            $line = $this->readLine();
            if (str_starts_with($line, '+')) {
                fwrite($stream, $literal);
                continue;
            }
            if (str_starts_with($line, $tag . ' OK')) {
                return;
            }
            if (str_starts_with($line, $tag . ' NO') || str_starts_with($line, $tag . ' BAD')) {
                throw new RuntimeException('IMAP APPEND failed.');
            }
        }
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
