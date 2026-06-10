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
            return $this->applyOptions($effects, $actionResult);
        }

        return $this->applyOptions([[
            'type' => 'html.replace',
            'target' => 'root',
            'html' => $nextHtml,
            'outer' => true,
        ]], $actionResult);
    }

    /**
     * @param array<int, array<string, mixed>> $effects
     * @return array<int, array<string, mixed>>
     */
    private function applyOptions(array $effects, mixed $actionResult): array
    {
        if (! $actionResult instanceof ActionEffectOptions) {
            return $effects;
        }

        return array_map(
            fn(array $effect): array => $this->applyRulesToEffect($effect, $actionResult->rules()),
            $effects,
        );
    }

    /**
     * @param array<string, mixed> $effect
     * @param array<int, array<string, mixed>> $rules
     * @return array<string, mixed>
     */
    private function applyRulesToEffect(array $effect, array $rules): array
    {
        foreach ($rules as $rule) {
            if (! $this->matchesRule($effect, $rule)) {
                continue;
            }

            if (isset($rule['transition']) && is_array($rule['transition'])) {
                $effect['transition'] = $rule['transition'];
            }

            if (isset($rule['transitions']) && is_array($rule['transitions'])) {
                $effect['transitions'] = $rule['transitions'];
            }
        }

        return $effect;
    }

    /**
     * @param array<string, mixed> $effect
     * @param array<string, mixed> $rule
     */
    private function matchesRule(array $effect, array $rule): bool
    {
        if (isset($rule['type']) && is_string($rule['type']) && ($effect['type'] ?? null) !== $rule['type']) {
            return false;
        }

        if (isset($rule['target']) && is_string($rule['target']) && ($effect['target'] ?? null) !== $rule['target']) {
            return false;
        }

        if (isset($rule['selector']) && is_string($rule['selector']) && ($effect['selector'] ?? null) !== $rule['selector']) {
            return false;
        }

        return true;
    }
}
