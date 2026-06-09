<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

/**
 * Splits a mixed PHP/template source into high-level segments so the compiler
 * only parses inline HTML fragments and passes PHP tokens through untouched.
 */
final class TemplateSourceTokenizer
{
    /**
     * @return array<int, TemplateSourceToken>
     */
    public function tokenize(string $contents): array
    {
        $tokens = [];
        $line = 1;
        $column = 1;

        foreach (token_get_all($contents) as $token) {
            if (is_string($token)) {
                $this->push($tokens, TemplateSourceToken::PHP, $token, $line, $column);
                [$line, $column] = $this->advancePosition($token, $line, $column);
                continue;
            }

            [$id, $value] = $token;

            if ($id === T_INLINE_HTML) {
                $this->push($tokens, TemplateSourceToken::INLINE_HTML, $value, $line, $column);
                [$line, $column] = $this->advancePosition($value, $line, $column);
                continue;
            }

            $this->push($tokens, TemplateSourceToken::PHP, $value, $line, $column);
            [$line, $column] = $this->advancePosition($value, $line, $column);
        }

        return $tokens;
    }

    /**
     * @param array<int, TemplateSourceToken> $tokens
     */
    private function push(array &$tokens, string $type, string $value, int $line, int $column): void
    {
        $lastIndex = array_key_last($tokens);

        if ($lastIndex === null || $tokens[$lastIndex]->type() !== $type) {
            $tokens[] = $type === TemplateSourceToken::INLINE_HTML
                ? TemplateSourceToken::inlineHtml($value, $line, $column)
                : TemplateSourceToken::php($value, $line, $column);

            return;
        }

        $merged = $tokens[$lastIndex]->value() . $value;
        $tokens[$lastIndex] = $type === TemplateSourceToken::INLINE_HTML
            ? TemplateSourceToken::inlineHtml($merged, $tokens[$lastIndex]->line(), $tokens[$lastIndex]->column())
            : TemplateSourceToken::php($merged, $tokens[$lastIndex]->line(), $tokens[$lastIndex]->column());
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
