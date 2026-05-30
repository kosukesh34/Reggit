<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\WalletPassRegistration;
use App\Models\WalletPassUpdate;
use App\Services\WalletPassAuthTokenService;
use App\Services\WalletPassService;
use App\Services\WalletPassSyncService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppleWalletPasskitController extends Controller
{
    public function __construct(
        private WalletPassAuthTokenService $authTokenService,
        private WalletPassService $walletPassService,
        private WalletPassSyncService $walletPassSyncService
    ) {}

    public function registerDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        $member = $this->resolveMember($passTypeIdentifier, $serialNumber);
        if ($member === null || ! $this->authorized($request, $member)) {
            return response('', 401);
        }

        $pushToken = trim((string) $request->input('pushToken', ''));
        if ($pushToken === '') {
            return response('', 400);
        }

        WalletPassRegistration::query()->updateOrCreate(
            [
                'pass_type_identifier' => $passTypeIdentifier,
                'serial_number' => $serialNumber,
                'device_library_identifier' => $deviceLibraryIdentifier,
            ],
            [
                'project_id' => (int) $member->project_id,
                'member_id' => (int) $member->id,
                'push_token' => $pushToken,
            ]
        );

        $this->walletPassSyncService->markMemberUpdated($member);

        return response('', 201);
    }

    public function unregisterDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        $member = $this->resolveMember($passTypeIdentifier, $serialNumber);
        if ($member === null || ! $this->authorized($request, $member)) {
            return response('', 401);
        }

        WalletPassRegistration::query()
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->where('device_library_identifier', $deviceLibraryIdentifier)
            ->delete();

        return response('', 200);
    }

    public function listUpdatedSerials(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier
    ): Response {
        if (! $this->authorizedForDevice($request, $deviceLibraryIdentifier, $passTypeIdentifier)) {
            return response('', 401);
        }

        $since = $request->query('passesUpdatedSince');
        $query = WalletPassUpdate::query()
            ->join('wallet_pass_registrations as r', function ($join) use ($deviceLibraryIdentifier, $passTypeIdentifier) {
                $join->on('wallet_pass_updates.serial_number', '=', 'r.serial_number')
                    ->on('wallet_pass_updates.pass_type_identifier', '=', 'r.pass_type_identifier')
                    ->where('r.device_library_identifier', '=', $deviceLibraryIdentifier)
                    ->where('r.pass_type_identifier', '=', $passTypeIdentifier);
            })
            ->where('wallet_pass_updates.pass_type_identifier', $passTypeIdentifier)
            ->select('wallet_pass_updates.serial_number', 'wallet_pass_updates.updated_at')
            ->orderBy('wallet_pass_updates.updated_at');

        if (is_string($since) && $since !== '') {
            $query->where('wallet_pass_updates.updated_at', '>', $since);
        }

        $rows = $query->get();
        $lastUpdated = $rows->max('updated_at');

        return response()->json([
            'serialNumbers' => $rows->pluck('serial_number')->unique()->values()->all(),
            'lastUpdated' => $lastUpdated ? (string) $lastUpdated : now()->toIso8601String(),
        ]);
    }

    public function getLatestPass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        $member = $this->resolveMember($passTypeIdentifier, $serialNumber);
        if ($member === null || ! $this->authorized($request, $member)) {
            return response('', 401);
        }

        $content = $this->walletPassService->generateForMember($member);
        if ($content === null) {
            return response('pass generate failed', 500, ['Content-Type' => 'text/plain']);
        }

        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="membership.pkpass"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function resolveMember(string $passTypeIdentifier, string $serialNumber): ?Member
    {
        if ($passTypeIdentifier !== (string) config('wallet_pass.type_id', '')) {
            return null;
        }
        if (! str_starts_with($serialNumber, 'member-')) {
            return null;
        }
        $memberId = (int) substr($serialNumber, strlen('member-'));
        if ($memberId <= 0) {
            return null;
        }

        return Member::query()->with(['user', 'project'])->find($memberId);
    }

    private function authorized(Request $request, Member $member): bool
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'ApplePass ')) {
            return false;
        }
        $token = trim(substr($header, strlen('ApplePass ')));
        if ($token === '') {
            return false;
        }

        return $this->authTokenService->verify($member, $token);
    }

    private function authorizedForDevice(Request $request, string $deviceLibraryIdentifier, string $passTypeIdentifier): bool
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'ApplePass ')) {
            return false;
        }
        $token = trim(substr($header, strlen('ApplePass ')));
        if ($token === '') {
            return false;
        }

        $registrations = WalletPassRegistration::query()
            ->where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->get(['member_id']);

        foreach ($registrations as $registration) {
            $member = Member::find((int) $registration->member_id);
            if ($member !== null && $this->authTokenService->verify($member, $token)) {
                return true;
            }
        }

        return false;
    }
}
