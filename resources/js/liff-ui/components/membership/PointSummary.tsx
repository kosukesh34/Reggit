export function PointSummary({ points }: { points?: number | null }) {
  const safePoints = Number.isFinite(Number(points)) ? Number(points) : 0;
  return (
    <div className="point-summary">
      <div className="point-summary__label">ポイント</div>
      <div className="point-summary__value">{safePoints.toLocaleString()} pt</div>
    </div>
  );
}

