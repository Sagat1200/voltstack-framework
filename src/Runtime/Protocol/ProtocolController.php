<?php

declare(strict_types=1);

namespace VoltStack\Runtime\Protocol;

use Quantum\Controllers\Controller;
use Quantum\Http\JsonResponse;
use Quantum\Http\Request;
use RuntimeException;
use VoltStack\Runtime\Component\ComponentManager;
use VoltStack\Runtime\Hydration\Exceptions\InvalidSnapshotException;

final class ProtocolController extends Controller
{
    public function __construct(private readonly ComponentManager $components)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = ActionPayload::fromArray($request->request());
            $component = $this->components->hydrate(
                $payload->component(),
                $payload->snapshot(),
                $request,
            );

            $this->components->callAction($component, $payload->action(), $payload->params(), $request);

            $snapshot = $this->components->dehydrate($component, [
                'action' => $payload->action(),
            ]);

            return $this->json((new ActionResponse(
                $payload->component(),
                $this->components->renderRoot($component, $snapshot),
                $snapshot,
                [],
                ['action' => $payload->action()],
            ))->toArray());
        } catch (InvalidSnapshotException|RuntimeException $exception) {
            return $this->json([
                'error' => [
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }
    }
}
