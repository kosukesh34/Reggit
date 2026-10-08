import { expect, test } from '@playwright/test';

for (const device of [
  { name: 'iOS', wallet: 'Apple Pay', other: 'Google Pay', userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1' },
  { name: 'Android', wallet: 'Google Pay', other: 'Apple Pay', userAgent: 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 Chrome/130.0.0.0 Mobile Safari/537.36' },
]) {
test.describe(device.name, () => {
test.use({ userAgent: device.userAgent });
for (const width of [320, 390]) {
test(`${width}px: 4ブランドを2列で選択でき、現金は受け取り時支払いになる`, async ({ page }) => {
  await page.setViewportSize({ width, height: 844 });
  await page.goto('/demo/liff#/mobileOrder');
  await page.getByRole('button', { name: '季節のおすすめの詳細' }).click();
  await page.getByRole('button', { name: 'カートに追加', exact: true }).click();
  await page.getByRole('button', { name: '注文を確認', exact: true }).click();
  const picker = page.getByRole('group', { name: 'お支払い方法' });
  await expect(picker.getByRole('button')).toHaveCount(4);
  const images = picker.locator('img');
  await expect(images).toHaveCount(device.wallet === 'Google Pay' ? 3 : 2);
  await expect(picker.getByRole('button', { name: new RegExp(device.other) })).toHaveCount(0);
  await expect.poll(() => images.evaluateAll((nodes) => nodes.every((node) =>
    node instanceof HTMLImageElement && node.complete && node.naturalWidth > 0 &&
    new URL(node.src).origin === window.location.origin && new URL(node.src).pathname.startsWith('/images/payments/'),
  ))).toBe(true);
  for (const name of ['クレジットカード', 'PayPay', device.wallet, '現金']) {
    const option = picker.getByRole('button', { name: new RegExp(name) });
    await option.click();
    await expect(option).toHaveAttribute('aria-pressed', 'true');
    await expect(picker.locator('[aria-pressed="true"]')).toHaveCount(1);
  }
  await expect(page.getByText('商品受け取り時に現金でお支払いください')).toBeVisible();
  const boxes = await picker.getByRole('button').evaluateAll((nodes) => nodes.map((node) => {
    const { x, y, width } = node.getBoundingClientRect(); return { x, y, width };
  }));
  expect(boxes[0].y).toBe(boxes[1].y);
  expect(boxes[2].y).toBe(boxes[3].y);
  expect(boxes[2].y).toBeGreaterThan(boxes[0].y);
  expect(boxes[0].width).toBeGreaterThan(100);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  if (width === 390) await page.screenshot({ path: `/private/tmp/reggit-payments-${device.name}.png`, fullPage: true });
});

}

});
}
