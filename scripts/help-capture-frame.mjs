// Draws the red help frame above the page so overflow, stacking and backgrounds cannot hide it.
export async function highlight(page, target) {
  await target.waitFor({ state: 'visible' });
  await target.evaluate(node => {
    const fits = node.getBoundingClientRect().height < window.innerHeight - 140;
    node.style.scrollMarginTop = '140px';
    node.style.scrollMarginBottom = '24px';
    node.scrollIntoView({ block: fits ? 'center' : 'start' });
  });
  // Settings pages keep shifting briefly after a long scroll; measure only after the layout settles.
  await page.waitForTimeout(1500);
  await target.evaluate(node => {
    const rect = node.getBoundingClientRect();
    const pad = 6;
    const top = Math.max(rect.top - pad, 2);
    const left = Math.max(rect.left - pad, 2);
    // LIFF screens change only the hash, so remove the frame left by the previous capture.
    document.getElementById('help-capture-frame')?.remove();
    const frame = document.createElement('div');
    frame.id = 'help-capture-frame';
    frame.setAttribute('aria-hidden', 'true');
    frame.style.cssText = `position:fixed;z-index:2147483647;pointer-events:none;box-sizing:border-box;border:4px solid #e11d48;border-radius:8px;top:${top}px;left:${left}px;width:${Math.min(rect.right + pad, window.innerWidth - 2) - left}px;height:${Math.min(rect.bottom + pad, window.innerHeight - 2) - top}px`;
    // Attach outside <body>: a transformed or contained body would offset a fixed frame.
    document.documentElement.append(frame);
  });
}
