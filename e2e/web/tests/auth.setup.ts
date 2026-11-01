import { test as setup } from '@playwright/test';
import { adminStorageState, loginAdmin, type AdminRole } from './support/adminAuth';

// 管理画面のログイン（パスワード＋パスキー）は回数制限があるため、ここで一度だけ行い各テストで使い回す
for (const role of ['store', 'project', 'system'] as AdminRole[]) {
  setup(`${role} 管理者でログインする`, async ({ page }) => {
    await loginAdmin(page, role);
    await page.context().storageState({ path: adminStorageState(role) });
  });
}
