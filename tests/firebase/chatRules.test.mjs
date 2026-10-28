// チャットの Firestore / Storage ルールの検証。エミュレーター上で実行する: npm run test:firebase-rules
import { readFileSync } from "node:fs";
import { after, before, beforeEach, describe, test } from "node:test";
import { assertFails, assertSucceeds, initializeTestEnvironment } from "@firebase/rules-unit-testing";
import {
  collection, doc, getDoc, getDocs, limit, orderBy, query, serverTimestamp, setDoc, Timestamp, updateDoc, where, writeBatch, deleteDoc,
} from "firebase/firestore";
import { getBytes, ref, uploadBytes } from "firebase/storage";

const PID = "1";
const until = () => Date.now() + 3600_000;
const member = (mid, stores = [10], extra = {}) => ({ role: "member", pid: PID, mid, label: `会員${mid}`, stores, until: until(), ...extra });
const staff = (extra = {}) => ({ role: "staff", pid: PID, label: "店長", all_stores: true, stores: [], until: until(), ...extra });

let env;
const db = (uid, claims) => env.authenticatedContext(uid, claims).firestore();
const storage = (uid, claims) => env.authenticatedContext(uid, claims).storage();
const thread = (firestore, mid, pid = PID) => doc(firestore, "projects", pid, "chats", mid);
const messages = (firestore, mid) => collection(thread(firestore, mid), "messages");
const memberThread = (claims) => ({ member_id: claims.mid, member_name: claims.label, store_ids: claims.stores, member_read_at: serverTimestamp() });
const message = (sender, label, extra = {}) => ({ sender, sender_name: label, text: "こんにちは", created_at: serverTimestamp(), ...extra });
const png = new Uint8Array([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);
const imagePath = (mid, name = "abcdefghijklmnopqrstuv.png") => `projects/${PID}/chats/${mid}/${name}`;

before(async () => {
  env = await initializeTestEnvironment({
    projectId: "demo-reggit",
    firestore: { rules: readFileSync("firestore.rules", "utf8"), host: "127.0.0.1", port: 8086 },
    storage: { rules: readFileSync("storage.rules", "utf8"), host: "127.0.0.1", port: 9199 },
  });
});

after(async () => { await env?.cleanup(); });

beforeEach(async () => {
  await env.clearFirestore();
  await env.clearStorage();
  // 会員1（店舗10の利用者）と会員2（店舗20の利用者）の会話を用意する
  await env.withSecurityRulesDisabled(async (context) => {
    const firestore = context.firestore();
    for (const [mid, store] of [["1", 10], ["2", 20]]) {
      await setDoc(thread(firestore, mid), { member_id: mid, member_name: `会員${mid}`, store_ids: [store], last_message_at: Timestamp.now(), last_sender: "member" });
      await setDoc(doc(messages(firestore, mid), "first"), { sender: "member", sender_name: `会員${mid}`, text: "秘密", created_at: Timestamp.now() });
    }
    await uploadBytes(ref(context.storage(), imagePath("1")), png, { contentType: "image/png" });
  });
});

describe("会員は自分の会話だけ", () => {
  test("自分の会話とメッセージは読める", async () => {
    const firestore = db("m-1-1", member("1"));
    await assertSucceeds(getDoc(thread(firestore, "1")));
    await assertSucceeds(getDocs(messages(firestore, "1")));
  });

  test("他の会員の会話・メッセージ・画像は読めない", async () => {
    const firestore = db("m-1-2", member("2", [20]));
    await assertFails(getDoc(thread(firestore, "1")));
    await assertFails(getDocs(messages(firestore, "1")));
    await assertFails(getDoc(doc(messages(firestore, "1"), "first")));
    await assertFails(getDocs(collection(firestore, "projects", PID, "chats")));
    await assertFails(getBytes(ref(storage("m-1-2", member("2", [20])), imagePath("1"))));
  });

  test("他の会員の会話へは書けない", async () => {
    const claims = member("2", [20]);
    const firestore = db("m-1-2", claims);
    await assertFails(addMessage(firestore, "1", message("member", claims.label)));
    await assertFails(setDoc(thread(firestore, "1"), memberThread(claims), { merge: true }));
    await assertFails(uploadBytes(ref(storage("m-1-2", claims), imagePath("1", "zyxwvutsrqponmlkjihgfe.png")), png, { contentType: "image/png" }));
  });

  test("別プロジェクトの同じ会員IDの会話は読めない", async () => {
    const firestore = db("m-2-1", member("1", [10], { pid: "2" }));
    await assertFails(getDoc(thread(firestore, "1")));
    await assertFails(getDocs(messages(firestore, "1")));
  });

  test("送信者・名前・時刻は偽れない", async () => {
    const claims = member("1");
    const firestore = db("m-1-1", claims);
    await assertSucceeds(addMessage(firestore, "1", message("member", claims.label)));
    await assertFails(addMessage(firestore, "1", message("staff", claims.label)));
    await assertFails(addMessage(firestore, "1", message("member", "店長")));
    await assertFails(addMessage(firestore, "1", message("member", claims.label, { created_at: Timestamp.fromDate(new Date(2020, 0, 1)) })));
    await assertFails(addMessage(firestore, "1", message("member", claims.label, { text: "x".repeat(2001) })));
    await assertFails(addMessage(firestore, "1", message("member", claims.label, { image_path: imagePath("2") })));
    await assertFails(addMessage(firestore, "1", message("member", claims.label, { extra: true })));
  });

  test("初めて開いた会員は会話を作って送信できる（画面と同じ書き込み）", async () => {
    const claims = member("3", [10, 20]);
    const firestore = db("m-1-3", claims);
    await assertSucceeds(setDoc(thread(firestore, "3"), memberThread(claims), { merge: true }));
    const batch = writeBatch(firestore);
    batch.set(doc(messages(firestore, "3")), message("member", claims.label, { image_path: imagePath("3") }));
    batch.update(thread(firestore, "3"), { last_message_at: serverTimestamp(), last_sender: "member", member_read_at: serverTimestamp() });
    await assertSucceeds(batch.commit());
    await assertFails(updateDoc(thread(firestore, "3"), { last_message_at: serverTimestamp(), last_sender: "staff" }));
  });

  test("メッセージは書き換え・削除できない", async () => {
    const firestore = db("m-1-1", member("1"));
    await assertFails(updateDoc(doc(messages(firestore, "1"), "first"), { text: "改ざん" }));
    await assertFails(deleteDoc(doc(messages(firestore, "1"), "first")));
    await assertFails(deleteDoc(thread(firestore, "1")));
  });

  test("会話の利用店舗・名前はトークンの値でしか書けない", async () => {
    const claims = member("1");
    const firestore = db("m-1-1", claims);
    await assertSucceeds(setDoc(thread(firestore, "1"), memberThread(claims), { merge: true }));
    await assertFails(setDoc(thread(firestore, "1"), { ...memberThread(claims), store_ids: [10, 20] }, { merge: true }));
    await assertFails(setDoc(thread(firestore, "1"), { ...memberThread(claims), member_name: "別人" }, { merge: true }));
    await assertFails(updateDoc(thread(firestore, "1"), { staff_read_at: serverTimestamp() }));
  });

  test("期限切れのトークンと未ログインは拒否する", async () => {
    await assertFails(getDoc(thread(db("m-1-1", member("1", [10], { until: Date.now() - 1000 })), "1")));
    await assertFails(getDoc(thread(env.unauthenticatedContext().firestore(), "1")));
    await assertFails(getBytes(ref(env.unauthenticatedContext().storage(), imagePath("1"))));
  });
});

describe("店舗スタッフ", () => {
  test("全店舗のスタッフは一覧・読み取り・返信ができる", async () => {
    const claims = staff();
    const firestore = db("a-1", claims);
    await assertSucceeds(getDocs(query(collection(firestore, "projects", PID, "chats"), orderBy("last_message_at", "desc"), limit(100))));
    await assertSucceeds(getDocs(messages(firestore, "2")));
    const batch = writeBatch(firestore);
    batch.set(doc(messages(firestore, "1")), message("staff", claims.label));
    batch.update(thread(firestore, "1"), { last_message_at: serverTimestamp(), last_sender: "staff", staff_read_at: serverTimestamp() });
    await assertSucceeds(batch.commit());
    await assertSucceeds(getBytes(ref(storage("a-1", claims), imagePath("1"))));
  });

  test("担当店舗のスタッフは利用店舗が重なる会員だけ", async () => {
    const claims = staff({ all_stores: false, stores: [10] });
    const firestore = db("a-2", claims);
    await assertSucceeds(getDocs(query(collection(firestore, "projects", PID, "chats"), where("store_ids", "array-contains-any", [10]), orderBy("last_message_at", "desc"))));
    await assertFails(getDocs(query(collection(firestore, "projects", PID, "chats"), orderBy("last_message_at", "desc"))));
    await assertSucceeds(getDocs(messages(firestore, "1")));
    await assertFails(getDoc(thread(firestore, "2")));
    await assertFails(getDocs(messages(firestore, "2")));
    await assertFails(addMessage(firestore, "2", message("staff", claims.label)));
    await assertSucceeds(getBytes(ref(storage("a-2", claims), imagePath("1"))));
    await assertFails(uploadBytes(ref(storage("a-2", claims), imagePath("2")), png, { contentType: "image/png" }));
  });

  test("会員として送ったり会員の情報を書き換えたりできない", async () => {
    const claims = staff();
    const firestore = db("a-1", claims);
    await assertFails(addMessage(firestore, "1", message("member", claims.label)));
    await assertFails(updateDoc(thread(firestore, "1"), { store_ids: [10, 20] }));
    await assertFails(updateDoc(thread(firestore, "1"), { member_read_at: serverTimestamp() }));
  });

  test("別プロジェクトのスタッフは読めない", async () => {
    await assertFails(getDocs(messages(db("a-9", staff({ pid: "2" })), "1")));
  });
});

describe("削除・ブロック・カード", () => {
  const deletion = (data, role) => ({ sender: data.sender, sender_name: data.sender_name, text: "", created_at: data.created_at, deleted: true, deleted_by: role });
  const seedStaffMessage = () => env.withSecurityRulesDisabled((context) =>
    setDoc(doc(messages(context.firestore(), "1"), "reply"), { sender: "staff", sender_name: "店長", text: "返信", created_at: Timestamp.now() }));

  test("会員は自分の送信だけ取り消せる", async () => {
    await seedStaffMessage();
    const firestore = db("m-1-1", member("1"));
    const mine = (await getDoc(doc(messages(firestore, "1"), "first"))).data();
    const reply = (await getDoc(doc(messages(firestore, "1"), "reply"))).data();
    await assertFails(setDoc(doc(messages(firestore, "1"), "reply"), deletion(reply, "member")));
    await assertFails(setDoc(doc(messages(firestore, "1"), "first"), { ...deletion(mine, "member"), text: "残す" }));
    await assertFails(setDoc(doc(messages(firestore, "1"), "first"), { ...deletion(mine, "member"), sender_name: "店長" }));
    await assertFails(setDoc(doc(messages(firestore, "1"), "first"), deletion(mine, "staff")));
    await assertSucceeds(setDoc(doc(messages(firestore, "1"), "first"), deletion(mine, "member")));
    await assertSucceeds(updateDoc(thread(firestore, "1"), { edited_at: serverTimestamp() }));
    // 他の会員は取り消せない
    await assertFails(setDoc(doc(messages(db("m-1-2", member("2", [20])), "1"), "first"), deletion(mine, "member")));
  });

  test("店舗は担当範囲の店舗側メッセージだけ削除でき、会員のメッセージは削除できない", async () => {
    await seedStaffMessage();
    const scoped = db("a-2", staff({ all_stores: false, stores: [10] }));
    const memberMessage = (await getDoc(doc(messages(scoped, "1"), "first"))).data();
    const reply = (await getDoc(doc(messages(scoped, "1"), "reply"))).data();
    await assertFails(setDoc(doc(messages(scoped, "1"), "first"), deletion(memberMessage, "staff")));
    await assertFails(setDoc(doc(messages(db("a-1", staff()), "1"), "first"), deletion(memberMessage, "staff")));
    await assertSucceeds(setDoc(doc(messages(scoped, "1"), "reply"), deletion(reply, "staff")));
    const other = db("a-3", staff({ all_stores: false, stores: [20] }));
    await assertFails(setDoc(doc(messages(other, "1"), "reply"), deletion(reply, "staff")));
  });

  test("自分の側のテキストだけ編集でき、送信者・時刻・画像は変えられない", async () => {
    await seedStaffMessage();
    const edit = (data, text, extra = {}) => ({ sender: data.sender, sender_name: data.sender_name, text, created_at: data.created_at, edited_at: serverTimestamp(), ...extra });
    const memberDb = db("m-1-1", member("1"));
    const mine = (await getDoc(doc(messages(memberDb, "1"), "first"))).data();
    const reply = (await getDoc(doc(messages(memberDb, "1"), "reply"))).data();
    await assertSucceeds(setDoc(doc(messages(memberDb, "1"), "first"), edit(mine, "訂正しました")));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "reply"), edit(reply, "店の返信を書き換え")));
    const edited = (await getDoc(doc(messages(memberDb, "1"), "first"))).data();
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit(edited, "名前も変える", { sender_name: "店長" })));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit(edited, "時刻も変える", { created_at: Timestamp.fromDate(new Date(2020, 0, 1)) })));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit(edited, "画像を足す", { image_path: imagePath("1") })));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), { ...edit(edited, "時刻を偽る"), edited_at: Timestamp.fromDate(new Date(2020, 0, 1)) }));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit(edited, "")));
    // 店舗は店舗側だけ。会員のメッセージは編集できない
    const staffDb = db("a-1", staff());
    await assertFails(setDoc(doc(messages(staffDb, "1"), "first"), edit(edited, "店が会員の文を直す")));
    await assertSucceeds(setDoc(doc(messages(staffDb, "1"), "reply"), edit(reply, "返信を訂正しました")));
  });

  test("ブロック中の会員・カード・削除済みのメッセージは編集できない", async () => {
    const memberDb = db("m-1-1", member("1"));
    const mine = (await getDoc(doc(messages(memberDb, "1"), "first"))).data();
    const edit = (text) => ({ sender: mine.sender, sender_name: mine.sender_name, text, created_at: mine.created_at, edited_at: serverTimestamp() });
    await assertSucceeds(updateDoc(thread(db("a-1", staff()), "1"), { blocked: true }));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit("ブロック中に編集")));
    await assertSucceeds(setDoc(doc(messages(memberDb, "1"), "first"), deletion(mine, "member")));
    await assertSucceeds(updateDoc(thread(db("a-1", staff()), "1"), { blocked: false }));
    await assertFails(setDoc(doc(messages(memberDb, "1"), "first"), edit("削除後に編集")));
    const staffDb = db("a-1", staff());
    await addMessage(staffDb, "1", message("staff", "店長", { text: "", card: { type: "coupon", title: "10%OFF" } }));
    const card = (await getDocs(messages(staffDb, "1"))).docs.find((item) => item.data().card);
    await assertFails(setDoc(card.ref, { sender: "staff", sender_name: "店長", text: "書き換え", created_at: card.data().created_at, edited_at: serverTimestamp() }));
  });

  test("ブロックした会員は送信できず、自分では解除できない", async () => {
    const claims = member("1");
    const firestore = db("m-1-1", claims);
    await assertFails(updateDoc(thread(firestore, "1"), { blocked: true }));
    await assertSucceeds(updateDoc(thread(db("a-1", staff()), "1"), { blocked: true }));
    await assertFails(addMessage(firestore, "1", message("member", claims.label)));
    await assertFails(updateDoc(thread(firestore, "1"), { blocked: false }));
    await assertSucceeds(getDocs(messages(firestore, "1")));
    await assertSucceeds(setDoc(thread(firestore, "1"), memberThread(claims), { merge: true }));
    await assertSucceeds(updateDoc(thread(db("a-1", staff()), "1"), { blocked: false }));
    await assertSucceeds(addMessage(firestore, "1", message("member", claims.label)));
  });

  test("クーポン・予約のカードは店舗だけが送れる", async () => {
    const card = { type: "coupon", title: "10%OFF", subtitle: "11月30日まで" };
    const staffDb = db("a-1", staff());
    await assertSucceeds(addMessage(staffDb, "1", message("staff", "店長", { text: "", card })));
    await assertSucceeds(addMessage(staffDb, "1", message("staff", "店長", { text: "", card: { type: "reservation", title: "11月3日 18:00" } })));
    await assertFails(addMessage(staffDb, "1", message("staff", "店長", { text: "", card: { ...card, type: "link" } })));
    await assertFails(addMessage(staffDb, "1", message("staff", "店長", { text: "", card: { ...card, url: "https://evil.example" } })));
    const claims = member("1");
    await assertFails(addMessage(db("m-1-1", claims), "1", message("member", claims.label, { text: "", card })));
  });
});

describe("画像", () => {
  test("JPEG・PNG・WebP の5MB未満だけ自分の会話に置ける", async () => {
    const claims = member("1");
    const files = storage("m-1-1", claims);
    await assertSucceeds(uploadBytes(ref(files, imagePath("1", "aaaaaaaaaaaaaaaaaaaaaa.png")), png, { contentType: "image/png" }));
    await assertFails(uploadBytes(ref(files, imagePath("1", "bbbbbbbbbbbbbbbbbbbbbb.png")), png, { contentType: "text/html" }));
    await assertFails(uploadBytes(ref(files, imagePath("1", "cccccccccccccccccccccc.png")), png, { contentType: "image/svg+xml" }));
    await assertFails(uploadBytes(ref(files, imagePath("1", "dddddddddddddddddddddd.html")), png, { contentType: "image/png" }));
    await assertFails(uploadBytes(ref(files, imagePath("1", "eeeeeeeeeeeeeeeeeeeeee.png")), new Uint8Array(5 * 1024 * 1024), { contentType: "image/png" }));
    await assertFails(uploadBytes(ref(files, imagePath("1")), png, { contentType: "image/png" }));
  });
});

function addMessage(firestore, mid, data) {
  return setDoc(doc(messages(firestore, mid)), data);
}
