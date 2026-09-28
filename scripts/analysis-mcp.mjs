#!/usr/bin/env node
import readline from 'node:readline';

const baseUrl = process.env.REGGIT_BASE_URL ?? 'http://localhost:8080';
const projectKey = process.env.REGGIT_PROJECT_KEY;
const token = process.env.REGGIT_API_TOKEN;
const tools = [
  {
    name: 'get_analysis_summary',
    description: '店舗の指定期間の集計を取得する。個人情報は返さない。',
    inputSchema: {
      type: 'object',
      properties: { start: { type: 'string', format: 'date' }, end: { type: 'string', format: 'date' } },
      required: ['start', 'end'], additionalProperties: false,
    },
  },
  {
    name: 'list_delivery_coupons',
    description: '配信可能なクーポンのIDと名前を取得する。',
    inputSchema: { type: 'object', properties: {}, additionalProperties: false },
  },
  {
    name: 'deliver_coupon_to_all',
    description: '指定クーポンを全会員へ即時配信する。実際の配信を伴うため、利用者の明示的な承認を得てから呼び出す。',
    inputSchema: {
      type: 'object',
      properties: {
        coupon_id: { type: 'integer', minimum: 1 },
        send_notification: { type: 'boolean', description: '通知も送信するか。既定値はfalse。' },
      },
      required: ['coupon_id'], additionalProperties: false,
    },
  },
];

function reply(id, result) {
  process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id, result }) + '\n');
}

function error(id, code, message) {
  process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id, error: { code, message } }) + '\n');
}

async function api(path, options = {}) {
  if (!projectKey || !token) throw new Error('REGGIT_PROJECT_KEY と REGGIT_API_TOKEN が必要です');
  const url = new URL(`/api/v1/${encodeURIComponent(projectKey)}/analysis/${path}`, baseUrl);
  const response = await fetch(url, {
    ...options,
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) },
  });
  const body = await response.json();
  if (!response.ok || !body.success) throw new Error(body.message ?? `HTTP ${response.status}`);
  return body.data;
}

async function callTool(name, args) {
  if (name === 'get_analysis_summary') {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(args.start ?? '') || !/^\d{4}-\d{2}-\d{2}$/.test(args.end ?? '')) throw new Error('start と end は YYYY-MM-DD で指定してください');
    return api(`summary?start=${encodeURIComponent(args.start)}&end=${encodeURIComponent(args.end)}`);
  }
  if (name === 'list_delivery_coupons') return api('coupons');
  if (name === 'deliver_coupon_to_all') {
    if (!Number.isSafeInteger(args.coupon_id) || args.coupon_id < 1) throw new Error('coupon_id は正の整数で指定してください');
    if (args.send_notification !== undefined && typeof args.send_notification !== 'boolean') throw new Error('send_notification は真偽値で指定してください');
    return api('deliveries', { method: 'POST', body: JSON.stringify({ coupon_id: args.coupon_id, send_notification: args.send_notification ?? false }) });
  }
  throw new Error(`Unknown tool: ${name}`);
}

const lines = readline.createInterface({ input: process.stdin, crlfDelay: Infinity });
for await (const line of lines) {
  let request;
  try { request = JSON.parse(line); } catch { error(null, -32700, 'Parse error'); continue; }
  const { id, method, params = {} } = request;
  if (id === undefined) continue;
  try {
    if (method === 'initialize') {
      reply(id, { protocolVersion: '2025-03-26', capabilities: { tools: {} }, serverInfo: { name: 'reggit-analysis', version: '1.0.0' } });
    } else if (method === 'tools/list') {
      reply(id, { tools });
    } else if (method === 'tools/call') {
      const data = await callTool(params.name, params.arguments ?? {});
      reply(id, { content: [{ type: 'text', text: JSON.stringify(data) }] });
    } else {
      error(id, -32601, 'Method not found');
    }
  } catch (cause) {
    if (method === 'tools/call') reply(id, { content: [{ type: 'text', text: cause.message }], isError: true });
    else error(id, -32603, cause.message);
  }
}
