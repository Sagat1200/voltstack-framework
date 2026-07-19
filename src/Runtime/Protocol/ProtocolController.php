<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use Quantum\Controllers\Controller;
use Quantum\Http\JsonResponse;
use Quantum\Http\Request;
use Quantum\Security\CsrfTokenManager;
use Quantum\Security\Exceptions\CsrfTokenMismatchException;
use Quantum\Validation\Validator;
use VoltStack\Runtime\Component\ComponentManager;

final class ProtocolController extends Controller
{
    private const INTERNAL_SYNC_ACTION = '__volt_sync__';
    private const EFFECTS_THAT_AVOID_HTML_FALLBACK = [
        'text.update',
        'html.replace',
        'dom.append',
        'dom.insert',
        'dom.remove',
        'dom.move',
        'attribute.set',
        'attribute.remove',
        'class.toggle',
        'style.set',
        'navigate',
    ];

    public function __construct(
        private readonly ComponentManager $components,
        private readonly Validator $validator,
        private readonly CsrfTokenManager $csrf,
        private readonly ActionEffectBuilder $effects,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->ensureCsrfToken($request);

        $this->validator->validate($request->request(), [
            'component' => ['required', 'string'],
            'action' => ['required', 'string'],
            'snapshot' => ['required', 'array'],
            'params' => ['array'],
            'updates' => ['array'],
        ]);

        $payload = ActionPayload::fromArray($request->request());
        $component = $this->components->hydrate(
            $payload->component(),
            $payload->snapshot(),
            $request,
        );
        $interactionMeta = $this->interactionMeta($payload);

        if ($payload->action() === self::INTERNAL_SYNC_ACTION) {
            $previousSnapshot = $this->components->dehydrate($component, $interactionMeta);
            $previousHtml = $this->components->renderRoot($component, $previousSnapshot);
            $this->components->applyUpdates($component, $payload->updates());
            $actionResult = null;
        } else {
            $this->components->applyUpdates($component, $payload->updates());
            $previousSnapshot = $this->components->dehydrate($component, $interactionMeta);
            $previousHtml = $this->components->renderRoot($component, $previousSnapshot);
            $actionResult = $this->components->callAction($component, $payload->action(), $payload->params(), $request);
        }

        $snapshot = $this->components->dehydrate($component, $interactionMeta);
        $html = $this->components->renderRoot($component, $snapshot);
        $effects = $this->effects->build($actionResult, $previousHtml, $html);
        $response = (new ActionResponse(
            $payload->component(),
            $this->shouldIncludeHtmlFallback($effects) ? $html : null,
            $snapshot,
            $effects,
            $interactionMeta,
        ))->toArray();

        return $this->json($response);
    }

    /**
     * @param array<int, array<string, mixed>> $effects
     */
    private function shouldIncludeHtmlFallback(array $effects): bool
    {
        if ($effects === []) {
            return true;
        }

        foreach ($effects as $effect) {
            if (! is_string($effect['type'] ?? null)) {
                return true;
            }

            if (! in_array($effect['type'], self::EFFECTS_THAT_AVOID_HTML_FALLBACK, true)) {
                return true;
            }
        }

        return false;
    }

    private function ensureCsrfToken(Request $request): void
    {
        $token = $request->header('X-CSRF-TOKEN') ?? $request->post('_token');

        if (! is_string($token) || ! $this->csrf->verify($token)) {
            throw new CsrfTokenMismatchException('CSRF token mismatch.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function interactionMeta(ActionPayload $payload): array
    {
        return array_replace($payload->snapshot()->meta(), [
            'action' => $payload->action(),
        ]);
    }
}
