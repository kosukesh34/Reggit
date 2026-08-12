const code128Patterns = [
  "212222", "222122", "222221", "121223", "121322", "131222", "122213", "122312", "132212", "221213",
  "221312", "231212", "112232", "122132", "122231", "113222", "123122", "123221", "223211", "221132",
  "221231", "213212", "223112", "312131", "311222", "321122", "321221", "312212", "322112", "322211",
  "212123", "212321", "232121", "111323", "131123", "131321", "112313", "132113", "132311", "211313",
  "231113", "231311", "112133", "112331", "132131", "113123", "113321", "133121", "313121", "211331",
  "231131", "213113", "213311", "213131", "311123", "311321", "331121", "312113", "312311", "332111",
  "314111", "221411", "431111", "111224", "111422", "121124", "121421", "141122", "141221", "112214",
  "112412", "122114", "122411", "142112", "142211", "241211", "221114", "413111", "241112", "134111",
  "111242", "121142", "121241", "114212", "124112", "124211", "411212", "421112", "421211", "212141",
  "214121", "412121", "111143", "111341", "131141", "114113", "114311", "411113", "411311", "113141",
  "114131", "311141", "411131", "211412", "211214", "211232", "2331112",
] as const;

interface BarcodeBar {
  width: number;
  x: number;
}

function encodeCode128B(value: string) {
  const source = typeof value === "string" ? value : String(value ?? "");
  const safeValue = Array.from(source).filter((character) => {
    const code = character.charCodeAt(0);
    return code >= 32 && code <= 126;
  }).join("");
  const dataCodes = Array.from(safeValue).map((character) => character.charCodeAt(0) - 32);
  const startCode = 104;
  const checksum = (startCode + dataCodes.reduce((sum, code, index) => sum + code * (index + 1), 0)) % 103;
  const symbols = [startCode, ...dataCodes, checksum, 106];
  const quietZone = 10;
  const bars: BarcodeBar[] = [];
  let x = quietZone;

  symbols.forEach((symbol) => {
    const pattern = code128Patterns[symbol];
    if (pattern === undefined) {
      return;
    }
    Array.from(pattern).forEach((widthCharacter, index) => {
      const width = Number(widthCharacter);
      if (index % 2 === 0) {
        bars.push({ x, width });
      }
      x += width;
    });
  });

  return { bars, safeValue, width: Math.max(x + quietZone, quietZone * 2 + 20) };
}

export function MembershipBarcode({ value, enlarged = false }: { value?: string | null; enlarged?: boolean }) {
  const barcode = encodeCode128B(String(value ?? "0"));

  return (
    <figure
      className={`barcode${enlarged ? " barcode--enlarged" : ""}`}
      aria-label={`会員番号 ${barcode.safeValue || "—"} のCode 128バーコード`}
    >
      <svg
        className="barcode__svg"
        viewBox={`0 0 ${barcode.width} 66`}
        role="img"
        aria-hidden="true"
        preserveAspectRatio="xMidYMid meet"
        shapeRendering="crispEdges"
      >
        <rect width={barcode.width} height="66" className="barcode__paper" />
        {barcode.bars.map((bar, index) => (
          <rect key={`${bar.x}-${index}`} x={bar.x} y="4" width={bar.width} height="48" className="barcode__ink" />
        ))}
      </svg>
      <figcaption className="barcode__value">{barcode.safeValue || "—"}</figcaption>
    </figure>
  );
}
