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

    public function shouldBootstrap(Request $request, Response $response): bool
    {
        if ($request->isVoltActionRequest()) {
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

    public function bootstrap(Response $response): Response
    {
        $content = $this->decorateDocument($response->content());
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

    private function decorateDocument(string $content): string
    {
        if (stripos($content, '<body') === false) {
            return $content;
        }

        $documentContract = $this->resolvedDocumentContract($content);

        $content = $this->ensureBodyAttribute(
            $content,
            self::DOCUMENT_ATTRIBUTE_NAMES,
            $documentContract,
        );

        if ($documentContract !== self::RELOAD_DOCUMENT_CONTRACT && ! $this->declaresNavigationModeMeta($content)) {
            $content = $this->ensureBodyAttribute($content, self::NAVIGATION_MODE_ATTRIBUTE_NAMES, self::DEFAULT_NAVIGATION_MODE);
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
        foreach (self::NAVIGATION_MODE_META_NAMES as $metaName) {
            if (preg_match('/<meta\b[^>]*name\s*=\s*["\']' . preg_quote($metaName, '/') . '["\'][^>]*>/i', $content) === 1) {
                return true;
            }
        }

        return false;
    }

    private function declaredDocumentContract(string $content): ?string
    {
        foreach (self::DOCUMENT_META_NAMES as $metaName) {
            if (preg_match('/<meta\b[^>]*name\s*=\s*["\']' . preg_quote($metaName, '/') . '["\'][^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $content, $matches) === 1) {
                return $this->normalizeDocumentContract($matches[1] ?? '');
            }

            if (preg_match('/<meta\b[^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*name\s*=\s*["\']' . preg_quote($metaName, '/') . '["\'][^>]*>/i', $content, $matches) === 1) {
                return $this->normalizeDocumentContract($matches[1] ?? '');
            }
        }

        return null;
    }

    private function bodyDocumentContract(string $content): ?string
    {
        foreach (self::DOCUMENT_ATTRIBUTE_NAMES as $attributeName) {
            if (preg_match('/<body\b[^>]*\b' . preg_quote($attributeName, '/') . '\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $content, $matches) === 1) {
                return $this->normalizeDocumentContract($matches[1] ?? '');
            }
        }

        return null;
    }

    private function normalizeDocumentContract(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'reload', 'reload-only', 'static', 'non-spa', 'document' => self::RELOAD_DOCUMENT_CONTRACT,
            default => self::DEFAULT_DOCUMENT_CONTRACT,
        };
    }

    private function resolvedDocumentContract(string $content): string
    {
        return $this->declaredDocumentContract($content)
            ?? $this->bodyDocumentContract($content)
            ?? self::DEFAULT_DOCUMENT_CONTRACT;
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
