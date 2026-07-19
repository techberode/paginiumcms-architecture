<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin OTP workflow verification (Iteration 41 — comment/publish approval).
 */
final class WorkflowController
{
    public function __construct(
        private OtpWorkflowService $otpWorkflow,
        private JsonResponder $json
    ) {
    }

    /**
     * POST /api/admin/workflows/otp/verify
     */
    public function verifyOtp(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $editor = $request->getAttribute('user');
        if (!$editor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !isset($data['challenge_id']) || !isset($data['code'])) {
            return $this->json->error($response, 'challenge_id a code sú povinné', 400);
        }

        try {
            $result = $this->otpWorkflow->verifyAdminOtp(
                (string) $data['challenge_id'],
                (string) $data['code'],
                $editor
            );

            return $this->json->respond($response, array_merge([
                'success' => true,
                'message' => 'Akcia bola úspešne potvrdená',
            ], $result));
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/admin/workflows/otp/resend
     */
    public function resendOtp(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $editor = $request->getAttribute('user');
        if (!$editor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !isset($data['challenge_id'])) {
            return $this->json->error($response, 'challenge_id je povinný', 400);
        }

        try {
            $result = $this->otpWorkflow->resendAdminOtp((string) $data['challenge_id'], $editor);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Nový overovací kód bol odoslaný',
                'challenge_id' => $result['challenge_id'],
                'expires_at' => $result['expires_at'],
                'debug_code' => $result['debug_code'] ?? null,
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }
}
