<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NoticeMarkdown;
use App\UseCases\Admin\SystemNotice\SystemNoticeManageUseCase;
use Illuminate\Http\Request;

class SystemNoticeController extends Controller
{
    public function __construct(private SystemNoticeManageUseCase $notices) {}

    public function index()
    {
        return view('admin.system-notices', ['notices' => $this->notices->paginate()]);
    }

    private function payload(Request $request): array
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string|max:20000', 'is_published' => 'nullable|boolean', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after:starts_at']);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    public function preview(Request $request, NoticeMarkdown $markdown)
    {
        $data = $request->validate(['body' => 'nullable|string|max:20000']);

        return response()->json(['html' => $markdown->toHtml($data['body'] ?? '')]);
    }

    public function store(Request $request)
    {
        $this->notices->create($this->payload($request));

        return back()->with('success', 'お知らせを作成しました');
    }

    public function update(Request $request, int $id)
    {
        $this->notices->update($id, $this->payload($request));

        return back()->with('success', 'お知らせを更新しました');
    }

    public function destroy(int $id)
    {
        $this->notices->delete($id);

        return back()->with('success', 'お知らせを削除しました');
    }
}
