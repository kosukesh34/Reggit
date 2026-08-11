import { ApiClient } from '../../shared/utils/apiClient';
import { AppConstants } from '../../shared/constants';
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

function client(): ApiClient {
  const app = window.app as { apiClient?: ApiClient } | undefined;
  return app?.apiClient ?? new ApiClient();
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

function receiptFrom(reference: string): OperationReceipt {
  return {
    reference,
    acceptedAt: new Date().toISOString(),
    status: 'accepted',
  };
}

function mapMember(payload: Record<string, unknown>): MemberProfile {
  const member = asRecord(payload.member);
  const user = asRecord(payload.user);
  const pointsExpiry = asRecord(payload.points_expiry);
  const name = String(user.name ?? member.name ?? 'ゲスト');
  const number = String(member.member_number ?? member.memberNumber ?? '');
  return {
    name,
    number: number !== '' ? number : '未発行',
    rank: String(payload.current_rank_label ?? payload.current_rank ?? 'レギュラー'),
    points: Number(payload.points ?? member.points ?? 0) || 0,
    expiration: String(pointsExpiry.expires_at_label ?? pointsExpiry.expires_at ?? '—'),
    nextRankLabel: String(payload.next_rank_label ?? '次のランク'),
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
  };
}

function mapProduct(row: Record<string, unknown>): RuntimeCatalogItem {
  return {
    id: String(row.id ?? ''),
    name: String(row.name ?? 'メニュー'),
    description: String(row.description ?? ''),
    price: Number(row.price ?? 0) || 0,
    image: row.image_url ? String(row.image_url) : (row.image ? String(row.image) : undefined),
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
  if (!result.success) {
    throw new Error(result.error || 'データの取得に失敗しました');
  }
  return asRecord(result.data);
}

export const reggitApi = {
  /** Reggit 埋め込み時は常に API 接続対象 */
  configured: typeof window !== 'undefined',

  async bootstrap(_storeId: string): Promise<RuntimeBootstrap> {
    const [memberRes, couponRes, newsRes, productRes, stampRes, queueRes, orderRes, reservationRes] = await Promise.allSettled([
      getJson(AppConstants.API_ENDPOINTS.MEMBERS_ME),
      getJson(AppConstants.API_ENDPOINTS.MY_COUPONS),
      getJson(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS),
      getJson(AppConstants.API_ENDPOINTS.PRODUCTS),
      getJson(AppConstants.API_ENDPOINTS.STAMPS),
      getJson(AppConstants.API_ENDPOINTS.QUEUE_ME),
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
        waitingGroups: Number(queuePayload.waiting_count ?? 0) || 0,
        estimatedMinutes: Number(queuePayload.estimated_wait_minutes ?? 0) || 0,
      },
      history,
    };
  },

  async createOrder(input: OrderInput): Promise<OperationReceipt> {
    const orderType = input.fulfillment === 'dine_in' ? 'in_store' : 'mobile';
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.ORDERS), {
      items: input.lines.map((line) => ({
        product_id: Number(line.itemId),
        quantity: line.quantity,
        notes: null,
      })),
      payment_method: 'cash',
      points_used: 0,
      notes: input.tableNumber ? `卓番: ${input.tableNumber}` : null,
      order_type: orderType,
    });
    if (!result.success) {
      throw new Error(result.error || '注文に失敗しました');
    }
    const data = asRecord(result.data);
    const order = asRecord(data.order);
    return receiptFrom(String(order.order_number ?? order.id ?? data.message ?? 'ORDER'));
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
    if (!result.success) {
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
    if (!result.success) {
      throw new Error(result.error || '順番待ちの受付に失敗しました');
    }
    const data = asRecord(result.data);
    const entry = asRecord(data.queue_entry);
    return receiptFrom(String(entry.formatted_queue_number ?? entry.queue_number ?? entry.id ?? 'QUE'));
  },

  async useCoupon(_storeId: string, couponId: string): Promise<OperationReceipt> {
    const result = await client().post(withProject(AppConstants.API_ENDPOINTS.MY_COUPON_USE(couponId)), {});
    if (!result.success) {
      throw new Error(result.error || 'クーポンを利用できませんでした');
    }
    return receiptFrom(`CPN-${couponId}`);
  },

  async addStamp(_storeId: string, _staffCode: string): Promise<OperationReceipt> {
    throw new Error('スタンプは店舗端末から付与されます');
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
    if (!result.success) {
      throw new Error(result.error || '問い合わせの送信に失敗しました');
    }
    return receiptFrom('CNT');
  },
};
