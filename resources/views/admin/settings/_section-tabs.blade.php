<div class="settings-category-tabs" role="tablist" aria-label="{{ $tabsLabel }}" data-section-tabs hidden>
    @foreach($sectionTabs as $id => $label)
        <button type="button" id="tab-{{ $id }}" role="tab" aria-controls="{{ $id }}" aria-selected="false" data-section-target="{{ $id }}">{{ $label }}</button>
    @endforeach
</div>
@once
@push('scripts')
<script>
document.querySelectorAll('[data-section-tabs]').forEach(function (bar) {
    const tabs = Array.from(bar.querySelectorAll('[data-section-target]'));
    function select(index, focus) {
        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
            const panel = document.getElementById(tab.dataset.sectionTarget);
            panel.hidden = i !== index;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tab.id);
        });
        bar.dispatchEvent(new CustomEvent('settings-tab-change', {detail: tabs[index].dataset.sectionTarget}));
        if (focus) tabs[index].focus();
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            select(index, false);
            history.replaceState(null, '', '#'+tab.dataset.sectionTarget);
        });
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next !== undefined) { event.preventDefault(); tabs[next].click(); select(next, true); }
        });
        document.getElementById(tab.dataset.sectionTarget).addEventListener('invalid', () => select(index, false), true);
    });
    let initial = tabs.findIndex(tab => '#'+tab.dataset.sectionTarget === location.hash);
    const errorPanel = tabs.findIndex(tab => document.getElementById(tab.dataset.sectionTarget).querySelector('[data-setting-error], .invalid-feedback'));
    if (errorPanel >= 0) initial = errorPanel;
    select(initial >= 0 ? initial : 0, false);
    bar.hidden = false;
});
</script>
@endpush
@endonce
