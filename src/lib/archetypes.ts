import type {
  Archetype,
  AspectRatio,
  BrandKit,
  Slide,
  SlideData,
  SlideElement,
  SlideType,
  TemplatePreset,
} from "@/types/carousel";
import { CANVAS_SIZES } from "@/types/carousel";
import { backgroundFor } from "./templates";
import { SAFE_MARGIN, chrome, extractRaw, makeEl, textHeight, uid } from "./layout-engine";

export interface ArchCtx {
  tpl: TemplatePreset;
  brand: BrandKit;
  ratio: AspectRatio;
  index: number;
  total: number;
  category: string;
  watermark: boolean;
  progress: boolean;
  headerScale?: number | undefined;
  footerScale?: number | undefined;
}

const MONO = "'JetBrains Mono', ui-monospace, monospace";
const X = SAFE_MARGIN;
const W = 1080 - SAFE_MARGIN * 2;
const CODE_BG = "#0b1120";
const BAD = { bg: "#fecaca", fg: "#450a0a" };
const GOOD = { bg: "#bbf7d0", fg: "#052e16" };
const A_SIDE = { bg: "#bfdbfe", fg: "#0c1a3a" };
const B_SIDE = { bg: "#fde68a", fg: "#3a2a02" };

const TYPE_MAP: Record<Archetype, SlideType> = {
  hook: "hook",
  cta: "cta",
  code_syntax: "code_breakdown",
  comparison_diff: "comparison",
  tech_update: "content",
  deep_dive: "content",
};

function fit(text: string, w: number, maxH: number, start: number, min: number, lh: number) {
  let size = start;
  while (size > min && textHeight(text, size, w, lh) > maxH) size -= 1;
  return size;
}

export function buildFromData(d: SlideData, ctx: ArchCtx): Slide {
  const { h } = CANVAS_SIZES[ctx.ratio];
  const t = ctx.tpl.theme;
  const HF = ctx.tpl.typographyPairing.heading;
  const BF = ctx.tpl.typographyPairing.body;
  const centered = ["centered", "billboard", "quote"].includes(ctx.tpl.layoutEngineId);
  const els: SlideElement[] = [
    ...chrome(ctx.tpl, ctx.brand, h, ctx.index, ctx.total, { watermark: ctx.watermark, progress: ctx.progress, headerScale: ctx.headerScale, footerScale: ctx.footerScale }),
  ];
  const top = 170;
  const bottom = h - 140;
  const s = Math.max(0.6, Math.min(1.2, (bottom - top) / 1040));
  let y = top;

  const push = (e: Partial<SlideElement> & Pick<SlideElement, "type" | "content">) =>
    els.push(makeEl({ zIndex: 6, ...e }));

  const text = (
    content: string | undefined,
    o: {
      type?: SlideElement["type"];
      size: number;
      min: number;
      maxH: number;
      weight?: number;
      color?: string;
      font?: string;
      lh?: number;
      x?: number;
      w?: number;
      align?: SlideElement["textAlign"];
      key?: string;
      gap?: number;
      ls?: number;
      rich?: boolean;
    },
  ) => {
    if (!content) return 0;
    const w = o.w ?? W;
    const lh = o.lh ?? 1.3;
    const size = fit(content, w, Math.max(o.maxH, o.min * lh), o.size, o.min, lh);
    const hh = Math.ceil(textHeight(content, size, w, lh)) + 2;
    push({
      type: o.type ?? "body",
      content,
      x: o.x ?? X,
      y,
      width: w,
      height: hh,
      fontSize: size,
      fontWeight: o.weight ?? 500,
      color: o.color ?? t.fg,
      fontFamily: o.font ?? BF,
      lineHeight: lh,
      textAlign: o.align ?? "left",
      letterSpacing: o.ls ?? 0,
      dataKey: o.key,
      rich: o.rich,
      radius: 0,
    });
    y += hh + (o.gap ?? 24 * s);
    return hh;
  };

  const label = (content: string, x = X + 32, w = W - 64, color = t.fg) =>
    text(content, { size: 20, min: 16, maxH: 30, weight: 800, ls: 3, x, w, color, gap: 10, font: BF });

  const pill = (
    content: string | undefined,
    o: { bg: string; fg: string; x?: number; key?: string; size?: number; hh?: number; advance?: boolean; maxW?: number },
  ) => {
    if (!content) return 0;
    let size = o.size ?? 24;
    const hh = o.hh ?? 56;
    const maxW = o.maxW ?? W;
    while (size > 16 && Math.ceil(content.length * (size * 0.66 + 1.2)) + 72 > maxW) size -= 1;
    const w = Math.min(maxW, Math.ceil(content.length * (size * 0.66 + 1.2)) + 72);
    push({
      type: "badge",
      content,
      x: o.x ?? (centered ? (1080 - w) / 2 : X),
      y,
      width: w,
      height: hh,
      fontSize: size,
      fontWeight: 700,
      letterSpacing: 1.2,
      color: o.fg,
      backgroundColor: o.bg,
      textAlign: "center",
      radius: hh / 2,
      dataKey: o.key,
      fontFamily: BF,
    });
    if (o.advance !== false) y += hh + 26 * s;
    return w;
  };

  const card = (yy: number, hh: number, bg = t.surface, x = X, w = W, radius = 28) =>
    els.push(makeEl({ type: "shape", content: "", x, y: yy, width: w, height: hh, backgroundColor: bg, radius, zIndex: 3 }));

  const align: SlideElement["textAlign"] = centered ? "center" : "left";

  switch (d.archetype) {
    case "hook": {
      y = top + 50 * s;
      pill(ctx.category || d.headerBadge, { bg: t.accent, fg: t.accentFg });
      text(d.headline, {
        type: "heading",
        size: 104,
        min: 56,
        weight: 800,
        lh: 1.06,
        ls: -1.5,
        font: HF,
        maxH: 440 * s,
        align,
        key: "headline",
        gap: 36 * s,
      });
      text(d.subheadline ?? d.definition, {
        type: "subheading",
        size: 40,
        min: 26,
        color: t.muted,
        maxH: 170 * s,
        align,
        key: d.subheadline ? "subheadline" : "definition",
        gap: 40 * s,
      });
      const meta = [d.difficulty, d.readTime].filter(Boolean).join("  •  ");
      pill(meta, { bg: t.surface, fg: t.fg, size: 24 });
      y = bottom - 70;
      push({
        type: "shape",
        content: "",
        x: centered ? (1080 - 160) / 2 : X,
        y: y + 22,
        width: 160,
        height: 8,
        radius: 4,
        backgroundColor: t.accent,
        zIndex: 4,
      });
      push({
        type: "badge",
        content: "Swipe  →",
        x: 1080 - X - 240,
        y: y - 60,
        width: 240,
        height: 48,
        fontSize: 28,
        fontWeight: 700,
        textAlign: "right",
        color: t.fg,
      });
      break;
    }

    case "code_syntax": {
      const lang = d.codeExample?.language ?? "code";
      pill(d.headerBadge ?? lang.toUpperCase(), { bg: t.accent, fg: t.accentFg, key: "headerBadge", size: 22, hh: 50 });
      text(d.headline, { type: "heading", size: 60, min: 36, weight: 800, lh: 1.1, font: HF, maxH: 150 * s, key: "headline", gap: 16 * s, ls: -0.5 });
      text(d.definition, { size: 30, min: 24, color: t.muted, maxH: 90 * s, key: "definition", lh: 1.35, gap: 22 * s });
      if (d.syntaxSnippet) {
        const start = y;
        y += 20;
        label("SYNTAX");
        text(d.syntaxSnippet, { size: 28, min: 18, maxH: 110 * s, x: X + 32, w: W - 64, font: MONO, key: "syntaxSnippet", gap: 0, lh: 1.4 });
        y += 20;
        card(start, y - start);
        y += 22 * s;
      }
      const code = d.codeExample?.code ?? "";
      const out = d.codeExample?.output;
      const reserve = (out ? 70 : 0) + (d.proTipOrGotcha ? 125 * s : 0);
      const budget = Math.max(220, bottom - y - reserve - 10);
      if (code) {
        const size = fit(code, W - 80 - 70, budget - 120, 30, 18, 1.55);
        const hh = Math.min(budget, Math.ceil(textHeight(code, size, W - 80, 1.55)) + 140);
        push({
          type: "code",
          content: code,
          language: lang,
          x: X,
          y,
          width: W,
          height: hh,
          fontSize: size,
          color: "#e2e8f0",
          backgroundColor: CODE_BG,
          radius: 24,
          dataKey: "codeExample.code",
        });
        y += hh + 18 * s;
      }
      if (out) {
        const start = y;
        y += 14;
        text(`▶ Output: ${out}`, { size: 24, min: 17, maxH: 70 * s, x: X + 28, w: W - 56, font: MONO, color: "#86efac", key: "codeExample.output", gap: 0, lh: 1.35 });
        y += 14;
        card(start, y - start, CODE_BG, X, W, 18);
        y += 18 * s;
      }
      if (d.proTipOrGotcha) {
        const start = y;
        y += 18;
        label(d.tipKind === "tip" ? "PRO TIP" : "COMMON GOTCHA", X + 40);
        text(d.proTipOrGotcha, { size: 26, min: 18, maxH: 90 * s, x: X + 40, w: W - 72, key: "proTipOrGotcha", gap: 0, lh: 1.35 });
        y += 18;
        card(start, y - start);
        push({ type: "shape", content: "", x: X, y: start, width: 10, height: y - start, backgroundColor: t.accent, radius: 5, zIndex: 4 });
      }
      break;
    }

    case "tech_update": {
      const w1 = pill(d.headerBadge ?? "New Release", { bg: t.accent, fg: t.accentFg, key: "headerBadge", advance: false, x: X });
      pill("WHAT'S NEW", { bg: t.surface, fg: t.fg, x: X + w1 + 16, size: 20 });
      text(d.headline, { type: "heading", size: 64, min: 38, weight: 800, lh: 1.1, font: HF, maxH: 170 * s, key: "headline", ls: -0.5 });
      // What changed
      {
        const start = y;
        y += 24;
        label("WHAT CHANGED");
        text(d.whatChanged ?? d.definition, { size: 30, min: 20, maxH: 150 * s, x: X + 32, w: W - 64, key: d.whatChanged ? "whatChanged" : "definition", gap: 0, lh: 1.4 });
        y += 24;
        card(start, y - start);
        y += 22 * s;
      }
      // Why it matters
      if (d.impactMetric || d.whyItMatters) {
        const start = y;
        const metricW = 300;
        push({
          type: "heading",
          content: d.impactMetric ?? "↑",
          x: X,
          y: start + 10,
          width: metricW,
          height: 110 * s,
          fontSize: fit(d.impactMetric ?? "↑", metricW, 100 * s, 84, 40, 1.1),
          fontWeight: 800,
          lineHeight: 1.1,
          color: t.fg,
          fontFamily: HF,
          dataKey: "impactMetric",
          radius: 0,
        });
        y = start;
        label("WHY IT MATTERS", X + metricW + 20, W - metricW - 20);
        text(d.whyItMatters, { size: 28, min: 19, maxH: 120 * s, x: X + metricW + 20, w: W - metricW - 20, color: t.muted, key: "whyItMatters", gap: 0, lh: 1.35 });
        y = Math.max(y, start + 120 * s) + 26 * s;
        push({ type: "shape", content: "", x: X, y: start + 115 * s, width: 90, height: 6, radius: 3, backgroundColor: t.accent, zIndex: 4 });
      }
      // Before vs after
      if (d.beforeAfter) {
        const colW = (W - 24) / 2;
        const start = y;
        const maxH = Math.max(120, bottom - start - 90);
        let colH = 0;
        [
          { lbl: "BEFORE", txt: d.beforeAfter.before, x: X, c: BAD, key: "beforeAfter.before" },
          { lbl: "AFTER", txt: d.beforeAfter.after, x: X + colW + 24, c: GOOD, key: "beforeAfter.after" },
        ].forEach((col) => {
          y = start + 20;
          pill(col.lbl, { bg: col.c.bg, fg: col.c.fg, x: col.x + 20, size: 18, hh: 40 });
          text(col.txt, { size: 24, min: 15, maxH, x: col.x + 24, w: colW - 48, font: MONO, color: "#e2e8f0", key: col.key, gap: 0, lh: 1.45 });
          colH = Math.max(colH, y - start + 20);
        });
        card(start, colH, CODE_BG, X, colW, 22);
        card(start, colH, CODE_BG, X + colW + 24, colW, 22);
        y = start + colH;
      }
      break;
    }

    case "comparison_diff": {
      const c = d.comparisonData ?? { leftTitle: "A", leftContent: [], rightTitle: "B", rightContent: [] };
      const oldNew = d.comparisonMode !== "a_vs_b";
      pill(d.headerBadge ?? (oldNew ? "OLD WAY vs MODERN WAY" : "HEAD TO HEAD"), { bg: t.accent, fg: t.accentFg, key: "headerBadge", size: 22, hh: 50 });
      text(d.headline, { type: "heading", size: 60, min: 36, weight: 800, lh: 1.1, font: HF, maxH: 160 * s, key: "headline", ls: -0.5, align });
      const colW = (W - 28) / 2;
      const start = y;
      const verdictReserve = d.verdict ? 170 * s : 0;
      const listMax = Math.max(160, bottom - start - verdictReserve - 120);
      let colH = 0;
      const cols = [
        { title: c.leftTitle, items: c.leftContent, x: X, col: oldNew ? BAD : A_SIDE, mark: oldNew ? "✕" : "•", side: "left" },
        { title: c.rightTitle, items: c.rightContent, x: X + colW + 28, col: oldNew ? GOOD : B_SIDE, mark: oldNew ? "✓" : "•", side: "right" },
      ];
      const listText = cols.map((cl) => cl.items.map((i) => `${cl.mark}  ${i}`).join("\n"));
      const size = Math.min(...listText.map((lt) => fit(lt || " ", colW - 60, listMax, 30, 18, 1.55)));
      cols.forEach((cl, i) => {
        y = start + 26;
        pill(cl.title, { bg: cl.col.bg, fg: cl.col.fg, x: cl.x + 26, size: 24, hh: 52, maxW: colW - 52, key: `comparisonData.${cl.side}Title` });
        const lt = listText[i]!;
        if (lt) {
          const hh = Math.ceil(textHeight(lt, size, colW - 60, 1.55)) + 2;
          push({
            type: "body",
            content: lt,
            x: cl.x + 30,
            y,
            width: colW - 60,
            height: hh,
            fontSize: size,
            lineHeight: 1.55,
            fontWeight: 500,
            color: t.fg,
            fontFamily: BF,
            dataKey: `comparisonData.${cl.side}Content`,
            radius: 0,
          });
          y += hh;
        }
        colH = Math.max(colH, y - start + 26);
      });
      card(start, colH, t.surface, X, colW);
      card(start, colH, t.surface, X + colW + 28, colW);
      y = start + colH + 26 * s;
      if (d.verdict) {
        const vs = y;
        y += 20;
        label("VERDICT", X + 32, W - 64, t.accentFg);
        text(d.verdict, { size: 30, min: 20, maxH: 100 * s, x: X + 32, w: W - 64, weight: 700, color: t.accentFg, key: "verdict", gap: 0, lh: 1.3 });
        y += 20;
        card(vs, y - vs, t.accent);
      }
      break;
    }

    case "deep_dive": {
      const iconSize = 96;
      push({ type: "shape", content: "", x: X, y, width: iconSize, height: iconSize, radius: 28, backgroundColor: t.accent, zIndex: 4 });
      push({ type: "icon", content: d.icon ?? "layers", x: X + 18, y: y + 18, width: 60, height: 60, color: t.accentFg });
      const saveY = y;
      y += 20;
      pill(d.headerBadge ?? "CONCEPT", { bg: t.surface, fg: t.fg, x: X + iconSize + 24, size: 22, hh: 52, key: "headerBadge" });
      y = saveY + iconSize + 28 * s;
      text(d.headline, { type: "heading", size: 66, min: 38, weight: 800, lh: 1.08, font: HF, maxH: 170 * s, key: "headline", ls: -0.5 });
      text(d.definition, { type: "subheading", size: 32, min: 22, color: t.muted, maxH: 140 * s, key: "definition", lh: 1.4, gap: 30 * s });
      (d.keyTakeaways ?? []).slice(0, 3).forEach((k, i, arr) => {
        const by = y;
        const hh = text(k, {
          size: 30,
          min: 20,
          maxH: 110 * s,
          x: X + 50,
          w: W - 50,
          rich: true,
          key: `keyTakeaways.${i}`,
          lh: 1.4,
          gap: i === arr.length - 1 ? 30 * s : 20 * s,
        });
        if (hh) {
          push({ type: "shape", content: "", x: X + 4, y: by + 14, width: 18, height: 18, radius: 9, backgroundColor: t.accent, zIndex: 4 });
        }
      });
      if (d.useCase) {
        const start = y;
        y += 22;
        label("IN PRODUCTION");
        text(d.useCase, { size: 27, min: 18, maxH: Math.max(60, bottom - y - 30), x: X + 32, w: W - 64, key: "useCase", gap: 0, lh: 1.4 });
        y += 22;
        card(start, y - start);
      }
      break;
    }

    case "cta": {
      const cardX = X;
      const inner = W - 120;
      const start = top + 20 * s;
      y = start + 50 * s;
      const logo = ctx.brand.logos[ctx.brand.activeLogoIndex];
      if (logo) {
        push({ type: "logo", content: logo, x: (1080 - 130) / 2, y, width: 130, height: 130, radius: 65 });
        y += 130 + 26 * s;
      }
      text(d.headline, { type: "heading", size: 60, min: 36, weight: 800, lh: 1.12, font: HF, maxH: 170 * s, key: "headline", align: "center", x: X + 60, w: inner });
      const recap = (d.keyTakeaways ?? []).slice(0, 4).map((k) => `✓  ${k.replace(/\*\*/g, "")}`).join("\n");
      if (recap) {
        label("SWIPE RECAP", X + 60, inner);
        text(recap, { size: 28, min: 18, maxH: 250 * s, x: X + 60, w: inner, lh: 1.5, key: "keyTakeaways", gap: 30 * s });
      }
      const btn = `Follow ${ctx.brand.handle}`;
      const bw = Math.min(inner, Math.ceil(btn.length * 32 * 0.6) + 120);
      push({
        type: "badge",
        content: btn,
        x: (1080 - bw) / 2,
        y,
        width: bw,
        height: 84,
        fontSize: 32,
        fontWeight: 700,
        textAlign: "center",
        color: t.accentFg,
        backgroundColor: t.accent,
        radius: 42,
      });
      y += 84 + 22 * s;
      text(d.subheadline ?? `${ctx.brand.name} · ${ctx.brand.profileUrl.replace(/^https?:\/\//, "")}`, {
        size: 24,
        min: 18,
        maxH: 70,
        x: X + 60,
        w: inner,
        align: "center",
        color: t.fg,
        key: "subheadline",
        gap: 0,
      });
      y += 44 * s;
      card(start, y - start, t.surface, cardX, W, 40);
      push({ type: "icon", content: "bookmark", x: (1080 - 220) / 2, y: Math.min(bottom - 40, y + 30 * s), width: 220, height: 60, color: t.muted });
      break;
    }
  }

  return {
    id: uid("slide"),
    slideNumber: ctx.index + 1,
    type: TYPE_MAP[d.archetype],
    elements: els,
    background: backgroundFor(t, ctx.tpl.backgroundType),
    notes: d.headline,
    data: d,
  };
}

/** Legacy slides (hand-made) become structured data so every template can re-render them. */
export function slideToData(slide: Slide): SlideData {
  if (slide.data) return { ...slide.data, slideNumber: slide.slideNumber };
  const raw = extractRaw(slide);
  const base = { slideNumber: slide.slideNumber, headline: raw.title };
  switch (raw.type) {
    case "hook":
      return { ...base, archetype: "hook", subheadline: raw.body };
    case "cta":
      return { ...base, archetype: "cta", subheadline: raw.body };
    case "code_breakdown":
      return { ...base, archetype: "code_syntax", codeExample: { language: "python", code: raw.code ?? "", output: "" } };
    case "comparison":
      return {
        ...base,
        archetype: "comparison_diff",
        comparisonMode: "a_vs_b",
        comparisonData: {
          leftTitle: raw.compare?.leftTitle ?? "A",
          leftContent: (raw.compare?.left ?? "").split("\n").filter(Boolean),
          rightTitle: raw.compare?.rightTitle ?? "B",
          rightContent: (raw.compare?.right ?? "").split("\n").filter(Boolean),
        },
      };
    default:
      return { ...base, archetype: "deep_dive", definition: raw.body };
  }
}

export function reflowSlides(slides: Slide[], ctx: Omit<ArchCtx, "index" | "total">): Slide[] {
  return slides.map((s, i) => buildFromData(slideToData(s), { ...ctx, index: i, total: slides.length }));
}

const LIST_KEYS = new Set(["keyTakeaways", "comparisonData.leftContent", "comparisonData.rightContent"]);
const PREFIX = /^(▶ Output:\s*|✓\s+|✕\s+|•\s+)/;

/** Writes an edited element's text back into the slide's structured data. */
export function applyEditToData(data: SlideData, key: string, value: string): SlideData {
  const next = structuredClone(data) as unknown as Record<string, unknown>;
  const parts = key.split(".");
  const v: unknown = LIST_KEYS.has(key)
    ? value.split("\n").map((l) => l.replace(PREFIX, "").trim()).filter(Boolean)
    : value.replace(PREFIX, "");
  let cur = next;
  for (let i = 0; i < parts.length - 1; i++) {
    const p = parts[i]!;
    if (Array.isArray(cur[p])) {
      (cur[p] as unknown[])[Number(parts[i + 1])] = v;
      return next as unknown as SlideData;
    }
    if (typeof cur[p] !== "object" || cur[p] === null) cur[p] = {};
    cur = cur[p] as Record<string, unknown>;
  }
  cur[parts[parts.length - 1]!] = v;
  return next as unknown as SlideData;
}
