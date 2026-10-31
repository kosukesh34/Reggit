import { expect, test, type Page } from '@playwright/test';

const loginId = process.env.E2E_ADMIN_LOGIN_ID ?? 'demo_store';
const password = process.env.E2E_ADMIN_PASSWORD ?? 'DemoStore#2026';

// 管理画面はパスワードの後にパスキーが必須。仮想認証器で登録・確認する（RP ID がホスト名になるため localhost で開く）
async function loginAdmin(page: Page): Promise<string> {
  const client = await page.context().newCDPSession(page);
  await client.send('WebAuthn.enable');
  await client.send('WebAuthn.addVirtualAuthenticator', {
    options: {
      protocol: 'ctap2',
      transport: 'internal',
      hasResidentKey: true,
      hasUserVerification: true,
      isUserVerified: true,
      automaticPresenceSimulation: true,
    },
  });

  await page.goto('/admin/login');
  await page.locator('#login_id').fill(loginId);
  await page.locator('#password').fill(password);
  await page.getByRole('button', { name: 'ログイン' }).click();
  await page.waitForURL(/\/admin\/(passkey\/(enroll|verify)|[^/]+\/dashboard)/);
  if (page.url().includes('/passkey/enroll')) {
    await page.getByLabel('端末名').fill('E2E');
    await page.getByRole('button', { name: 'パスキーを作成して続行' }).click();
  } else if (page.url().includes('/passkey/verify')) {
    // 確認画面は開くと自動でパスキー認証を始める。進まないときだけボタンを押す
    await page.waitForURL(/\/admin\/[^/]+\/dashboard/, { timeout: 3000 })
      .catch(() => page.getByRole('button', { name: 'パスキーで続行' }).click());
  }
  await expect(page).toHaveURL(/\/admin\/[^/]+\/dashboard/);

  return new URL(page.url()).pathname.replace(/\/dashboard$/, '');
}

// 見た目用のカードで覆われたラジオは、ラベルを押して選ぶ
async function choose(page: Page, name: string, value: string): Promise<void> {
  const radio = page.locator(`input[name="${name}"][value="${value}"]`);
  await page.locator('label', { has: radio }).click();
  await expect(radio).toBeChecked();
}

// 配信対象・タイミング・条件・会員選択の連動（お知らせ・クーポン配信で共通の部品）
async function exerciseAudienceControls(page: Page): Promise<void> {
  // 全会員: 対象者プレビューを取得してから人数が出る
  await expect(page.locator('#audiencePreviewCount')).toHaveText(/対象 [\d,]+人/);
  await expect(page.locator('#deliverySubmitLabel')).toContainText('配信内容を確認');

  // 予約配信に切り替えると日時欄と予約ラベルになる
  await choose(page, 'delivery_timing', 'scheduled');
  await expect(page.locator('#scheduledBlock')).toBeVisible();
  await expect(page.locator('#scheduled_at')).toHaveAttribute('required', '');
  await expect(page.locator('#deliverySubmitLabel')).toContainText('予約内容を確認');
  await choose(page, 'delivery_timing', 'immediate');
  await expect(page.locator('#scheduledBlock')).toBeHidden();

  // 条件で絞る: 条件を追加するとチップと人数が出て、×で外せる
  await choose(page, 'audience_type', 'segment');
  await expect(page.locator('#segmentBlock')).toBeVisible();
  await page.locator('#segmentAddCondition').click();
  await page.locator('[data-menu-group="attribute"]').click();
  await page.locator('[data-menu-field="sex"]').click();
  await page.locator('#segment_sex').selectOption('1');
  await expect(page.locator('#deliverySummaryConditions .ad-condition-text')).toHaveText(['性別は 男性']);
  await expect(page.locator('#segmentPreview')).toHaveText(/対象 [\d,]+人/);
  await expect(page.locator('#segmentChoiceCount')).toHaveText(/[\d,]+人/);
  await expect(page.locator('input[name="audience_type"][value="segment"]').locator('xpath=ancestor::label')).toHaveClass(/is-selected/);
  await page.locator('#deliverySummaryConditions .ad-condition-remove').click();
  await expect(page.locator('#deliverySummaryConditions .ad-condition-chip')).toHaveCount(0);
  await expect(page.locator('#segment_sex')).toHaveValue('');

  // 条件なしで確認しようとするとエラー
  await page.locator('#deliverySubmitButton').click();
  await expect(page.locator('#deliveryFormError')).toHaveText('セグメント配信では、絞り込み条件を1つ以上指定してください。');

  // 会員を選ぶ: 未選択ではエラー、検索して追加・削除できる
  await choose(page, 'audience_type', 'personal');
  await expect(page.locator('#personalTargetBlock')).toBeVisible();
  await page.locator('#deliverySubmitButton').click();
  await expect(page.locator('#deliveryFormError')).toHaveText('配信する会員を1人以上選択してください。');
  await page.locator('#memberSearch').fill('1');
  const firstResult = page.locator('#memberSearchResults .ad-member-result').first();
  await expect(firstResult).toBeVisible();
  await firstResult.click();
  await expect(page.locator('#selectedMembers .ad-member-tag')).toHaveCount(1);
  await expect(page.locator('#targetMemberIdsHidden input[name="target_member_ids[]"]:not([disabled])')).toHaveCount(1);
  await expect(page.locator('#deliverySubmitLabel')).toHaveText('1人への配信内容を確認');
  await expect(page.locator('#audiencePreviewCount')).toHaveText('対象 1人');
  await page.locator('#selectedMembers .ad-member-tag button').click();
  await expect(page.locator('#selectedMembers .ad-selected-members-empty')).toBeVisible();
  await expect(page.locator('#deliverySubmitLabel')).toHaveText('配信内容を確認する');

  await choose(page, 'audience_type', 'immediate_bulk');
  await expect(page.locator('#audiencePreviewCount')).toHaveText(/対象 [\d,]+人/);
}

test.describe('お知らせ・クーポン配信画面', () => {
  test.describe.configure({ mode: 'serial' });
  test.skip(process.env.E2E_SKIP_ADMIN === '1', 'E2E_SKIP_ADMIN=1');

  // パスキー確認には回数制限があるため、ログインは1回だけにしてページを使い回す
  let page: Page;
  let prefix: string;
  test.beforeAll(async ({ browser }, testInfo) => {
    page = await browser.newPage({ baseURL: testInfo.project.use.baseURL });
    prefix = await loginAdmin(page);
  });
  test.afterAll(async () => {
    await page.close();
  });

  test('クーポン配信: 対象・条件・会員選択が連動し、確認モーダルにクーポンが出る', async () => {
    await page.goto(`${prefix}/coupons/bulk-issue`);
    await expect(page.getByRole('heading', { name: 'クーポン配信' })).toBeVisible();

    await exerciseAudienceControls(page);

    // クーポン未選択では確認に進めない
    await page.locator('#deliverySubmitButton').click();
    await expect(page.locator('#deliveryFormError')).toHaveText('配布するクーポンを選択してください。');

    const coupon = page.locator('input[name="delivery_coupon_ids[]"]:not([disabled]), input[name="coupon_id"]:not([disabled])').first();
    test.skip(await coupon.count() === 0, '配布できるクーポンがない');
    await coupon.check({ force: true });
    const couponName = (await coupon.getAttribute('data-name')) ?? '';
    await expect(page.locator('#audiencePreviewCount')).toHaveText(/対象 [\d,]+人/);

    await page.locator('#deliverySubmitButton').click();
    const modal = page.locator('#deliveryReviewModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#deliveryReviewCoupon')).toContainText(couponName);
    await expect(page.locator('#deliveryReviewAudienceLabel')).toContainText('人（全会員）');
    await expect(page.locator('#deliveryReviewTiming')).toHaveText('今すぐ');
    await expect(page.locator('#deliveryReviewConfirmLabel')).toHaveText('この内容で今すぐ配信する');
    await expect(page.locator('#deliveryReviewWarningTitle')).toHaveText('確認後すぐに配信します');
    await expect(page.locator('#deliveryReviewWarningText')).toContainText('クーポンを配布');
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
  });

  test('お知らせ配信: 文字数・対象の連動と確認モーダル', async () => {
    const response = await page.goto(`${prefix}/announcements/delivery`);
    test.skip(response?.status() === 403, 'このプロジェクトのプランではお知らせ配信を使えない');

    await page.locator('#title').fill('E2Eテスト');
    await expect(page.locator('#announcementTitleCounter')).toHaveText('6 / 200');
    await page.locator('#body').fill('本文です');
    await expect(page.locator('#announcementBodyCounter')).toHaveText('4 / 5,000');

    await exerciseAudienceControls(page);

    await page.locator('#deliverySubmitButton').click();
    const modal = page.locator('#deliveryReviewModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#deliveryReviewPinned')).toHaveText('しない');
    await expect(page.locator('#deliveryReviewCoupon')).toHaveText('配布しない');
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
  });

  test('会員一覧: 条件ビルダーで条件を足すと条件の文が出る', async () => {
    await page.goto(`${prefix}/members`);
    const form = page.locator('form[data-segment-form]');
    await expect(form).toBeVisible();

    await form.locator('#segmentAddCondition').click();
    await form.locator('[data-menu-group="attribute"]').click();
    await form.locator('[data-menu-field="sex"]').click();
    await form.locator('#segment_sex').selectOption('2');
    await expect(page.locator('#deliverySummaryConditions .ad-condition-text')).toHaveText(['性別は 女性']);
    await expect(page.locator('#deliverySummaryConditionBlock')).toBeVisible();
    await form.locator('.ad-cond-card-remove').click();
    await expect(page.locator('#deliverySummaryConditions .ad-condition-chip')).toHaveCount(0);
    await expect(page.locator('#deliverySummaryConditionBlock')).toBeHidden();
  });
});
