<?php

declare(strict_types=1);

namespace Quantum\Http;

final class HtmlDocumentBootstrapper
{
    private const DEFAULT_DOCUMENT_CONTRACT = 'spa';
    private const RELOAD_DOCUMENT_CONTRACT = 'reload';
    private const DEFAULT_NAVIGATION_MODE = 'auto';
    private const RUNTIME_MARKER = 'data-volt-runtime="true"';
    private const DOCUMENT_ATTRIBUTE_NAMES = [
        'data-volt-document',
        'volt-document',
        'volt:document',
    ];
    private const NAVIGATION_MODE_ATTRIBUTE_NAMES = [
        'data-volt-navigation-mode',
        'volt-navigation-mode',
        'volt:navigation-mode',
    ];
    private const NAVIGATION_MODE_META_NAMES = [
        'volt-navigation-mode',
        'volt:navigation-mode',
    ];
    private const DOCUMENT_META_NAMES = [
        'volt-document',
        'volt:document',
    ];
    private const PAGE_TRANSITION_ATTRIBUTE_NAMES = [
        'data-volt-page-transition',
        'volt-page-transition',
        'volt:page-transition',
    ];
    private const PAGE_TRANSITION_META_NAMES = [
        'volt-page-transition',
        'volt:page-transition',
    ];

    public function shouldBootstrap(Request $request, Response $response): bool
    {
        if (! $request->isConventionalHttpRequest()) {
            return false;
        }

        if ($response instanceof JsonResponse || $response instanceof RedirectResponse) {
            return false;
        }

        if ($this->isAttachment($response)) {
            return false;
        }

        if ($this->hasNonHtmlContentType($response)) {
            return false;
        }

        $content = $response->content();

        if (trim($content) === '' || $this->hasRuntime($content)) {
            return false;
        }

        return $this->looksLikeHtml($content);
    }

    public function bootstrap(Request $request, Response $response): Response
    {
        $content = $this->decorateDocument($request, $response->content());
        $script = volt_runtime_script();

        $bodyOffset = stripos($content, '</body>');
        $htmlOffset = stripos($content, '</html>');

        if ($bodyOffset !== false) {
            $content = substr($content, 0, $bodyOffset) . $script . "\n" . substr($content, $bodyOffset);
        } elseif ($htmlOffset !== false) {
            $content = substr($content, 0, $htmlOffset) . $script . "\n" . substr($content, $htmlOffset);
        } else {
            $content = rtrim($content) . "\n" . $script;
        }

        $response->setContent($content);

        if (! $this->hasContentType($response)) {
            $response->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return $response;
    }

    private function decorateDocument(Request $request, string $content): string
    {
        if (stripos($content, '<body') === false) {
            return $content;
        }

        $documentContract = $this->resolvedDocumentContract($request, $content);

        $content = $this->ensureBodyAttribute(
            $content,
            self::DOCUMENT_ATTRIBUTE_NAMES,
            $documentContract,
        );

        $navigationMode = $this->resolvedNavigationMode($request, $content, $documentContract);

        if ($navigationMode !== null) {
            $content = $this->ensureBodyAttribute($content, self::NAVIGATION_MODE_ATTRIBUTE_NAMES, $navigationMode);
        }

        $pageTransition = $this->resolvedPageTransition($request, $content);

        if ($pageTransition !== null) {
            $content = $this->ensureBodyAttribute($content, self::PAGE_TRANSITION_ATTRIBUTE_NAMES, $pageTransition);
        }

        return $content;
    }

    private function hasRuntime(string $content): bool
    {
        return str_contains($content, self::RUNTIME_MARKER);
    }

    private function looksLikeHtml(string $content): bool
    {
        $trimmed = ltrim($content);

        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^(<!DOCTYPE\s+html|<html\b|<body\b|<[a-zA-Z][^>]*>)/i', $trimmed) === 1) {
            return true;
        }

        return str_contains($content, 'data-volt-root="true"')
            || str_contains($content, 'volt:navigate')
            || str_contains($content, 'volt-navigate');
    }

    /**
     * @param array<int, string> $attributeNames
     */
    private function ensureBodyAttribute(string $content, array $attributeNames, string $value): string
    {
        return (string) preg_replace_callback('/<body\b[^>]*>/i', function (array $matches) use ($attributeNames, $value): string {
            $tag = $matches[0];

            foreach ($attributeNames as $attributeName) {
                if (preg_match('/\b' . preg_quote($attributeName, '/') . '\s*=/i', $tag) === 1) {
                    return $tag;
                }
            }

            return substr($tag, 0, -1) . ' ' . $attributeNames[0] . '="' . $value . '">';
        }, $content, 1);
    }

    private function declaresNavigationModeMeta(string $content): bool
    {
        return $this->declaredMetaContent($content, self::NAVIGATION_MODE_META_NAMES) !== null;
    }

    private function declaredDocumentContract(string $content): ?string
    {
        $value = $this->declaredMetaContent($content, self::DOCUMENT_META_NAMES);

        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeDocumentContract($value);
    }

    private function bodyDocumentContract(string $content): ?string
    {
        $value = $this->bodyAttributeValue($content, self::DOCUMENT_ATTRIBUTE_NAMES);

        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeDocumentContract($value);
    }

    private function normalizeDocumentContract(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'reload', 'reload-only', 'static', 'non-spa', 'document' => self::RELOAD_DOCUMENT_CONTRACT,
            default => self::DEFAULT_DOCUMENT_CONTRACT,
        };
    }

    private function resolvedDocumentContract(Request $request, string $content): string
    {
        return $this->declaredDocumentContract($content)
            ?? $this->bodyDocumentContract($content)
            ?? $this->runtimeDocumentContract($request)
            ?? self::DEFAULT_DOCUMENT_CONTRACT;
    }

    private function resolvedNavigationMode(Request $request, string $content, string $documentContract): ?string
    {
        if ($this->declaresNavigationModeMeta($content) || $this->bodyAttributeValue($content, self::NAVIGATION_MODE_ATTRIBUTE_NAMES) !== null) {
            return null;
        }

        $navigationMode = $this->runtimeNavigationMode($request);

        if ($navigationMode !== null) {
            return $navigationMode;
        }

        return $documentContract !== self::RELOAD_DOCUMENT_CONTRACT
            ? self::DEFAULT_NAVIGATION_MODE
            : null;
    }

    private function resolvedPageTransition(Request $request, string $content): ?string
    {
        if (
            $this->declaredMetaContent($content, self::PAGE_TRANSITION_META_NAMES) !== null
            || $this->bodyAttributeValue($content, self::PAGE_TRANSITION_ATTRIBUTE_NAMES) !== null
        ) {
            return null;
        }

        $transition = $request->routeRuntimeMeta('transition');

        if (! is_string($transition)) {
            $transition = $request->routeRuntimeMeta('pageTransition');
        }

        if (! is_string($transition) || trim($transition) === '') {
            return null;
        }

        return trim($transition);
    }

    /**
     * @param array<int, string> $metaNames
     */
    private function declaredMetaContent(string $content, array $metaNames): ?string
    {
        foreach ($metaNames as $metaName) {
            if (preg_match('/<meta\b[^>]*name\s*=\s*["\']' . preg_quote($metaName, '/') . '["\'][^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $content, $matches) === 1) {
                return trim((string) ($matches[1] ?? ''));
            }

            if (preg_match('/<meta\b[^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*name\s*=\s*["\']' . preg_quote($metaName, '/') . '["\'][^>]*>/i', $content, $matches) === 1) {
                return trim((string) ($matches[1] ?? ''));
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $attributeNames
     */
    private function bodyAttributeValue(string $content, array $attributeNames): ?string
    {
        foreach ($attributeNames as $attributeName) {
            if (preg_match('/<body\b[^>]*\b' . preg_quote($attributeName, '/') . '\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $content, $matches) === 1) {
                return trim((string) ($matches[1] ?? ''));
            }
        }

        return null;
    }

    private function runtimeDocumentContract(Request $request): ?string
    {
        $document = $request->routeRuntimeMeta('document');

        if (! is_string($document) || trim($document) === '') {
            $document = $request->routeRuntimeMeta('contract');
        }

        if (! is_string($document) || trim($document) === '') {
            $mode = $request->routeRuntimeMeta('mode');

            if (is_string($mode) && $this->supportsRuntimeDocumentMode($mode)) {
                $document = $mode;
            }
        }

        if (! is_string($document) || trim($document) === '') {
            return null;
        }

        return $this->normalizeDocumentContract($document);
    }

    private function runtimeNavigationMode(Request $request): ?string
    {
        $navigationMode = $request->routeRuntimeMeta('navigation');

        if (! is_string($navigationMode) || trim($navigationMode) === '') {
            $navigationMode = $request->routeRuntimeMeta('navigationMode');
        }

        if (! is_string($navigationMode) || trim($navigationMode) === '') {
            return null;
        }

        return strtolower(trim($navigationMode));
    }

    private function supportsRuntimeDocumentMode(string $mode): bool
    {
        return in_array(strtolower(trim($mode)), [
            self::DEFAULT_DOCUMENT_CONTRACT,
            self::RELOAD_DOCUMENT_CONTRACT,
            'reload-only',
            'static',
            'non-spa',
            'document',
        ], true);
    }

    private function hasContentType(Response $response): bool
    {
        foreach ($response->headers() as $name => $value) {
            if (strcasecmp($name, 'Content-Type') === 0 && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasNonHtmlContentType(Response $response): bool
    {
        foreach ($response->headers() as $name => $value) {
            if (strcasecmp($name, 'Content-Type') !== 0) {
                continue;
            }

            $normalized = strtoupper(trim($value));

            if ($normalized === '') {
                return false;
            }

            return ! str_contains($normalized, 'TEXT/HTML');
        }

        return false;
    }

    private function isAttachment(Response $response): bool
    {
        foreach ($response->headers() as $name => $value) {
            if (strcasecmp($name, 'Content-Disposition') !== 0) {
                continue;
            }

            return str_contains(strtolower($value), 'attachment');
        }

        return false;
    }
}
