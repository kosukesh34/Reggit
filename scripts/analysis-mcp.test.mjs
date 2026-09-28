import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import readline from 'node:readline';
import { test } from 'node:test';

test('MCPで集計・クーポン一覧・配信を呼び出せる', async () => {
  const calls = [];
  const server = createServer(async (request, response) => {
    let body = '';
    for await (const chunk of request) body += chunk;
    calls.push({ method: request.method, url: request.url, body });
    response.setHeader('Content-Type', 'application/json');
    response.end(JSON.stringify({ success: true, data: request.url.endsWith('/coupons') ? { coupons: [{ id: 3, name: '来店クーポン' }] } : request.url.endsWith('/deliveries') ? { issued: 1, scheduled: 0 } : { summary: { members: 1 } } }));
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const child = spawn(process.execPath, [new URL('./analysis-mcp.mjs', import.meta.url).pathname], {
    env: { ...process.env, REGGIT_BASE_URL: `http://127.0.0.1:${server.address().port}`, REGGIT_PROJECT_KEY: 'demo', REGGIT_API_TOKEN: 'test-token' },
    stdio: ['pipe', 'pipe', 'inherit'],
  });
  const lines = readline.createInterface({ input: child.stdout });
  const iterator = lines[Symbol.asyncIterator]();
  async function rpc(id, method, params) {
    child.stdin.write(JSON.stringify({ jsonrpc: '2.0', id, method, params }) + '\n');
    return JSON.parse((await iterator.next()).value);
  }
  try {
    assert.equal((await rpc(1, 'initialize')).result.serverInfo.name, 'reggit-analysis');
    assert.equal((await rpc(2, 'tools/list')).result.tools.length, 3);
    assert.deepEqual(JSON.parse((await rpc(3, 'tools/call', { name: 'get_analysis_summary', arguments: { start: '2026-09-01', end: '2026-09-24' } })).result.content[0].text), { summary: { members: 1 } });
    assert.equal(JSON.parse((await rpc(4, 'tools/call', { name: 'list_delivery_coupons' })).result.content[0].text).coupons[0].id, 3);
    assert.equal(JSON.parse((await rpc(5, 'tools/call', { name: 'deliver_coupon_to_all', arguments: { coupon_id: 3 } })).result.content[0].text).issued, 1);
    assert.equal(calls[0].url, '/api/v1/demo/analysis/summary?start=2026-09-01&end=2026-09-24');
    assert.equal(calls[2].method, 'POST');
    assert.deepEqual(JSON.parse(calls[2].body), { coupon_id: 3, send_notification: false });
  } finally {
    child.kill();
    server.close();
  }
});
