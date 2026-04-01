<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\WalletPassService;
use App\Services\WalletPassSyncService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Apple PassKit Web Service (v1)
 * https://developer.apple.com/documentation/walletpasses/adding-a-web-service-to-update-passes
 */
class WalletPassWebServiceController extends Controller
{
    public function __construct(
        private readonly WalletPassSyncService $walletPassSyncService,
        private readonly WalletPassService $walletPassService,
    ) {}

    public function registerDevice(Request $request, string $project_key, string $deviceLibraryIdentifier, string $passTypeIdentifier, string $serialNumber): Response
    {
        $project = $this->resolveProject($project_key);
        if ($project === null) {
            return response('Project not found', 404);
        }

        $pass = $this->walletPassSyncService->findPassByTypeAndSerial((int) $project->id, $passTypeIdentifier, $serialNumber);
        if ($pass === null) {
            return response('Pass not found', 404);
        }

        if (! $this->isAuthorized($request, $pass->authentication_token)) {
            return response('Unauthorized', 401);
        }

        $pushToken = (string) $request->input('pushToken', '');
        $pushToken = trim($pushToken);
        if ($pushToken === '') {
            return response('pushToken is required', 400);
        }

        $created = $this->walletPassSyncService->registerDevice($pass, $deviceLibraryIdentifier, $pushToken);

        return response('', $created ? 201 : 200);
    }

    public function unregisterDevice(Request $request, string $project_key, string $deviceLibraryIdentifier, string $passTypeIdentifier, string $serialNumber): Response
    {
        $project = $this->resolveProject($project_key);
        if ($project === null) {
            return response('Project not found', 404);
        }

        $pass = $this->walletPassSyncService->findPassByTypeAndSerial((int) $project->id, $passTypeIdentifier, $serialNumber);
        if ($pass === null) {
            return response('', 200);
        }

        if (! $this->isAuthorized($request, $pass->authentication_token)) {
            return response('Unauthorized', 401);
        }

        $this->walletPassSyncService->unregisterDevice($pass, $deviceLibraryIdentifier);

        return response('', 200);
    }

    public function listSerials(Request $request, string $project_key, string $deviceLibraryIdentifier, string $passTypeIdentifier): Response|JsonResponse
    {
        $project = $this->resolveProject($project_key);
        if ($project === null) {
            return response('Project not found', 404);
        }

        $result = $this->walletPassSyncService->listUpdatedSerials(
            (int) $project->id,
            $deviceLibraryIdentifier,
            $passTypeIdentifier,
            $request->query('passesUpdatedSince')
        );

        if ($result === null) {
            return response('', 204);
        }

        return response()->json($result);
    }

    public function getLatestPass(Request $request, string $project_key, string $passTypeIdentifier, string $serialNumber): Response
    {
        $project = $this->resolveProject($project_key);
        if ($project === null) {
            return response('Project not found', 404);
        }

        $pass = $this->walletPassSyncService->findPassByTypeAndSerial((int) $project->id, $passTypeIdentifier, $serialNumber);
        if ($pass === null) {
            return response('Pass not found', 404);
        }

        if (! $this->isAuthorized($request, $pass->authentication_token)) {
            return response('Unauthorized', 401);
        }

        AdminContext::setCurrentProject($project);
        $user = User::where('id', (int) $pass->user_id)
            ->where('project_id', (int) $project->id)
            ->first();
        if ($user === null) {
            return response('User not found', 404);
        }

        $content = $this->walletPassService->generateFromUser($user);
        if ($content === null) {
            return response('Wallet pass build failed', 503);
        }

        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="membership.pkpass"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Last-Modified' => ($pass->last_updated_at ?? $pass->updated_at ?? now())->toRfc7231String(),
        ]);
    }

    public function log(Request $request, string $project_key): Response
    {
        // Apple Wallet 側ログ受け口（必要最小限で 200 を返す）
        logger()->info('apple-wallet passkit log', [
            'project_key' => $project_key,
            'logs' => $request->input('logs', []),
        ]);

        return response('', 200);
    }

    private function isAuthorized(Request $request, string $authenticationToken): bool
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'ApplePass ')) {
            return false;
        }
        $token = trim(substr($header, strlen('ApplePass ')));

        return $token !== '' && hash_equals($authenticationToken, $token);
    }

    private function resolveProject(string $projectKey): ?Project
    {
        return Project::where('project_key', $projectKey)
            ->where('is_active', true)
            ->first();
    }
}
