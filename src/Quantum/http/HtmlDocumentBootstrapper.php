<?php

declare(strict_types=1);

namespace Quantum\Http;

final class HtmlDocumentBootstrapper
{
    private const RUNTIME_MARKER = 'data-volt-runtime="true"';

    public function shouldBootstrap(Request $request, Response $response): bool
    {
        if ($request->isVoltActionRequest()) {
            return false;
        }

        if ($response instanceof JsonResponse || $response instanceof RedirectResponse) {
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
        $content = $response->content();
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
}
