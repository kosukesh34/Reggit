// 画面が使う ChatClient を、エミュレーターの Auth / Firestore / Storage と実際のルールで動かす。
// 実行: npm run test:firebase-rules（Node の --experimental-transform-types で TypeScript を読み込む）
import { after, before, test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { initializeTestEnvironment } from "@firebase/rules-unit-testing";
import { ChatClient, isChatUnread } from "../../resources/js/shared/chat/firebaseChat.ts";

const PID = 7;
// Auth エミュレーターは署名を検証しないため、Laravel と同じ形のクレームを署名なしで渡す
function session(uid, claims, memberId) {
  const encode = (value) => Buffer.from(JSON.stringify(value)).toString("base64url");
  const now = Math.floor(Date.now() / 1000);
  const token = `${encode({ alg: "none", typ: "JWT" })}.${encode({
    iss: "chat@test", sub: "chat@test", aud: "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit",
    iat: now, exp: now + 3600, uid, claims: { pid: String(PID), until: Date.now() + 3600_000, ...claims },
  })}.`;
  return { token, project_id: PID, member_id: memberId, firebase: { apiKey: "fake", projectId: "demo-reggit", storageBucket: "demo-reggit.appspot.com", appId: "fake", emulatorHost: "127.0.0.1" } };
}

const next = (subscribe, predicate) => new Promise((resolve, reject) => {
  const stop = subscribe((value) => { if (predicate(value)) { queueMicrotask(() => stop()); resolve(value); } }, reject);
});

let env;
before(async () => {
  env = await initializeTestEnvironment({
    projectId: "demo-reggit",
    firestore: { rules: readFileSync("firestore.rules", "utf8"), host: "127.0.0.1", port: 8086 },
  });
  await env.clearFirestore();
});
after(async () => { await env?.cleanup(); });

test("会員の送信を担当店舗のスタッフが一覧で受け取り、既読にして返信できる", async () => {
  const member = await ChatClient.connect(session("m-7-5", { role: "member", mid: "5", label: "佐藤", stores: [3] }, 5));
  const outsider = await ChatClient.connect(session("m-7-6", { role: "member", mid: "6", label: "他人", stores: [3] }, 6));
  const scopedStaff = await ChatClient.connect(session("a-1", { role: "staff", label: "店長", all_stores: false, stores: [3] }));
  const otherStore = await ChatClient.connect(session("a-2", { role: "staff", label: "支店", all_stores: false, stores: [9] }));
  try {
    await member.openAsMember("5");
    await member.send("5", "member", "予約の変更はできますか？", new File([new Uint8Array([0x89, 0x50, 0x4e, 0x47])], "a.png", { type: "image/png" }));

    const threads = await next((onChange, onError) => scopedStaff.watchThreads(onChange, onError), (list) => list.length === 1);
    assert.equal(threads[0].memberName, "佐藤");
    assert.ok(isChatUnread(threads[0], "staff"));
    const received = await next((onChange, onError) => scopedStaff.watchMessages("5", onChange, onError), (list) => list.length === 1);
    assert.equal(received[0].text, "予約の変更はできますか？");
    assert.match(received[0].imagePath, /^projects\/7\/chats\/5\/[0-9a-f-]{36}\.png$/);

    await scopedStaff.markRead("5", "staff");
    await scopedStaff.send("5", "staff", "はい、可能です");
    const thread = await next((onChange, onError) => member.watchThread("5", onChange, onError), (value) => value?.lastSender === "staff");
    assert.ok(isChatUnread(thread, "member"));
    const replies = await next((onChange, onError) => member.watchMessages("5", onChange, onError), (list) => list.length === 2);
    assert.deepEqual(replies.map((message) => [message.sender, message.senderName, message.text]), [["member", "佐藤", "予約の変更はできますか？"], ["staff", "店長", "はい、可能です"]]);

    // 他の会員・担当外の店舗には見えない
    await assert.rejects(next((onChange, onError) => outsider.watchMessages("5", onChange, onError), () => true), /permission/i);
    assert.deepEqual(await next((onChange, onError) => otherStore.watchThreads(onChange, onError), () => true), []);
    await assert.rejects(next((onChange, onError) => otherStore.watchMessages("5", onChange, onError), () => true), /permission/i);
    await assert.rejects(member.send("5", "member", "x".repeat(2001)), /2000文字/);
    await assert.rejects(member.send("5", "member", "", new File(["<svg/>"], "a.svg", { type: "image/svg+xml" })), /JPEG・PNG・WebP/);
  } finally {
    await Promise.all([member, outsider, scopedStaff, otherStore].map((client) => client.close()));
  }
});

test("Storage を使わない設定ではテキストだけ送れる", async () => {
  const options = session("m-7-8", { role: "member", mid: "8", label: "鈴木", stores: [3] }, 8);
  delete options.firebase.storageBucket;
  const member = await ChatClient.connect(options);
  try {
    assert.equal(member.imagesEnabled, false);
    await member.openAsMember("8");
    await member.send("8", "member", "画像なしで送ります");
    await assert.rejects(member.send("8", "member", "", new File([new Uint8Array([0x89])], "a.png", { type: "image/png" })), /画像は送れません/);
    const sent = await next((onChange, onError) => member.watchMessages("8", onChange, onError), (list) => list.length === 1);
    assert.equal(sent[0].text, "画像なしで送ります");
  } finally {
    await member.close();
  }
});

test("送信取消・ブロック・カードが画面の操作どおりに動く", async () => {
  const member = await ChatClient.connect(session("m-7-9", { role: "member", mid: "9", label: "高橋", stores: [3] }, 9));
  const staff = await ChatClient.connect(session("a-9", { role: "staff", label: "店長", all_stores: true, stores: [] }));
  try {
    await member.openAsMember("9");
    await member.send("9", "member", "間違えて送りました");
    const [sent] = await next((onChange, onError) => member.watchMessages("9", onChange, onError), (list) => list.length === 1 && list[0].createdAtStamp !== null);
    await member.deleteMessage("9", sent, "member");
    const [removed] = await next((onChange, onError) => staff.watchMessages("9", onChange, onError), (list) => list[0]?.deleted === true);
    assert.equal(removed.text, "");

    await staff.sendCard("9", { type: "coupon", title: "10%OFF", subtitle: "11/30まで" });
    const cards = await next((onChange, onError) => member.watchMessages("9", onChange, onError), (list) => list.some((item) => item.card));
    assert.deepEqual(cards.find((item) => item.card).card, { type: "coupon", title: "10%OFF", subtitle: "11/30まで" });

    await staff.setBlocked("9", true);
    const blocked = await next((onChange, onError) => member.watchThread("9", onChange, onError), (value) => value?.blocked === true);
    assert.equal(blocked.blocked, true);
    await assert.rejects(member.send("9", "member", "まだ送れる？"), /permission/i);
    await staff.setBlocked("9", false);
    await next((onChange, onError) => member.watchThread("9", onChange, onError), (value) => value?.blocked === false);
    await member.send("9", "member", "解除後は送れる");
  } finally {
    await Promise.all([member.close(), staff.close()]);
  }
});

test("自分の側のメッセージだけ編集・削除でき、店舗は会員のメッセージを変えられない", async () => {
  const member = await ChatClient.connect(session("m-7-11", { role: "member", mid: "11", label: "伊藤", stores: [3] }, 11));
  const staff = await ChatClient.connect(session("a-11", { role: "staff", label: "店長", all_stores: true, stores: [] }));
  try {
    await member.openAsMember("11");
    await member.send("11", "member", "明日10時に伺います");
    const [sent] = await next((onChange, onError) => member.watchMessages("11", onChange, onError), (list) => list.length === 1 && list[0].createdAtStamp !== null);
    await member.editMessage("11", sent, "明日11時に伺います");
    const [edited] = await next((onChange, onError) => staff.watchMessages("11", onChange, onError), (list) => list[0]?.edited === true);
    assert.equal(edited.text, "明日11時に伺います");
    await assert.rejects(staff.editMessage("11", edited, "店が書き換え"), /permission/i);
    await assert.rejects(staff.deleteMessage("11", edited, "staff"), /permission/i);

    await staff.send("11", "staff", "承知しました");
    const replies = await next((onChange, onError) => staff.watchMessages("11", onChange, onError), (list) => list.length === 2 && list[1].createdAtStamp !== null);
    await staff.editMessage("11", replies[1], "承知しました。お待ちしております");
    await assert.rejects(member.editMessage("11", replies[1], "会員が店の文を書き換え"), /permission/i);
    await staff.deleteMessage("11", replies[1], "staff");
  } finally {
    await Promise.all([member.close(), staff.close()]);
  }
});
