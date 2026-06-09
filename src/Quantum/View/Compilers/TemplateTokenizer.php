<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use RuntimeException;

final class TemplateTokenizer
{
    private const INLINE_PATTERN = '/\{\{--[\s\S]*?--\}\}|\{!!\s*[\s\S]+?\s*!!\}|\{\{\s*[\s\S]+?\s*\}\}|@[a-zA-Z_][\w-]*\s*(\((?:[^()]+|(?1))*\))?/m';

    /**
     * @return array<int, TemplateToken>
     */
    public function tokenize(string $contents): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($contents);

        while ($offset < $length) {
            $result = preg_match(self::INLINE_PATTERN, $contents, $matches, PREG_OFFSET_CAPTURE, $offset);

            if ($result === false) {
                throw new RuntimeException('Failed to tokenize template fragments.');
            }

            if ($result === 0) {
                $tokens[] = TemplateToken::text(substr($contents, $offset));
                break;
            }

            [$matched, $position] = $matches[0];

            if ($position > $offset) {
                $tokens[] = TemplateToken::text(substr($contents, $offset, $position - $offset));
            }

            $tokens[] = $this->tokenFromMatch($matched);
            $offset = $position + strlen($matched);
        }

        return $tokens;
    }

    private function tokenFromMatch(string $matched): TemplateToken
    {
        if (str_starts_with($matched, '{{--')) {
            return TemplateToken::comment($matched);
        }

        if (str_starts_with($matched, '{!!')) {
            return TemplateToken::rawEcho($matched);
        }

        if (str_starts_with($matched, '{{')) {
            return TemplateToken::echo($matched);
        }

        return TemplateToken::directive($matched);
    }
}
