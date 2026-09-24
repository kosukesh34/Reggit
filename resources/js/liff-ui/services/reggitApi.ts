import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
import { ensureLiffIdentityForLoad } from '../../frontend/demo/ensureIdentity';
import { isStampGrantCode } from '../../shared/utils/stampGrantToken';
import type {
  ContactInput,
  NotificationSettings,
  OperationReceipt,
  OrderInput,
  QueueInput,
  ReservationInput,
  RuntimeBootstrap,
  RuntimeCatalogItem,
  RuntimeHistoryItem,
} from '../types/runtime';
import type { CouponItem, MemberProfile, NewsItem, StoreSummary } from '../types/app';

const apiClient = new ApiClient();

function client(): ApiClient {
  return apiClient;
}

async function ensureIdentity(): Promise<void> {
  await ensureLiffIdentityForLoad({ waitNativeDevice: true });
}

function withProject(path: string): string {
  const projectKey = typeof window !== 'undefined' && window.__PROJECT_KEY__
    ? String(window.__PROJECT_KEY__)
    : '';
  if (projectKey === '') {
    return path;
  }
  const separator = path.includes('?') ? '&' : '?';
  return `${path}${separator}project_key=${encodeURIComponent(projectKey)}`;
}

function asRecord(value: unknown): Record<string, unknown> {
  return value !== null && typeof value === 'object' ? value as Record<string, unknown> : {};
}

function asArray(value: unknown): unknown[] {
  return Array.isArray(value) ? value : [];
}

function receiptFrom(reference: string, extras?: Partial<OperationReceipt>): OperationReceipt {
  return {
    reference,
    acceptedAt: new Date().toISOString(),
    status: 'accepted',
    ...extras,
  };
}

function stampGrantReceipt(payload: Record<string, unknown>): OperationReceipt {
  const stampCardId = Number(payload.stamp_card_id);
  const currentStamps = Number(payload.current_stamps);
  const grantedCount = Number(payload.granted_count);
  const cycleCount = Number(payload.cycle_count);
  const nextStampCardId = Number(payload.next_stamp_card_id);
  return receiptFrom(`STP-${String(payload.stamp_card_id ?? 'QR')}`, {
    stampCardId: Number.isFinite(stampCardId) && stampCardId > 0 ? stampCardId : undefined,
    currentStamps: Number.isFinite(currentStamps) && currentStamps >= 0 ? currentStamps : undefined,
    grantedCount: Number.isFinite(grantedCount) && grantedCount > 0 ? grantedCount : 1,
    completedNow: payload.completed_now === true,
    cycleCount: Number.isFinite(cycleCount) && cycleCount > 0 ? cycleCount : undefined,
    nextStampCardId: Number.isFinite(nextStampCardId) && nextStampCardId > 0 ? nextStampCardId : undefined,
    nextStampCardName: typeof payload.next_stamp_card_name === 'string' ? payload.next_stamp_card_name : undefined,
    message: typeof payload.message === 'string' ? payload.message : undefined,
  });
}

function mapMember(payload: Record<string, unknown>): MemberProfile {
  const member = asRecord(payload.member);
  const user = asRecord(payload.user);
  const pointsExpiry = asRecord(payload.points_expiry);
  const isMember = payload.is_member === true;
  const rawName = typeof user.name === 'string' ? user.name.trim() : '';
  const name = rawName !== '' ? rawName : (isMember ? '会員' : 'ゲスト');
  const number = String(member.member_number ?? member.memberNumber ?? '');
  return {
    name,
    number: number !== '' ? number : '未発行',
    rank: String(payload.current_rank_label ?? payload.current_rank ?? 'レギュラー'),
    points: Number(payload.points ?? member.points ?? 0) || 0,
    expiration: String(pointsExpiry.expires_at_label ?? pointsExpiry.expires_at ?? '—'),
    nextRankLabel: String(payload.next_rank_label ?? '次のランク'),
    isMember,
  };
}

function mapCoupon(row: Record<string, unknown>): CouponItem {
  return {
    id: String(row.id ?? ''),
    title: String(row.coupon_name ?? row.title ?? 'クーポン'),
    description: String(row.description ?? row.use_display ?? ''),
    validFrom: row.valid_from ? String(row.valid_from) : undefined,
    validUntil: String(row.valid_until ?? ''),
    image: row.image_url ? String(row.image_url) : undefined,
    featured: row.status === 'available',
  };
}

function mapNews(row: Record<string, unknown>): NewsItem {
  return {
    id: String(row.id ?? ''),
    title: String(row.title ?? ''),
    date: String(row.published_at ?? row.date ?? ''),
    category: row.is_pinned === true ? '重要' : 'お知らせ',
    body: String(row.body ?? ''),
    image: row.image_url ? String(row.image_url) : undefined,
    isRead: row.is_read === true,
    isPinned: row.is_pinned === true,
  };
}

function mapProduct(row: Record<string, unknown>): RuntimeCatalogItem {
  const optionGroups = asArray(row.option_groups).map((groupValue) => {
    const group = asRecord(groupValue);
    const selectionType = String(group.selection_type ?? 'single') === 'multiple' ? 'multiple' as const : 'single' as const;
    const options = asArray(group.options).map((optionValue) => {
      const option = asRecord(optionValue);
      return {
        id: String(option.id ?? ''),
        name: String(option.name ?? ''),
        priceDelta: Number(option.price_delta ?? 0),
        isDefault: option.is_default === true,
      };
    }).filter((option) => option.id !== '');
    const requiredFlag = group.is_required === true;
    const configuredMin = Number(group.min_select ?? (requiredFlag ? 1 : 0));
    const isRequired = requiredFlag || configuredMin > 0;
    const configuredMax = Number(group.max_select ?? (selectionType === 'single' ? 1 : options.length));
    return {
      id: String(group.id ?? ''),
      name: String(group.name ?? 'オプション'),
      selectionType,
      isRequired,
      minSelect: Math.max(0, Number.isFinite(configuredMin) ? configuredMin : (isRequired ? 1 : 0)),
      maxSelect: Math.max(1, Number.isFinite(configuredMax) ? configuredMax : 1),
      options,
    };
  }).filter((group) => group.id !== '' && group.options.length > 0);

  return {
    id: String(row.id ?? ''),
    name: String(row.name ?? 'メニュー'),
    description: String(row.description ?? ''),
    price: Number(row.price ?? 0) || 0,
    image: row.image_url ? String(row.image_url) : (row.image ? String(row.image) : undefined),
    category: row.category ? String(row.category) : undefined,
    isAvailable: row.is_available !== false && row.is_available !== 0 && row.is_available !== '0',
    stock: row.stock === null || row.stock === undefined || row.stock === '' ? null : Number(row.stock),
    optionGroups,
  };
}

function mapOrderHistory(row: Record<string, unknown>): RuntimeHistoryItem {
  const items = asArray(row.items).map((item) => asRecord(item));
  const firstName = String(items[0]?.product_name ?? asRecord(items[0]?.product).name ?? '注文');
  return {
    id: String(row.id ?? row.order_number ?? ''),
    type: 'order',
    date: String(row.created_at ?? ''),
    title: firstName,
    summary: String(row.order_number ?? ''),
    total: Number(row.total_amount ?? 0) || 0,
    status: String(row.status ?? ''),
  };
}

function projectStoreSummary(): StoreSummary {
  const header = asRecord(window.__FRONTEND_HEADER__);
  return {
    name: String(header.title ?? '店舗'),
    address: '',
    openHours: '',
    access: '',
    phone: '',
  };
}

async function getJson(endpoint: string): Promise<Record<string, unknown>> {
  const result = await client().get(withProject(endpoint));
  if (result.success === false) {
    throw new Error(result.error || 'データの取得に失敗しました');
  }
  return asRecord(result.data);
}

export const reggitApi = {
  /** Reggit 埋め込み時は常に API 接続対象 */
  configured: typeof window !== 'undefined',

  async bootstrap(_storeId: string): Promise<RuntimeBootstrap> {
    await ensureIdentity();
    const [memberRes, couponRes, newsRes, productRes, stampRes, queueRes, orderRes, reservationRes] = await Promise.allSettled([
      getJson(AppConstants.API_ENDPOINTS.MEMBER_PROFILE),
      getJson(AppConstants.API_ENDPOINTS.MEMBER_COUPONS),
      getJson(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS),
      getJson(AppConstants.API_ENDPOINTS.PRODUCTS),
      getJson(AppConstants.API_ENDPOINTS.STAMPS),
      getJson(AppConstants.API_ENDPOINTS.CURRENT_QUEUE),
      getJson(AppConstants.API_ENDPOINTS.ORDERS),
      getJson(AppConstants.API_ENDPOINTS.RESERVATIONS_AVAILABLE_DATES),
    ]);

    if (memberRes.status === 'rejected'
      && couponRes.status === 'rejected'
      && productRes.status === 'rejected') {
      throw new Error('店舗データを取得できませんでした');
    }

    const memberPayload = memberRes.status === 'fulfilled' ? memberRes.value : {};
    const couponsPayload = couponRes.status === 'fulfilled' ? couponRes.value : {};
    const newsPayload = newsRes.status === 'fulfilled' ? newsRes.value : {};
    const productPayload = productRes.status === 'fulfilled' ? productRes.value : {};
    const stampPayload = stampRes.status === 'fulfilled' ? stampRes.value : {};
    const queuePayload = queueRes.status === 'fulfilled' ? queueRes.value : {};
    const queueEntry = asRecord(queuePayload.queue_entry);
    const orderPayload = orderRes.status === 'fulfilled' ? orderRes.value : {};
    const reservationPayload = reservationRes.status === 'fulfilled' ? reservationRes.value : {};

    const coupons = asArray(couponsPayload.coupons).map((row) => mapCoupon(asRecord(row)));
    const usedCouponIds = coupons
      .filter((_, index) => {
        const raw = asRecord(asArray(couponsPayload.coupons)[index]);
        return raw.status === 'used' || raw.status === 'expired';
      })
      .map((coupon) => coupon.id);

    const stampCards = asArray(stampPayload.stamp_cards).map((row) => asRecord(row));
    const stampCount = stampCards.reduce((sum, card) => sum + (Number(card.current_stamps) || 0), 0);

    const products = asArray(productPayload.products).map((row) => mapProduct(asRecord(row)));
    const ordersRaw = orderPayload.data !== undefined ? asArray(orderPayload.data) : asArray(orderPayload.orders ?? orderPayload);
    const history = ordersRaw.map((row) => mapOrderHistory(asRecord(row)));

    const availableDates = asArray(reservationPayload.available_dates).map((row) => asRecord(row));
    const dates = availableDates.map((row) => String(row.date ?? '')).filter(Boolean);
    const times = asArray(availableDates[0]?.time_slots).map((slot) => String(slot));

    return {
      member: mapMember(memberPayload),
      coupons: coupons.filter((coupon) => coupon.id !== ''),
      news: asArray(newsPayload.announcements).map((row) => mapNews(asRecord(row))),
      stores: [projectStoreSummary()],
      usedCouponIds,
      stampCount,
      notificationCount: asArray(newsPayload.announcements).filter((row) => asRecord(row).is_read !== true).length,
      commerce: products.length > 0
        ? {
            items: products,
            pickupEstimate: '最短でご用意します',
            dineInDetail: '店内注文',
          }
        : undefined,
      reservationAvailability: dates.length > 0
        ? {
            dates,
            times: times.length > 0 ? times : ['11:00', '12:00', '13:00', '18:00', '19:00'],
            partySizes: [1, 2, 3, 4, 5, 6],
          }
        : undefined,
      queueStatus: {
        waitingGroups: Number(queueEntry.waiting_count ?? queuePayload.waiting_count ?? 0) || 0,
        estimatedMinutes: Number(queueEntry.estimated_wait_minutes ?? queuePayload.estimated_wait_minutes ?? 0) || 0,
      },
      history,
    };
  },

  async createOrder(input: OrderInput): Promise<OperationReceipt> {
    await ensureIdentity();
    const orderType = input.fulfillment === 'dine_in' ? 'in_store' : 'mobile';
    const paymentMethod = input.fulfillment === 'dine_in'
      ? 'cash'
      : (input.paymentMethod === 'cash' ? 'cash' : 'stripe');
    const isStripe = paymentMethod === 'stripe';
    const items = input.lines.map((line) => {
      const productId = Number(line.itemId);
      if (!Number.isFinite(productId) || productId <= 0) {
        throw new Error('注文できる商品が見つかりません。画面を再読み込みしてください');
      }
      return {
        product_id: productId,
        quantity: line.quantity,
        notes: line.notes ?? null,
        option_ids: (line.optionIds ?? []).map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0),
      };
    });
    if (items.length === 0) {
      throw new Error('カートが空です');
    }
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.ORDERS), {
      items,
      payment_method: paymentMethod,
      points_used: 0,
      notes: input.tableNumber ? `卓番: ${input.tableNumber}` : null,
      order_type: orderType,
    });
    if (result.success === false) {
      throw new Error(result.error || '注文に失敗しました');
    }
    const data = asRecord(result.data);
    const order = asRecord(data.order);
    const orderId = Number(order.id);
    return receiptFrom(String(order.order_number ?? order.id ?? data.message ?? 'ORDER'), {
      orderId: Number.isFinite(orderId) && orderId > 0 ? orderId : undefined,
      totalAmount: Number(order.total_amount ?? input.total) || input.total,
      needsPayment: isStripe,
    });
  },

  async createReservation(input: ReservationInput): Promise<OperationReceipt> {
    const datePart = /^\d{4}-\d{2}-\d{2}/.test(input.dateLabel)
      ? input.dateLabel.slice(0, 10)
      : input.dateLabel;
    const timePart = input.time.length === 5 ? `${input.time}:00` : input.time;
    const reservedAt = `${datePart} ${timePart}`.trim();
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.RESERVATIONS), {
      reserved_at: reservedAt,
      number_of_people: input.partySize,
      notes: input.plan || null,
    });
    if (result.success === false) {
      throw new Error(result.error || '予約に失敗しました');
    }
    const data = asRecord(result.data);
    const reservation = asRecord(data.reservation ?? data);
    return receiptFrom(String(reservation.reservation_number ?? reservation.id ?? 'RSV'));
  },

  async joinQueue(input: QueueInput): Promise<OperationReceipt> {
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.QUEUE), {
      party_size: input.partySize,
    });
    if (result.success === false) {
      throw new Error(result.error || '順番待ちの受付に失敗しました');
    }
    const data = asRecord(result.data);
    const entry = asRecord(data.queue_entry);
    return receiptFrom(String(entry.formatted_queue_number ?? entry.queue_number ?? entry.id ?? 'QUE'));
  },

  async useCoupon(_storeId: string, couponId: string): Promise<OperationReceipt> {
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.MEMBER_COUPON_REDEEM(couponId)), {});
    if (result.success === false) {
      throw new Error(result.error || 'クーポンを利用できませんでした');
    }
    return receiptFrom(`CPN-${couponId}`);
  },

  async markAnnouncementRead(announcementId: string): Promise<void> {
    await ensureIdentity();
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.ANNOUNCEMENT_READ(announcementId)), {});
    if (result.success === false) {
      throw new Error(result.error || 'お知らせの既読登録に失敗しました');
    }
  },

  async addStamp(_storeId: string, staffCode: string): Promise<OperationReceipt> {
    await ensureIdentity();
    const raw = staffCode.trim();
    if (isStampGrantCode(raw)) {
      const result = await client().post(withProject(`${AppConstants.API_BASE}/stamps/grant-code/${raw.toLowerCase()}`), {});
      if (result.success === false) {
        throw new Error(('error' in result ? result.error : undefined) || 'スタンプを追加できませんでした');
      }
      return stampGrantReceipt(asRecord(result.data));
    }
    let grantUrl: URL;
    try {
      grantUrl = new URL(raw, window.location.origin);
    } catch {
      throw new Error('スタンプ付与QRを読み取ってください');
    }
    if (!/^https?:$/.test(grantUrl.protocol) || !/^\/api\/stamps\/grant\/\d+$/.test(grantUrl.pathname)) {
      throw new Error('このスタンプ付与URLは利用できません');
    }
    const postUrl = /^https?:\/\//i.test(raw) ? raw : grantUrl.href;
    const result = await client().post(postUrl, {});
    if (result.success === false) {
      throw new Error(('error' in result ? result.error : undefined) || 'スタンプを追加できませんでした');
    }
    return stampGrantReceipt(asRecord(result.data));
  },

  async redeemPoints(_storeId: string, _points: number): Promise<OperationReceipt> {
    throw new Error('ポイント交換はこの画面からは行えません。店舗スタッフまでお問い合わせください');
  },

  async saveSettings(_storeId: string, _settings: NotificationSettings): Promise<OperationReceipt> {
    return receiptFrom('SET-LOCAL');
  },

  async submitContact(_storeId: string, input: ContactInput): Promise<OperationReceipt> {
    const result = await client().post(withProject(`${AppConstants.API_BASE}/contact`), {
      name: 'LIFF会員',
      email: input.email,
      message: `[${input.category}]\n${input.message}`,
    });
    if (result.success === false) {
      throw new Error(result.error || '問い合わせの送信に失敗しました');
    }
    return receiptFrom('CNT');
  },

  async logout(): Promise<void> {
    await ensureIdentity();
    await client().post(withProject(AppConstants.API_ENDPOINTS.MEMBERS_SESSION_CLEAR), {});
    client().clearPersistedLineUserId();
    if (typeof window !== 'undefined') {
      window.__LINE_USER_ID__ = null;
      try {
        const liffSdk = window.liff as { logout?: () => void; isLoggedIn?: () => boolean } | undefined;
        if (liffSdk !== undefined && typeof liffSdk.logout === 'function' && typeof liffSdk.isLoggedIn === 'function' && liffSdk.isLoggedIn()) {
          liffSdk.logout();
        }
      } catch {
        // LIFF logout はベストエフォート
      }
    }
  },

  async withdraw(): Promise<void> {
    await ensureIdentity();
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.MEMBERS_WITHDRAW), {});
    if (result.success === false) {
      throw new Error(('error' in result ? result.error : undefined) || '退会に失敗しました');
    }
    client().clearPersistedLineUserId();
    if (typeof window !== 'undefined') {
      window.__LINE_USER_ID__ = null;
    }
  },
};
