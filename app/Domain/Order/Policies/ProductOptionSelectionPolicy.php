<?php

namespace App\Domain\Order\Policies;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\ValueObjects\ProductOptionChoice;
use App\Domain\Order\ValueObjects\ProductOptionGroupSpec;

final class ProductOptionSelectionPolicy
{
    /**
     * @param  list<ProductOptionGroupSpec>  $groups
     * @param  list<int>  $optionIds
     * @return array{0: float, 1: string}
     */
    public function resolve(string $productName, array $groups, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        $optionsById = $this->indexOptionsById($groups);

        /** @var array<int, list<ProductOptionChoice>> $selectedByGroupId */
        $selectedByGroupId = [];

        foreach ($optionIds as $optionId) {
            if (isset($optionsById[$optionId]) === false) {
                throw new OrderOperationException("商品 {$productName} のオプションが不正です");
            }

            $choice = $optionsById[$optionId];
            $selectedByGroupId[$choice->groupId][] = $choice;
        }

        foreach ($groups as $group) {
            $this->assertGroupSelection($productName, $group, $selectedByGroupId[$group->id] ?? []);
        }

        $delta = 0.0;
        $names = [];
        foreach ($optionIds as $optionId) {
            $choice = $optionsById[$optionId];
            $delta += $choice->priceDelta;
            $names[] = $choice->name;
        }

        return [$delta, $names === [] ? '' : implode(', ', $names)];
    }

    /**
     * @param  list<ProductOptionGroupSpec>  $groups
     * @return array<int, ProductOptionChoice>
     */
    private function indexOptionsById(array $groups): array
    {
        $indexed = [];
        foreach ($groups as $group) {
            foreach ($group->options as $option) {
                $indexed[$option->id] = $option;
            }
        }

        return $indexed;
    }

    /**
     * @param  list<ProductOptionChoice>  $selected
     */
    private function assertGroupSelection(
        string $productName,
        ProductOptionGroupSpec $group,
        array $selected,
    ): void {
        $count = count($selected);
        $minSelect = max($group->minSelect, $group->isRequired ? 1 : 0);

        if ($group->isRequired && $count === 0) {
            throw new OrderOperationException("商品 {$productName} の「{$group->name}」は必須です");
        }

        if ($count > 0 && $count < $minSelect) {
            throw new OrderOperationException("商品 {$productName} の「{$group->name}」は{$minSelect}個以上選択してください");
        }

        if ($group->selectionType === ProductOptionGroupSpec::SELECTION_SINGLE && $count > 1) {
            throw new OrderOperationException("商品 {$productName} の「{$group->name}」は1つだけ選択できます");
        }

        if ($group->selectionType === ProductOptionGroupSpec::SELECTION_MULTIPLE
            && $group->maxSelect !== null
            && $group->maxSelect > 0
            && $count > $group->maxSelect) {
            throw new OrderOperationException("商品 {$productName} の「{$group->name}」は最大{$group->maxSelect}個までです");
        }
    }
}
