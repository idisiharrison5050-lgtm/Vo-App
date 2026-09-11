<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Numbers\RenewNumberAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;

class NumberLifecycleController extends Controller
{
    public function renew(Request $request, RenewNumberAction $action): JsonResponse
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:number_assignments,id'],
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey || !preg_match('/^[0-9a-fA-F-]{36}$/', $idempotencyKey)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid UUID Idempotency-Key header is required.',
            ], 422);
        }

        try {
            $order = $action->execute(
                $request->user()->id,
                (int) $data['assignment_id'],
                $idempotencyKey,
            );
        } catch (RuntimeException $exception) {
            $status = match ($exception->getMessage()) {
                'NUMBER_ASSIGNMENT_UNAVAILABLE', 'NUMBER_NOT_RENEWABLE', 'RENEWAL_OFFER_UNAVAILABLE' => 409,
                'PROVIDER_REFERENCE_MISSING' => 503,
                'INVALID_RENEWAL_PRICE' => 422,
                'Insufficient wallet funds.' => 402,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => $order->status === 'completed' ? 'Number renewed.' : 'Renewal queued.',
            'data' => [
                'order' => $order,
                'assignment' => $order->assignment,
            ],
        ], $order->status === 'completed' ? 200 : 202);
    }
}
