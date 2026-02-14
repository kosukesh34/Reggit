<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiTokenStoreRequest;
use App\Http\Requests\Admin\ApiTokenUpdateRequest;
use App\Models\ApiToken;
use App\Models\Project;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = ApiToken::with('project')->orderByDesc('id')->paginate(20);
        return view('admin.api-tokens.index', compact('tokens'));
    }

    public function create()
    {
        $projects = Project::where('is_active', true)->orderBy('name')->get();
        return view('admin.api-tokens.create', compact('projects'));
    }

    public function edit(int $id)
    {
        $token = ApiToken::with('project')->findOrFail($id);
        return view('admin.api-tokens.edit', compact('token'));
    }

    public function store(ApiTokenStoreRequest $request)
    {
        $payload = $request->toPayload();
        $project = Project::findOrFail($payload['project_id']);
        $expiresAt = $payload['expires_at'] ? new \DateTimeImmutable($payload['expires_at']) : null;
        $issued = ApiToken::issue($project, $payload['name'], $expiresAt);

        return redirect()
            ->route('system-admin.api-tokens.index')
            ->with('success', 'APIトークンを発行しました')
            ->with('issued_token_plain', $issued['plain']);
    }

    public function update(ApiTokenUpdateRequest $request, int $id)
    {
        $token = ApiToken::findOrFail($id);
        $token->update($request->toPayload());

        return redirect()->route('system-admin.api-tokens.index')->with('success', 'APIトークンを更新しました');
    }

    public function revoke(int $id)
    {
        $token = ApiToken::findOrFail($id);
        $token->update(['is_active' => false]);
        return redirect()->route('system-admin.api-tokens.index')->with('success', 'APIトークンを失効しました');
    }

    public function rotate(int $id)
    {
        $token = ApiToken::findOrFail($id);
        $token->update(['is_active' => false]);
        $project = Project::findOrFail($token->project_id);
        $issued = ApiToken::issue($project, $token->name . ' (rotated)', $token->expires_at);

        return redirect()
            ->route('system-admin.api-tokens.index')
            ->with('success', 'APIトークンを再発行しました')
            ->with('issued_token_plain', $issued['plain']);
    }
}
