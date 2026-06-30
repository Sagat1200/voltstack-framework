<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use JsonException;
use Quantum\Controllers\Controller;
use Quantum\Http\Request;
use Quantum\Http\Response;
use Quantum\Routing\FrontendRouteManifest;
use Quantum\Routing\FrontendRouteManifestStore;
use Quantum\Routing\Router;
use RuntimeException;

final class FrontendRouteManifestController extends Controller
{
    public function __construct(
        private readonly FrontendRouteManifestStore $store,
        private readonly Router $router,
    ) {}

    public function __invoke(Request $request): Response
    {
        $manifest = $this->resolveManifest();
        $etag = '"volt-routes-manifest-' . $manifest->checksum() . '"';
        $lastModifiedAt = is_file($this->store->path()) ? filemtime($this->store->path()) : false;

        if ($this->matchesConditionalRequest($request, $etag, $lastModifiedAt)) {
            return $this->response('', 304, $this->headers($etag, $lastModifiedAt));
        }

        try {
            $contents = json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to serialize the frontend route manifest response.', 0, $exception);
        }

        return $this->response(
            $contents . PHP_EOL,
            200,
            $this->headers($etag, $lastModifiedAt),
        );
    }

    private function resolveManifest(): FrontendRouteManifest
    {
        $compiled = $this->store->compile($this->router);
        $loaded = $this->store->load();

        if ($loaded !== null && $loaded->checksum() === $compiled->checksum()) {
            return $loaded;
        }

        $this->store->write($compiled);

        return $compiled;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $etag, int|false $lastModifiedAt): array
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (is_int($lastModifiedAt)) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $lastModifiedAt) . ' GMT';
        }

        return $headers;
    }

    private function matchesConditionalRequest(Request $request, string $etag, int|false $lastModifiedAt): bool
    {
        $ifNoneMatch = trim((string) $request->header('If-None-Match', ''));

        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            return true;
        }

        $ifModifiedSince = trim((string) $request->header('If-Modified-Since', ''));

        if ($ifModifiedSince === '' || ! is_int($lastModifiedAt)) {
            return false;
        }

        $requestedTimestamp = strtotime($ifModifiedSince);

        return $requestedTimestamp !== false && $requestedTimestamp >= $lastModifiedAt;
    }
}