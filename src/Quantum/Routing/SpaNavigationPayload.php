<?php

declare(strict_types=1);

namespace Quantum\Routing;

final class SpaNavigationPayload
{
    private const PROTOCOL_NAME = 'VoltStack SPA Routing';
    private const PROTOCOL_VERSION = '1.0';

    /**
     * @param array{target: string, method: string} $navigation
     * @param array{route: ?string} $screen
     * @param array{document: ?string, navigation: ?string} $policy
     * @param array{layout: ?string, transition: ?string, hydrate: ?bool} $runtime
     * @param array{location: string, status: int}|null $redirect
     * @param array{code: int, message: string}|null $error
     */
    public function __construct(
        private readonly array $navigation,
        private readonly array $screen,
        private readonly array $policy,
        private readonly array $runtime,
        private readonly ?array $redirect,
        private readonly ?array $error,
    ) {}

    /**
     * @return array{
     *     protocol: array{name: string, version: string},
     *     navigation: array{target: string, method: string},
     *     screen: array{route: ?string},
     *     policy: array{document: ?string, navigation: ?string},
     *     runtime: array{layout: ?string, transition: ?string, hydrate: ?bool},
     *     redirect: array{location: string, status: int}|null,
     *     error: array{code: int, message: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'protocol' => [
                'name' => self::PROTOCOL_NAME,
                'version' => self::PROTOCOL_VERSION,
            ],
            'navigation' => $this->navigation,
            'screen' => $this->screen,
            'policy' => $this->policy,
            'runtime' => $this->runtime,
            'redirect' => $this->redirect,
            'error' => $this->error,
        ];
    }
}