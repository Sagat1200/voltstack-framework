<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use Quantum\Http\RedirectResponse;

final class ActionEffectBuilder
{
    public function __construct(
        private readonly HtmlTargetEffectDiffer $differ = new HtmlTargetEffectDiffer(),
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(mixed $actionResult, string $previousHtml, string $nextHtml): array
    {
        if ($actionResult instanceof RedirectResponse) {
            $location = $actionResult->headers()['Location'] ?? null;

            if (is_string($location) && $location !== '') {
                return [[
                    'type' => 'navigate',
                    'url' => $location,
                    'replace' => false,
                    'status' => $actionResult->statusCode(),
                ]];
            }
        }

        $effects = $this->differ->diff($previousHtml, $nextHtml);

        if ($effects !== null && $effects !== []) {
            return $effects;
        }

        return [[
            'type' => 'html.replace',
            'target' => 'root',
            'html' => $nextHtml,
            'outer' => true,
        ]];
    }
}
