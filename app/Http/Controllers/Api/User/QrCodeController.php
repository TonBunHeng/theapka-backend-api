<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Generate QR code for a specific guest's personalized invitation.
     */
    public function forGuest(Request $request, int $id): JsonResponse
    {
        $guest = Guest::findOrFail($id);
        $wedding = $guest->wedding;

        $url = $this->qrCodeService->forGuest($wedding->slug, $guest->token);
        $qrDataUri = $this->qrCodeService->generateDataUri($url, 300);

        return response()->json([
            'data' => [
                'guest' => new GuestResource($guest),
                'url' => $url,
                'qr_code' => $qrDataUri,
            ],
        ]);
    }

    /**
     * Generate generic QR code for the general wedding invitation link.
     */
    public function forWedding(Request $request): JsonResponse
    {
        $wedding = $request->user()->currentWedding();

        if (! $wedding) {
            return response()->json([
                'message' => 'No wedding found for current user.',
            ], 404);
        }

        $baseUrl = config('app.url', 'http://localhost');
        $url = "{$baseUrl}/invitation/{$wedding->slug}";
        $qrDataUri = $this->qrCodeService->generateDataUri($url, 300);

        return response()->json([
            'data' => [
                'url' => $url,
                'qr_code' => $qrDataUri,
            ],
        ]);
    }
}
