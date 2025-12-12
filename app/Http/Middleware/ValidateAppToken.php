<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ValidateAppToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');
        $isLoginPath = $path === 'login' || $path === 'api/login';

        if ($isLoginPath) {
            return $this->validateClientToken($request, $next);
        }

        if (!Auth::check()) {
            return $next($request);
        }

        $appToken = $request->header('APP_TOKEN');

        if ($appToken === null || $appToken === '') {
            return $this->failureResponse($request, 'missing_token', 401, 'Unauthorized');
        }

        $parts = explode('.', $appToken);

        if (count($parts) !== 2) {
            return $this->failureResponse($request, 'invalid_format', 401, 'Unauthorized');
        }

        [$payloadB64, $signatureB64] = $parts;

        $payloadJson = base64_decode($payloadB64, true);

        if ($payloadJson === false) {
            return $this->failureResponse($request, 'invalid_payload_encoding', 401, 'Unauthorized');
        }

        $claims = json_decode($payloadJson, true);

        if (!is_array($claims)) {
            return $this->failureResponse($request, 'invalid_payload_structure', 401, 'Unauthorized');
        }

        $expectedSignature = base64_encode(
            hash_hmac('sha256', $payloadB64, (string) config('app.key'), true)
        );

        if (!hash_equals($expectedSignature, $signatureB64)) {
            return $this->failureResponse($request, 'invalid_signature', 401, 'Unauthorized');
        }

        $now = Carbon::now()->timestamp;

        if (isset($claims['exp']) && $claims['exp'] < $now) {
            return $this->failureResponse($request, 'expired', 403, 'Forbidden');
        }

        $userId = Auth::id();

        if (isset($claims['sub']) && (int) $claims['sub'] !== (int) $userId) {
            return $this->failureResponse($request, 'subject_mismatch', 401, 'Unauthorized');
        }

        return $next($request);
    }

    private function validateClientToken(Request $request, Closure $next): Response
    {
        $appToken = $request->header('APP_TOKEN');

        if ($appToken === null || $appToken === '') {
            return $this->failureResponse($request, 'missing_client_token', 401, 'Unauthorized');
        }

        $validTokens = config('app.client_tokens') ?? [];

        if (!in_array($appToken, $validTokens, true)) {
            return $this->failureResponse($request, 'unauthorized_client_token', 403, 'Forbidden');
        }

        return $next($request);
    }

    private function failureResponse(Request $request, string $reason, int $status, string $message): Response
    {
        Log::warning('APP_TOKEN validation failed', [
            'timestamp' => Carbon::now()->toIso8601String(),
            'ip' => $request->ip(),
            'path' => $request->path(),
            'reason' => $reason,
        ]);

        return response()->json([
            'code' => $status,
            'message' => $message,
            'data' => [],
            'pagination' => (object) [],
        ], $status);
    }
}
