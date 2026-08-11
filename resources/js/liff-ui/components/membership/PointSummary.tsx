export function PointSummary({ points }: { points: number }) {
  return (
    <div className="point-summary">
      <div className="point-summary__label">ポイント</div>
      <div className="point-summary__value">{points.toLocaleString()} pt</div>
    </div>
  );
}

