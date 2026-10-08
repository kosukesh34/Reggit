import test from 'node:test';
import assert from 'node:assert/strict';
import { profileChoices, validateProfile } from './nativeProfile.ts';
test('Skip の選択値と表示ラベルを保持する', () => {
  assert.deepEqual(profileChoices({key:'sex', values:['2','1']}), [{value:'2', label:'女性'}, {value:'1', label:'男性'}]);
  assert.equal(profileChoices({key:'notification_preference'})[0].label, 'すべてのお知らせ');
  assert.deepEqual(profileChoices({key:'custom',options:[{value:3,label:'三'}]}),[{value:'3',label:'三'}]);
});
test('必須の氏名は姓と名の両方を検証する', () => {
  const fields=[{key:'name',label:'お名前',required:true}];
  assert.equal(validateProfile(fields,{name:'山田'},{name_last:'山田'}).name,'お名前は姓と名の両方を入力してください');
  assert.deepEqual(validateProfile(fields,{name:'山田 太郎'},{name_last:'山田',name_first:'太郎'}),{});
});
test('必須チェックボックス・メール・設定外の選択値を検証する', () => {
  const fields=[{key:'hobbies',label:'趣味',input_type:'checkbox',required:true},{key:'email',input_type:'email'},{key:'sex'}];
  assert.deepEqual(Object.keys(validateProfile(fields,{hobbies:[],email:'invalid@host',sex:'9'},{})),['hobbies','email','sex']);
  assert.deepEqual(validateProfile(fields,{hobbies:['読書'],email:'name@example.com',sex:'2'},{}),{});
});
test('任意の日付を解除した空値を受け入れる', () => assert.deepEqual(validateProfile([{key:'birthday',input_type:'date'}],{birthday:''},{}),{}));
test('郵便番号は全角・ハイフンを許容し、桁不足を検証する', () => {
  const fields = [{ key: 'postal_code', label: '郵便番号' }];
  assert.deepEqual(validateProfile(fields, { postal_code: '１００－０００１' }, {}), {});
  assert.deepEqual(validateProfile(fields, { postal_code: '' }, {}), {});
  assert.equal(validateProfile(fields, { postal_code: '100' }, {}).postal_code, '郵便番号は7桁で入力してください');
});
