<?php

declare(strict_types=1);

/**
 * Adds @param/@return/@var array<string, mixed> PHPDoc tags for bare array types
 * to satisfy PHPStan level 8 missingType.iterableValue checks.
 */

$root = dirname(__DIR__);
$paths = [
    $root . '/backend/app',
    $root . '/backend/bootstrap',
];

$arrayType = 'array<string, mixed>';

foreach ($paths as $basePath) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $fileInfo */
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->getExtension() !== 'php') {
            continue;
        }

        $path = $fileInfo->getPathname();
        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }

        $original = $content;
        $content = patchFile($content, $arrayType);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Patched: {$path}\n";
        }
    }
}

function patchFile(string $content, string $arrayType): string
{
    $content = patchPropertyDocblocks($content, $arrayType);
    $content = patchFunctionDocblocks($content, $arrayType);

    return $content;
}

function patchPropertyDocblocks(string $content, string $arrayType): string
{
    return (string) preg_replace_callback(
        '/((?:public|protected|private)\s+array\s+\$\w+)/m',
        static function (array $matches) use ($content, $arrayType): string {
            $propertyLine = $matches[1];
            $offset = strpos($content, $propertyLine);
            if ($offset === false) {
                return $propertyLine;
            }

            $before = substr($content, max(0, $offset - 600), $offset);
            if (preg_match('/@var\s+' . preg_quote($arrayType, '/') . '/s', $before) === 1) {
                return $propertyLine;
            }

            if (preg_match('/@var\s+array</s', $before) === 1) {
                return $propertyLine;
            }

            return "/** @var {$arrayType} */\n    " . $propertyLine;
        },
        $content
    );
}

function patchFunctionDocblocks(string $content, string $arrayType): string
{
    $pattern = '/(?P<signature>(?:public|protected|private|static)\s+(?:static\s+)?function\s+\w+\s*\((?P<params>[^)]*)\)(?:\s*:\s*(?P<return>array|void|string|int|bool|float|\?[\w\\\\|]+|[\w\\\\|]+))?)/m';

    return (string) preg_replace_callback(
        $pattern,
        static function (array $matches) use ($content, $arrayType): string {
            $signature = $matches['signature'];
            $params = $matches['params'] ?? '';
            $return = $matches['return'] ?? '';

            $offset = strpos($content, $signature);
            if ($offset === false) {
                return $signature;
            }

            $before = substr($content, max(0, $offset - 1200), $offset);
            $docStart = strrpos($before, '/**');
            $docEnd = $docStart !== false ? strrpos($before, '*/') : false;
            $hasDoc = $docStart !== false && $docEnd !== false && $docEnd > $docStart;

            $doc = $hasDoc ? substr($before, $docStart, $docEnd - $docStart + 2) : "/**\n */";

            $arrayParams = [];
            if ($params !== '') {
                foreach (explode(',', $params) as $param) {
                    $param = trim($param);
                    if ($param === '') {
                        continue;
                    }
                    if (preg_match('/array\s+\$(\w+)/', $param, $pm) === 1) {
                        $arrayParams[] = $pm[1];
                    }
                }
            }

            foreach ($arrayParams as $paramName) {
                if (preg_match('/@param\s+' . preg_quote($arrayType, '/') . '\s+\$' . preg_quote($paramName, '/') . '\b/', $doc) === 1) {
                    continue;
                }
                if (preg_match('/@param\s+array<[^>]+>\s+\$' . preg_quote($paramName, '/') . '\b/', $doc) === 1) {
                    continue;
                }
                $doc = preg_replace('/\*\/\s*$/', " * @param {$arrayType} \${$paramName}\n */", $doc) ?? $doc;
            }

            if ($return === 'array' && preg_match('/@return\s+' . preg_quote($arrayType, '/') . '\b/', $doc) !== 1
                && preg_match('/@return\s+array</', $doc) !== 1) {
                $doc = preg_replace('/\*\/\s*$/', " * @return {$arrayType}\n */", $doc) ?? $doc;
            }

            if (!$hasDoc && ($return === 'array' || $arrayParams !== [])) {
                return rtrim($doc) . "\n    " . $signature;
            }

            if ($hasDoc && $doc !== substr($before, $docStart, $docEnd - $docStart + 2)) {
                $oldDoc = substr($before, $docStart, $docEnd - $docStart + 2);
                return str_replace($oldDoc, $doc, $signature);
            }

            return $signature;
        },
        $content
    );
}
