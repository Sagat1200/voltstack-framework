<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Exceptions\TemplateParseException;

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
            TemplateToken::TEXT => TemplateNode::text($token->value(), $token->line(), $token->column()),
            TemplateToken::COMMENT => TemplateNode::comment($token->value(), $token->line(), $token->column()),
            TemplateToken::ECHO => TemplateNode::echo(
                $this->parseEchoExpression($token->value(), '{{', '}}'),
                $token->line(),
                $token->column(),
            ),
            TemplateToken::RAW_ECHO => TemplateNode::rawEcho(
                $this->parseEchoExpression($token->value(), '{!!', '!!}'),
                $token->line(),
                $token->column(),
            ),
            TemplateToken::DIRECTIVE => $this->parseDirective($token),
            default => throw new TemplateParseException(
                sprintf('Unknown template token type [%s]', $token->type()),
                $token->line(),
                $token->column(),
            ),
        };
    }

    private function parseDirective(TemplateToken $token): TemplateNode
    {
        $value = $token->value();
        $result = preg_match(self::DIRECTIVE_PATTERN, $value, $matches);

        if ($result === false) {
            throw new TemplateParseException('Failed to parse template directive', $token->line(), $token->column());
        }

        if ($result !== 1) {
            throw new TemplateParseException(
                sprintf('Unable to parse directive fragment [%s]', $value),
                $token->line(),
                $token->column(),
            );
        }

        $expression = $matches[2] ?? null;

        if (is_string($expression) && $expression !== '') {
            $expression = substr($expression, 1, -1);
        }

        return TemplateNode::directive(strtolower($matches[1]), $expression, $token->line(), $token->column());
    }

    private function parseEchoExpression(string $value, string $opening, string $closing): string
    {
        $value = substr($value, strlen($opening), -strlen($closing));

        return trim($value);
    }
}
