import assert from 'node:assert/strict';
import test from 'node:test';
import {
    renderGuestNoCardTemplate,
    renderGuestRegisterTemplate,
    renderLoginOptionalTemplate,
    renderMemberErrorTemplate,
    renderMemberSkeleton,
} from './memberStateTemplates.ts';

const escapeHtml = (value) => value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

test('login optional template escapes dynamic content', () => {
    const html = renderLoginOptionalTemplate(escapeHtml, '<script>x</script>', '<img src=x>');
    assert.doesNotMatch(html, /<script>|<img/);
    assert.match(html, /&lt;script&gt;x&lt;\/script&gt;/);
});

test('guest register template escapes copy and class values', () => {
    const html = renderGuestRegisterTemplate(escapeHtml, {
        title: '<b>title</b>',
        subtitle: 'safe & sound',
        buttonLabel: '<登録>',
        buttonClass: 'primary" onclick="bad()',
    });
    assert.doesNotMatch(html, /<b>|onclick="bad/);
    assert.match(html, /primary&quot; onclick=&quot;bad\(\)/);
});

test('error template escapes Error messages and uses a safe fallback', () => {
    assert.match(renderMemberErrorTemplate(escapeHtml, new Error('<svg onload=bad()>')), /&lt;svg onload=bad\(\)&gt;/);
    assert.match(renderMemberErrorTemplate(escapeHtml, { message: '<unsafe>' }), /エラーが発生しました/);
});

test('static guest and skeleton templates retain their state hooks', () => {
    assert.match(renderGuestNoCardTemplate(), /member-open-register/);
    assert.match(renderMemberSkeleton(), /skeleton-shimmer/);
});
