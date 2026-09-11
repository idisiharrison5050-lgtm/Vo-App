<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Marketplace\PurchaseNumberAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class NumberOrderController extends Controller
{
    public function store(Request $request, PurchaseNumberAction $purchase): JsonResponse
    {
        $validated = $request->validate([
            'offer_id' => ['required', 'integer', 'exists:number_offers,id'],
            'auto_renew' => ['sometimes', 'boolean'],
            'custom_duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $idempotencyKey = (string) $request->header('Idempotency-Key');
        if ($idempotencyKey === '' || !Str::isUuid($idempotencyKey)) {
            return response()->json([
                'success' => false,
                'message' => 'A valid Idempotency-Key UUID is required.',
                'code' => 'IDEMPOTENCY_KEY_REQUIRED',
                'errors' => [],
            ], 400);
        }

        try {
            $order = $purchase->execute(
                $request->user()->id,
                (int) $validated['offer_id'],
                $idempotencyKey,
                (bool) ($validated['auto_renew'] ?? false),
                $validated['custom_duration_days'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return match ($exception->getMessage()) {
                'NUMBER_UNAVAILABLE' => response()->json([
                    'success' => false,
                    'message' => 'No number matching this offer is currently available.',
                    'code' => 'NUMBER_UNAVAILABLE',
                    'errors' => [],
                ], 409),
                'NUMBER_CAPABILITY_UNAVAILABLE' => response()->json([
                    'success' => false,
                    'message' => 'No available number currently supports this capability.',
                    'code' => 'NUMBER_CAPABILITY_UNAVAILABLE',
                    'errors' => [],
                ], 409),
                'PROVIDER_UNAVAILABLE' => response()->json([
                    'success' => false,
                    'message' => 'The selected number provider is temporarily unavailable.',
                    'code' => 'PROVIDER_UNAVAILABLE',
                    'errors' => [],
                ], 503),
                'AUTO_RENEW_UNSUPPORTED' => response()->json([
                    'success' => false,
                    'message' => 'Auto-renew is not supported for this offer.',
                    'code' => 'AUTO_RENEW_UNSUPPORTED',
                    'errors' => [],
                ], 422),
                'Insufficient wallet funds.' => response()->json([
                    'success' => false,
                    'message' => 'Your wallet balance is not sufficient for this purchase.',
                    'code' => 'INSUFFICIENT_FUNDS',
                    'errors' => [],
                ], 422),
                default => throw $exception,
            };
        }

        return response()->json([
            'success' => true,
            'message' => 'Number assigned successfully.',
            'data' => [
                'order' => $order,
                'assignment' => $order->assignment,
            ],
        ], 201);
    }
}
