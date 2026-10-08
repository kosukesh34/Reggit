import test from 'node:test';
import assert from 'node:assert/strict';
import { playableEntitlements, scratchCells, rouletteLabels, rouletteTravel } from './nativeLottery.ts';
test('無効な種類と消費済みの権利を次の抽選に含めない', () => {
  const state={gacha_enabled:true,scratch_enabled:true,roulette_enabled:false,entitlements:[{id:1,reward_type:'gacha'},{id:2,reward_type:'scratch'},{id:3,reward_type:'roulette'}]};
  assert.deepEqual(playableEntitlements(state,[1]),[{id:2,reward_type:'scratch'}]);
});
test('スクラッチは Skip と同じ 18×12 の隣接セルを削り、重複を数えない', () => {
  const cells=scratchCells(150,100,300,200,[]);
  assert.equal(cells.length,9);
  assert.deepEqual(scratchCells(150,100,300,200,cells),cells);
  assert.equal(scratchCells(0,0,300,200,[]).length,4);
  assert.deepEqual(scratchCells(0,0,0,0,[]),[]);
});
test('半分削る閾値は 108 セルであり、各セルを一度だけ数える', () => {
  let cells=[];
  for(let row=0;row<5;row++)for(let col=0;col<18;col++)cells=scratchCells(col*10+5,row*10+5,180,120,cells);
  assert.equal(cells.length,108);
});
test('ルーレットのラベルを8枠に展開し、抽選結果の枠で停止する', () => {
  assert.deepEqual(rouletteLabels(['A','B']),['A','B','A','B','A','B','A','B']);
  assert.equal((30+rouletteTravel(2,8,30,false))%360,247.5);
  assert.equal(rouletteTravel(0,8,0,true),697.5);
});
