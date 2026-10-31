<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\ShopOperations;
use App\UseCases\Shop\ShopReceiptUseCase;
use App\Util\AdminContext;
use Illuminate\Contracts\View\View;

/** 配送注文の領収書。署名つき URL（注文確認メール・管理画面）からのみ開ける。 */
class ShopReceiptController extends Controller
{
    public function __construct(
        private ShopOperations $operations,
        private ShopReceiptUseCase $receipts,
    ) {}

    public function __invoke(string $project_key, int $order): View
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        ['order' => $receipt, 'taxes' => $taxes] = $this->receipts->handle((int) $project->id, $order);

        return view('frontend.shop-receipt', [
            'order' => $receipt,
            'projectName' => (string) $project->name,
            'legal' => $this->operations->legalNotice((int) $project->id),
            'registrationNumber' => $this->operations->invoiceRegistrationNumber((int) $project->id),
            'taxes' => $taxes,
        ]);
    }
}
