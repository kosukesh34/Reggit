<?php

namespace App\Http\Controllers;

use App\UseCases\Contact\ContactStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private ContactStoreUseCase $storeUseCase) {}

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $this->storeUseCase->handle($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            LogHelper::error('[Contact] store failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '送信に失敗しました'], 500);
        }
    }
}
