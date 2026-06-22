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

    public function __construct(
        private readonly ComponentManager $components,
        private readonly Validator $validator,
        private readonly CsrfTokenManager $csrf,
        private readonly ActionEffectBuilder $effects,
    ) {
    }

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

        if ($payload->action() === self::INTERNAL_SYNC_ACTION) {
            $previousSnapshot = $this->components->dehydrate($component, [
                'action' => $payload->action(),
            ]);
            $previousHtml = $this->components->renderRoot($component, $previousSnapshot);
            $this->components->applyUpdates($component, $payload->updates());
            $actionResult = null;
        } else {
            $this->components->applyUpdates($component, $payload->updates());
            $previousSnapshot = $this->components->dehydrate($component, [
                'action' => $payload->action(),
            ]);
            $previousHtml = $this->components->renderRoot($component, $previousSnapshot);
            $actionResult = $this->components->callAction($component, $payload->action(), $payload->params(), $request);
        }

        $snapshot = $this->components->dehydrate($component, [
            'action' => $payload->action(),
        ]);
        $html = $this->components->renderRoot($component, $snapshot);

        return $this->json((new ActionResponse(
            $payload->component(),
            $html,
            $snapshot,
            $this->effects->build($actionResult, $previousHtml, $html),
            ['action' => $payload->action()],
        ))->toArray());
    }

    private function ensureCsrfToken(Request $request): void
    {
        $token = $request->header('X-CSRF-TOKEN') ?? $request->post('_token');

        if (! is_string($token) || ! $this->csrf->verify($token)) {
            throw new CsrfTokenMismatchException('CSRF token mismatch.');
        }
    }
}
