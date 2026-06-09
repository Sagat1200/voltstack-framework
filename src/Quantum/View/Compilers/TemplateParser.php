<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use RuntimeException;

final class TemplateParser
{
    private const DIRECTIVE_PATTERN = '/^@([a-zA-Z_][\w-]*)\s*(\((?:[^()]+|(?2))*\))?$/m';

    /**
     * @param array<int, TemplateToken> $tokens
     * @return array<int, TemplateNode>
     */
    public function parse(array $tokens): array
    {
        $nodes = [];

        foreach ($tokens as $token) {
            $nodes[] = $this->parseToken($token);
        }

        return $nodes;
    }

    private function parseToken(TemplateToken $token): TemplateNode
    {
        return match ($token->type()) {
            TemplateToken::TEXT => TemplateNode::text($token->value()),
            TemplateToken::COMMENT => TemplateNode::comment($token->value()),
            TemplateToken::ECHO => TemplateNode::echo($this->parseEchoExpression($token->value(), '{{', '}}')),
            TemplateToken::RAW_ECHO => TemplateNode::rawEcho($this->parseEchoExpression($token->value(), '{!!', '!!}')),
            TemplateToken::DIRECTIVE => $this->parseDirective($token->value()),
            default => throw new RuntimeException(sprintf('Unknown template token type [%s].', $token->type())),
        };
    }

    private function parseDirective(string $value): TemplateNode
    {
        $result = preg_match(self::DIRECTIVE_PATTERN, $value, $matches);

        if ($result === false) {
            throw new RuntimeException('Failed to parse template directive.');
        }

        if ($result !== 1) {
            throw new RuntimeException(sprintf('Unable to parse directive fragment [%s].', $value));
        }

        $expression = $matches[2] ?? null;

        if (is_string($expression) && $expression !== '') {
            $expression = substr($expression, 1, -1);
        }

        return TemplateNode::directive(strtolower($matches[1]), $expression);
    }

    private function parseEchoExpression(string $value, string $opening, string $closing): string
    {
        $value = substr($value, strlen($opening), -strlen($closing));

        return trim($value);
    }
}
