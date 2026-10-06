import type { JSX } from 'react';
import React from 'react';
import type { ProductCategory } from './types';

type CategoryTabsProps = {
    categories: ProductCategory[];
    currentCategory: string;
    onSelect: (key: string) => void;
    id?: string;
};

export function CategoryTabs({
    categories,
    currentCategory,
    onSelect,
    id = 'categoryTabs',
}: CategoryTabsProps): JSX.Element {
    const tabs = categories.length > 0
        ? categories
        : [{ key: 'all', label: 'すべて', icon: 'fa-th-large', count: 0 }];

    return (
        <div className="category-tabs products-category-tabs" id={id}>
            {tabs.map((cat) => (
                <button
                    key={cat.key}
                    type="button"
                    className={`tab-btn${cat.key === currentCategory ? ' active' : ''}`}
                    data-category={cat.key}
                    onClick={() => onSelect(cat.key)}
                >
                    <i className={`fas ${cat.icon}`} aria-hidden="true" />
                    <span>{cat.label}</span>
                    <span className="tab-count">{cat.count || 0}</span>
                </button>
            ))}
        </div>
    );
}
