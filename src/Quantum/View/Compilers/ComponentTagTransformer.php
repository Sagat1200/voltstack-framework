<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Exceptions\TemplateParseException;

final class ComponentTagTransformer
{
    private const DIRECTIVE_SEPARATOR = '{{--__volt_component_tag_separator__--}}';

    /**
     * @param array<string, mixed> $options
     */
    public function transform(string $contents, int $line = 1, int $column = 1): string
    {
        $cursor = 0;

        return $this->parseNodes($contents, $cursor, null, $line, $column);
    }

    private function parseNodes(
        string $contents,
        int &$cursor,
        ?string $expectedClosingTag,
        int $line,
        int $column,
    ): string {
        $result = '';
        $length = strlen($contents);

        while ($cursor < $length) {
            if ($expectedClosingTag !== null && $this->startsWithClosingTag($contents, $cursor, $expectedClosingTag)) {
                $this->consumeClosingTag($contents, $cursor, $expectedClosingTag, $line, $column);

                return $result;
            }

            if ($this->startsWithOpeningTag($contents, $cursor)) {
                $result .= $this->parseComponentTag($contents, $cursor, $line, $column);
                continue;
            }

            $character = $contents[$cursor];
            $result .= $character;
            [$line, $column] = $this->advancePosition($character, $line, $column);
            $cursor++;
        }

        if ($expectedClosingTag !== null) {
            throw new TemplateParseException(
                sprintf('Unclosed component tag [%s]', $expectedClosingTag),
                $line,
                $column,
            );
        }

        return $result;
    }

    private function parseComponentTag(string $contents, int &$cursor, int &$line, int &$column): string
    {
        $tagLine = $line;
        $tagColumn = $column;
        $tagMarkup = $this->readTagMarkup($contents, $cursor, $line, $column);
        $selfClosing = str_ends_with($tagMarkup, '/>');
        $innerMarkup = substr($tagMarkup, 3, - ($selfClosing ? 2 : 1));
        $innerMarkup = trim($innerMarkup);

        if ($innerMarkup === '') {
            throw new TemplateParseException('Component tags require a name', $tagLine, $tagColumn);
        }

        preg_match('/^(?<name>[A-Za-z0-9:._-]+)(?<attributes>[\s\S]*)$/', $innerMarkup, $matches);

        $name = trim((string) ($matches['name'] ?? ''));
        $attributeSource = trim((string) ($matches['attributes'] ?? ''));

        if ($name === '') {
            throw new TemplateParseException('Component tags require a name', $tagLine, $tagColumn);
        }

        if ($this->isSlotTag($name)) {
            return $this->compileSlotTag($contents, $cursor, $name, $selfClosing, $line, $column, $tagLine, $tagColumn);
        }

        $expression = $this->compileArguments($this->normalizeComponentName($name), $attributeSource);

        if ($selfClosing) {
            return sprintf('@dynamic(%s)', $expression);
        }

        $body = $this->parseNodes($contents, $cursor, $name, $line, $column);

        return sprintf('@component(%s)%s@endcomponent%s', $expression, $body, self::DIRECTIVE_SEPARATOR);
    }

    private function compileSlotTag(
        string $contents,
        int &$cursor,
        string $name,
        bool $selfClosing,
        int &$line,
        int &$column,
        int $tagLine,
        int $tagColumn,
    ): string {
        $slotName = $this->slotNameFromTag($name);

        if ($slotName === '') {
            throw new TemplateParseException('Slot tags require a name', $tagLine, $tagColumn);
        }

        if ($selfClosing) {
            return sprintf("@slot('%s')@endslot%s", $slotName, self::DIRECTIVE_SEPARATOR);
        }

        $body = $this->parseNodes($contents, $cursor, $name, $line, $column);

        return sprintf("@slot('%s')%s@endslot%s", $slotName, $body, self::DIRECTIVE_SEPARATOR);
    }

    private function startsWithOpeningTag(string $contents, int $cursor): bool
    {
        return substr($contents, $cursor, 3) === '<x-';
    }

    private function startsWithClosingTag(string $contents, int $cursor, string $name): bool
    {
        return preg_match(
            sprintf('/\G<\/x-%s\s*>/A', preg_quote($name, '/')),
            $contents,
            $matches,
            0,
            $cursor,
        ) === 1;
    }

    private function consumeClosingTag(string $contents, int &$cursor, string $name, int &$line, int &$column): void
    {
        preg_match(
            sprintf('/\G<\/x-%s\s*>/A', preg_quote($name, '/')),
            $contents,
            $matches,
            0,
            $cursor,
        );

        $markup = (string) ($matches[0] ?? '');
        [$line, $column] = $this->advancePosition($markup, $line, $column);
        $cursor += strlen($markup);
    }

    private function readTagMarkup(string $contents, int &$cursor, int &$line, int &$column): string
    {
        $length = strlen($contents);
        $tag = '';
        $quote = null;

        while ($cursor < $length) {
            $character = $contents[$cursor];
            $tag .= $character;
            [$line, $column] = $this->advancePosition($character, $line, $column);
            $cursor++;

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === '\'') {
                $quote = $character;
                continue;
            }

            if ($character === '>') {
                return $tag;
            }
        }

        throw new TemplateParseException('Unterminated component tag', $line, $column);
    }

    private function normalizeComponentName(string $name): string
    {
        return str_replace(':', '.', trim($name));
    }

    private function isSlotTag(string $name): bool
    {
        return str_starts_with(strtolower($name), 'slot:');
    }

    private function slotNameFromTag(string $name): string
    {
        return trim(substr($name, strlen('slot:')));
    }

    private function compileArguments(string $name, string $attributeSource): string
    {
        $attributes = $this->parseAttributes($attributeSource);
        $props = [];
        $htmlAttributes = [];

        foreach ($attributes as $attribute) {
            $key = $attribute['name'];
            $target = $this->isHtmlAttribute($key) ? $htmlAttributes : $props;
            $target[$key] = $attribute['expression'];

            if ($this->isHtmlAttribute($key)) {
                $htmlAttributes[$key] = $attribute['expression'];
                continue;
            }

            $props[$key] = $attribute['expression'];
        }

        if ($htmlAttributes !== []) {
            $props['attributes'] = $this->compileArray($htmlAttributes);
        }

        if ($props === []) {
            return var_export($name, true);
        }

        return sprintf('%s, %s', var_export($name, true), $this->compileArray($props));
    }

    /**
     * @return array<int, array{name: string, expression: string}>
     */
    private function parseAttributes(string $source): array
    {
        $attributes = [];
        $length = strlen($source);
        $cursor = 0;

        while ($cursor < $length) {
            while ($cursor < $length && ctype_space($source[$cursor])) {
                $cursor++;
            }

            if ($cursor >= $length) {
                break;
            }

            $nameStart = $cursor;

            while ($cursor < $length && preg_match('/[:A-Za-z0-9_.-]/', $source[$cursor]) === 1) {
                $cursor++;
            }

            $rawName = substr($source, $nameStart, $cursor - $nameStart);
            $rawName = trim($rawName);

            if ($rawName === '') {
                break;
            }

            $isBound = str_starts_with($rawName, ':');
            $name = $isBound ? substr($rawName, 1) : $rawName;
            $name = trim($name);

            while ($cursor < $length && ctype_space($source[$cursor])) {
                $cursor++;
            }

            if ($cursor < $length && $source[$cursor] === '=') {
                $cursor++;

                while ($cursor < $length && ctype_space($source[$cursor])) {
                    $cursor++;
                }

                $expression = $this->readAttributeValue($source, $cursor, $isBound);
            } else {
                $expression = 'true';
            }

            if ($name !== '') {
                $attributes[] = [
                    'name' => $name,
                    'expression' => $expression,
                ];
            }
        }

        return $attributes;
    }

    private function readAttributeValue(string $source, int &$cursor, bool $isBound): string
    {
        $length = strlen($source);

        if ($cursor >= $length) {
            return $isBound ? 'null' : var_export('', true);
        }

        $quote = $source[$cursor];

        if ($quote === '"' || $quote === '\'') {
            $cursor++;
            $start = $cursor;

            while ($cursor < $length && $source[$cursor] !== $quote) {
                $cursor++;
            }

            $value = substr($source, $start, $cursor - $start);

            if ($cursor < $length) {
                $cursor++;
            }

            return $isBound ? trim($value) : var_export($value, true);
        }

        $start = $cursor;

        while ($cursor < $length && ! ctype_space($source[$cursor])) {
            $cursor++;
        }

        $value = substr($source, $start, $cursor - $start);

        return $isBound ? trim($value) : var_export($value, true);
    }

    private function isHtmlAttribute(string $name): bool
    {
        return $name === 'class'
            || $name === 'style'
            || $name === 'id'
            || str_starts_with($name, 'data-')
            || str_starts_with($name, 'aria-');
    }

    /**
     * @param array<string, string> $values
     */
    private function compileArray(array $values): string
    {
        $pairs = [];

        foreach ($values as $key => $value) {
            $pairs[] = sprintf('%s => %s', var_export($key, true), $value);
        }

        return '[' . implode(', ', $pairs) . ']';
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