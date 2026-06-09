<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Exceptions\TemplateParseException;

final class TemplateTokenizer
{
    private const INLINE_PATTERN = '/\{\{--[\s\S]*?--\}\}|\{!!\s*[\s\S]+?\s*!!\}|\{\{\s*[\s\S]+?\s*\}\}|@[a-zA-Z_][\w-]*\s*(\((?:[^()]+|(?1))*\))?/m';

    /**
     * @return array<int, TemplateToken>
     */
    public function tokenize(string $contents, int $startLine = 1, int $startColumn = 1): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($contents);
        $line = $startLine;
        $column = $startColumn;

        while ($offset < $length) {
            $result = preg_match(self::INLINE_PATTERN, $contents, $matches, PREG_OFFSET_CAPTURE, $offset);

            if ($result === false) {
                throw new TemplateParseException('Failed to tokenize template fragments', $line, $column);
            }

            if ($result === 0) {
                $tokens[] = TemplateToken::text(substr($contents, $offset), $line, $column);
                break;
            }

            [$matched, $position] = $matches[0];

            if ($position > $offset) {
                $text = substr($contents, $offset, $position - $offset);
                $tokens[] = TemplateToken::text($text, $line, $column);
                [$line, $column] = $this->advancePosition($text, $line, $column);
            }

            $tokens[] = $this->tokenFromMatch($matched, $line, $column);
            [$line, $column] = $this->advancePosition($matched, $line, $column);
            $offset = $position + strlen($matched);
        }

        return $tokens;
    }

    private function tokenFromMatch(string $matched, int $line, int $column): TemplateToken
    {
        if (str_starts_with($matched, '{{--')) {
            return TemplateToken::comment($matched, $line, $column);
        }

        if (str_starts_with($matched, '{!!')) {
            return TemplateToken::rawEcho($matched, $line, $column);
        }

        if (str_starts_with($matched, '{{')) {
            return TemplateToken::echo($matched, $line, $column);
        }

        return TemplateToken::directive($matched, $line, $column);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function advancePosition(string $value, int $line, int $column): array
    {
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            if ($value[$index] === "\n") {
                $line++;
                $column = 1;
                continue;
            }

            $column++;
        }

        return [$line, $column];
    }
}
