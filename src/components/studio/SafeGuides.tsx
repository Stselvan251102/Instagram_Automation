interface Props {
  width: number;
  height: number;
}

/** Overlay showing where Instagram chrome covers the post. */
export function SafeGuides({ width, height }: Props) {
  return (
    <div style={{ position: "absolute", inset: 0, pointerEvents: "none", width, height }}>
      <div
        style={{
          position: "absolute",
          inset: 40,
          border: "2px dashed rgba(99,102,241,.55)",
          borderRadius: 8,
        }}
      />
      <div
        style={{
          position: "absolute",
          top: 0,
          left: 0,
          right: 0,
          height: 120,
          background: "rgba(244,63,94,.14)",
          borderBottom: "2px dashed rgba(244,63,94,.5)",
        }}
      />
      <div
        style={{
          position: "absolute",
          bottom: 0,
          left: 0,
          right: 0,
          height: 150,
          background: "rgba(244,63,94,.14)",
          borderTop: "2px dashed rgba(244,63,94,.5)",
        }}
      />
      <div
        style={{
          position: "absolute",
          right: 24,
          top: height / 2 - 60,
          width: 90,
          height: 120,
          borderRadius: 12,
          background: "rgba(56,189,248,.16)",
          border: "2px dashed rgba(56,189,248,.5)",
        }}
      />
    </div>
  );
}
