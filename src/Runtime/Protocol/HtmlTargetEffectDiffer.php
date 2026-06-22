<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class HtmlTargetEffectDiffer
{
    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function diff(string $previousHtml, string $nextHtml): ?array
    {
        $previousTargets = $this->extractTargets($previousHtml);
        $nextTargets = $this->extractTargets($nextHtml);

        if ($previousTargets === null || $nextTargets === null) {
            return null;
        }

        $previousNames = array_keys($previousTargets);
        $nextNames = array_keys($nextTargets);
        sort($previousNames);
        sort($nextNames);

        if ($previousNames === [] || $previousNames !== $nextNames) {
            return null;
        }

        $effects = [];

        foreach ($nextNames as $name) {
            $changes = $this->compareTarget($name, $previousTargets[$name], $nextTargets[$name]);

            if ($changes === null) {
                return null;
            }

            array_push($effects, ...$changes);
        }

        return $effects;
    }

    /**
     * @return array<string, DOMElement>|null
     */
    private function extractTargets(string $html): ?array
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-volt-target]');

        if ($nodes === false) {
            return null;
        }

        $targets = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $name = trim($node->getAttribute('data-volt-target'));

            if ($name === '' || isset($targets[$name])) {
                return null;
            }

            $targets[$name] = $node;
        }

        return $targets;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function compareTarget(string $target, DOMElement $previous, DOMElement $next): ?array
    {
        if ($previous->tagName !== $next->tagName) {
            return null;
        }

        $effects = [];
        $previousChildren = $this->elementChildCount($previous);
        $nextChildren = $this->elementChildCount($next);

        $attributeChanges = $this->attributeChanges($target, $previous, $next);

        if ($attributeChanges === null) {
            return null;
        }

        array_push($effects, ...$attributeChanges);

        if ($previousChildren > 0 || $nextChildren > 0) {
            $listChanges = $this->listChildChanges($target, $previous, $next);

            if ($listChanges !== null) {
                array_push($effects, ...$listChanges);

                return $effects;
            }
        }

        if ($previousChildren === 0 && $this->textContent($previous) !== $this->textContent($next)) {
            $effects[] = [
                'type' => 'text.update',
                'target' => $target,
                'value' => $this->textContent($next),
            ];
        }

        if ($previousChildren > 0 && $this->innerMarkup($previous) !== $this->innerMarkup($next)) {
            return null;
        }

        return $effects;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function listChildChanges(string $target, DOMElement $previous, DOMElement $next): ?array
    {
        $previousChildren = $this->directChildElements($previous);
        $nextChildren = $this->directChildElements($next);

        if ($previousChildren === [] && $nextChildren === []) {
            return [];
        }

        if (! $this->hasStableKeys($previousChildren) || ! $this->hasStableKeys($nextChildren)) {
            return null;
        }

        $previousByKey = $this->mapChildrenByKey($previousChildren);
        $nextByKey = $this->mapChildrenByKey($nextChildren);
        $previousKeys = array_keys($previousByKey);
        $nextKeys = array_keys($nextByKey);

        if ($previousKeys === $nextKeys) {
            return $this->moveAndReplaceEffects($target, $previousByKey, $nextByKey, $previousKeys, $nextKeys);
        }

        if ($this->isSubsequence($previousKeys, $nextKeys)) {
            if (array_slice($nextKeys, 0, count($previousKeys)) === $previousKeys) {
                return $this->appendAndReplaceEffects($target, $previousByKey, $nextByKey, $previousKeys, $nextKeys);
            }

            return $this->insertAndReplaceEffects($target, $previousByKey, $nextByKey, $previousKeys, $nextKeys);
        }

        if ($this->isSubsequence($nextKeys, $previousKeys)) {
            return $this->removeAndReplaceEffects($target, $previousByKey, $nextByKey, $previousKeys, $nextKeys);
        }

        if ($this->sameKeySet($previousKeys, $nextKeys)) {
            return $this->moveAndReplaceEffects($target, $previousByKey, $nextByKey, $previousKeys, $nextKeys);
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function attributeChanges(string $target, DOMElement $previous, DOMElement $next): ?array
    {
        $previousAttributes = $this->attributes($previous);
        $nextAttributes = $this->attributes($next);
        $effects = [];

        array_push($effects, ...$this->classChanges($target, $previousAttributes, $nextAttributes));
        array_push($effects, ...$this->styleChanges($target, $previousAttributes, $nextAttributes));

        foreach ($nextAttributes as $name => $value) {
            if (in_array($name, ['class', 'style'], true)) {
                continue;
            }

            if (! array_key_exists($name, $previousAttributes) || $previousAttributes[$name] !== $value) {
                $effects[] = [
                    'type' => 'attribute.set',
                    'target' => $target,
                    'name' => $name,
                    'value' => $value,
                ];
            }
        }

        foreach ($previousAttributes as $name => $value) {
            if (in_array($name, ['class', 'style'], true)) {
                continue;
            }

            if (! array_key_exists($name, $nextAttributes)) {
                $effects[] = [
                    'type' => 'attribute.remove',
                    'target' => $target,
                    'name' => $name,
                ];
            }
        }

        return $effects;
    }

    /**
     * @param array<string, string> $previousAttributes
     * @param array<string, string> $nextAttributes
     * @return array<int, array<string, mixed>>
     */
    private function classChanges(string $target, array $previousAttributes, array $nextAttributes): array
    {
        $previousClasses = $this->parseClassList($previousAttributes['class'] ?? '');
        $nextClasses = $this->parseClassList($nextAttributes['class'] ?? '');
        $allClasses = array_values(array_unique([...$previousClasses, ...$nextClasses]));
        sort($allClasses);
        $effects = [];

        foreach ($allClasses as $className) {
            $wasPresent = in_array($className, $previousClasses, true);
            $isPresent = in_array($className, $nextClasses, true);

            if ($wasPresent === $isPresent) {
                continue;
            }

            $effects[] = [
                'type' => 'class.toggle',
                'target' => $target,
                'class' => $className,
                'force' => $isPresent,
            ];
        }

        return $effects;
    }

    /**
     * @param array<string, string> $previousAttributes
     * @param array<string, string> $nextAttributes
     * @return array<int, array<string, mixed>>
     */
    private function styleChanges(string $target, array $previousAttributes, array $nextAttributes): array
    {
        $previousStyles = $this->parseStyleDeclarations($previousAttributes['style'] ?? '');
        $nextStyles = $this->parseStyleDeclarations($nextAttributes['style'] ?? '');
        $properties = array_values(array_unique([...array_keys($previousStyles), ...array_keys($nextStyles)]));
        sort($properties);
        $effects = [];

        foreach ($properties as $property) {
            $previousValue = $previousStyles[$property] ?? null;
            $nextValue = $nextStyles[$property] ?? null;

            if ($previousValue === $nextValue) {
                continue;
            }

            $effects[] = [
                'type' => 'style.set',
                'target' => $target,
                'property' => $property,
                'value' => $nextValue,
            ];
        }

        return $effects;
    }

    /**
     * @return array<string, string>
     */
    private function attributes(DOMElement $element): array
    {
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $name = $attribute->nodeName;

            if ($name === 'data-volt-target') {
                continue;
            }

            $attributes[$name] = $attribute->nodeValue ?? '';
        }

        ksort($attributes);

        return $attributes;
    }

    private function elementChildCount(DOMElement $element): int
    {
        return count($this->directChildElements($element));
    }

    private function textContent(DOMElement $element): string
    {
        return $element->textContent ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function parseClassList(string $classAttribute): array
    {
        $classes = preg_split('/\s+/', trim($classAttribute)) ?: [];
        $classes = array_values(array_filter($classes, static fn(string $value): bool => $value !== ''));
        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * @return array<string, string>
     */
    private function parseStyleDeclarations(string $styleAttribute): array
    {
        $styles = [];

        foreach (explode(';', $styleAttribute) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '' || ! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = explode(':', $declaration, 2);
            $property = trim(strtolower($property));
            $value = trim($value);

            if ($property === '') {
                continue;
            }

            $styles[$property] = $value;
        }

        ksort($styles);

        return $styles;
    }

    private function innerMarkup(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    private function outerMarkup(DOMElement $element): string
    {
        return $element->ownerDocument->saveHTML($element) ?: '';
    }

    /**
     * @return array<int, DOMElement>
     */
    private function directChildElements(DOMElement $element): array
    {
        $children = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * @param array<int, DOMElement> $elements
     */
    private function hasStableKeys(array $elements): bool
    {
        $keys = [];

        foreach ($elements as $element) {
            $key = $this->childKey($element);

            if ($key === null || isset($keys[$key])) {
                return false;
            }

            $keys[$key] = true;
        }

        return true;
    }

    private function childKey(DOMElement $element): ?string
    {
        $key = trim($element->getAttribute('data-volt-key'));

        return $key === '' ? null : $key;
    }

    /**
     * @param array<int, DOMElement> $children
     * @return array<string, DOMElement>
     */
    private function mapChildrenByKey(array $children): array
    {
        $mapped = [];

        foreach ($children as $child) {
            $key = $this->childKey($child);

            if ($key !== null) {
                $mapped[$key] = $child;
            }
        }

        return $mapped;
    }

    /**
     * @param array<string, DOMElement> $previousByKey
     * @param array<string, DOMElement> $nextByKey
     * @param array<int, string> $previousKeys
     * @param array<int, string> $nextKeys
     * @return array<int, array<string, mixed>>
     */
    private function moveAndReplaceEffects(
        string $target,
        array $previousByKey,
        array $nextByKey,
        array $previousKeys,
        array $nextKeys,
    ): array {
        $effects = [];
        $currentKeys = $previousKeys;

        for ($index = 0, $count = count($nextKeys); $index < $count; $index++) {
            $desiredKey = $nextKeys[$index];

            if (($currentKeys[$index] ?? null) === $desiredKey) {
                continue;
            }

            $fromIndex = array_search($desiredKey, $currentKeys, true);

            if ($fromIndex === false) {
                return [];
            }

            $beforeKey = $currentKeys[$index] ?? null;

            $effects[] = [
                'type' => 'dom.move',
                'selector' => $this->itemSelector($target, $desiredKey),
                'parentTarget' => $target,
                'beforeSelector' => $beforeKey !== null ? $this->itemSelector($target, $beforeKey) : null,
                'position' => $beforeKey === null ? 'beforeend' : 'beforebegin',
            ];

            array_splice($currentKeys, (int) $fromIndex, 1);
            array_splice($currentKeys, $index, 0, [$desiredKey]);
        }

        return array_merge($effects, $this->replacementEffects($target, $previousByKey, $nextByKey, $nextKeys));
    }

    /**
     * @param array<string, DOMElement> $previousByKey
     * @param array<string, DOMElement> $nextByKey
     * @param array<int, string> $previousKeys
     * @param array<int, string> $nextKeys
     * @return array<int, array<string, mixed>>
     */
    private function insertAndReplaceEffects(
        string $target,
        array $previousByKey,
        array $nextByKey,
        array $previousKeys,
        array $nextKeys,
    ): array {
        $effects = [];
        $currentKeys = $previousKeys;

        for ($index = 0, $count = count($nextKeys); $index < $count; $index++) {
            $desiredKey = $nextKeys[$index];

            if (($currentKeys[$index] ?? null) === $desiredKey) {
                continue;
            }

            if (in_array($desiredKey, $currentKeys, true)) {
                return [];
            }

            $beforeKey = $currentKeys[$index] ?? null;

            $effects[] = [
                'type' => 'dom.insert',
                'target' => $target,
                'beforeSelector' => $beforeKey !== null ? $this->itemSelector($target, $beforeKey) : null,
                'position' => $beforeKey === null ? 'beforeend' : 'beforebegin',
                'html' => $this->outerMarkup($nextByKey[$desiredKey]),
            ];

            array_splice($currentKeys, $index, 0, [$desiredKey]);
        }

        return array_merge($effects, $this->replacementEffects($target, $previousByKey, $nextByKey, $previousKeys));
    }

    /**
     * @param array<string, DOMElement> $previousByKey
     * @param array<string, DOMElement> $nextByKey
     * @param array<int, string> $previousKeys
     * @param array<int, string> $nextKeys
     * @return array<int, array<string, mixed>>
     */
    private function appendAndReplaceEffects(
        string $target,
        array $previousByKey,
        array $nextByKey,
        array $previousKeys,
        array $nextKeys,
    ): array {
        $html = '';

        for ($index = count($previousKeys), $count = count($nextKeys); $index < $count; $index++) {
            $html .= $this->outerMarkup($nextByKey[$nextKeys[$index]]);
        }

        $effects = [];

        if ($html !== '') {
            $effects[] = [
                'type' => 'dom.append',
                'target' => $target,
                'position' => 'beforeend',
                'html' => $html,
            ];
        }

        return array_merge($effects, $this->replacementEffects($target, $previousByKey, $nextByKey, $previousKeys));
    }

    /**
     * @param array<string, DOMElement> $previousByKey
     * @param array<string, DOMElement> $nextByKey
     * @param array<int, string> $previousKeys
     * @param array<int, string> $nextKeys
     * @return array<int, array<string, mixed>>
     */
    private function removeAndReplaceEffects(
        string $target,
        array $previousByKey,
        array $nextByKey,
        array $previousKeys,
        array $nextKeys,
    ): array {
        $effects = $this->replacementEffects($target, $previousByKey, $nextByKey, $nextKeys);

        for ($index = count($previousKeys) - 1; $index >= 0; $index--) {
            $key = $previousKeys[$index];

            if (in_array($key, $nextKeys, true)) {
                continue;
            }

            $effects[] = [
                'type' => 'dom.remove',
                'selector' => $this->itemSelector($target, $key),
            ];
        }

        return $effects;
    }

    /**
     * @param array<string, DOMElement> $previousByKey
     * @param array<string, DOMElement> $nextByKey
     * @param array<int, string> $keys
     * @return array<int, array<string, mixed>>
     */
    private function replacementEffects(string $target, array $previousByKey, array $nextByKey, array $keys): array
    {
        $effects = [];

        foreach ($keys as $key) {
            if (! isset($previousByKey[$key], $nextByKey[$key])) {
                continue;
            }

            if ($this->outerMarkup($previousByKey[$key]) === $this->outerMarkup($nextByKey[$key])) {
                continue;
            }

            $effects[] = [
                'type' => 'html.replace',
                'selector' => $this->itemSelector($target, $key),
                'html' => $this->outerMarkup($nextByKey[$key]),
                'outer' => true,
            ];
        }

        return $effects;
    }

    /**
     * @param array<int, string> $needle
     * @param array<int, string> $haystack
     */
    private function isSubsequence(array $needle, array $haystack): bool
    {
        $needleIndex = 0;
        $needleCount = count($needle);

        foreach ($haystack as $value) {
            if ($needleIndex < $needleCount && $needle[$needleIndex] === $value) {
                $needleIndex++;
            }
        }

        return $needleIndex === $needleCount;
    }

    /**
     * @param array<int, string> $left
     * @param array<int, string> $right
     */
    private function sameKeySet(array $left, array $right): bool
    {
        $leftSorted = $left;
        $rightSorted = $right;
        sort($leftSorted);
        sort($rightSorted);

        return $leftSorted === $rightSorted;
    }

    private function itemSelector(string $target, string $key): string
    {
        return sprintf('[data-volt-target="%s"] > [data-volt-key="%s"]', $target, $key);
    }
}
