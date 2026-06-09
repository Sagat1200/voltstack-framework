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

        foreach (token_get_all($contents) as $token) {
            if (is_string($token)) {
                $this->push($tokens, TemplateSourceToken::PHP, $token);
                continue;
            }

            [$id, $value] = $token;

            if ($id === T_INLINE_HTML) {
                $this->push($tokens, TemplateSourceToken::INLINE_HTML, $value);
                continue;
            }

            $this->push($tokens, TemplateSourceToken::PHP, $value);
        }

        return $tokens;
    }

    /**
     * @param array<int, TemplateSourceToken> $tokens
     */
    private function push(array &$tokens, string $type, string $value): void
    {
        $lastIndex = array_key_last($tokens);

        if ($lastIndex === null || $tokens[$lastIndex]->type() !== $type) {
            $tokens[] = $type === TemplateSourceToken::INLINE_HTML
                ? TemplateSourceToken::inlineHtml($value)
                : TemplateSourceToken::php($value);

            return;
        }

        $merged = $tokens[$lastIndex]->value() . $value;
        $tokens[$lastIndex] = $type === TemplateSourceToken::INLINE_HTML
            ? TemplateSourceToken::inlineHtml($merged)
            : TemplateSourceToken::php($merged);
    }
}
