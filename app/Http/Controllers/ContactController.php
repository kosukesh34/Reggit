<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $data = $request->all();

            ContactMessage::create([
                'project_id' => $projectId,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'] ?? ($data['body'] ?? null),
                'payload' => $data,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Contact store error: ' . $e->getMessage());
            return response()->json(['error' => '送信に失敗しました'], 500);
        }
    }
}
