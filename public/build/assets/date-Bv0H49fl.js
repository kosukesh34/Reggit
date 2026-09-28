import{f}from"./date-vendor-D5ebMS61.js";function n(e,a,o="—"){const t=e?.trim()??"";if(t===""||t==="—")return o;const r=new Date(t);return Number.isNaN(r.getTime())?t:f(r,a)}export{n as f};
