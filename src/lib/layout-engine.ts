import type {
  AspectRatio,
  BrandKit,
  Slide,
  SlideElement,
  TemplatePreset,
} from "@/types/carousel";
import { CANVAS_SIZES } from "@/types/carousel";
import { backgroundFor } from "./templates";
import type { RawSlideContent } from "./content-bank";

export const SAFE_MARGIN = 80;

let seq = 0;
export const uid = (p = "id") => `${p}_${Date.now().toString(36)}_${(seq++).toString(36)}`;

export function measureLines(text: string, fontSize: number, width: number) {
  const charWidth = fontSize * 0.52;
  const perLine = Math.max(1, Math.floor(width / charWidth));
  return text
    .split("\n")
    .reduce((acc, para) => acc + Math.max(1, Math.ceil(para.length / perLine)), 0);
}

export function textHeight(text: string, fontSize: number, width: number, lineHeight = 1.25) {
  return measureLines(text, fontSize, width) * fontSize * lineHeight;
}

/** Shrinks font size until the text fits the box, down to a floor. */
export function autoFit(text: string, box: { width: number; height: number }, start: number, floor = 22) {
  let size = start;
  while (size > floor && textHeight(text, size, box.width) > box.height) size -= 2;
  return size;
}

export function makeEl(e: Partial<SlideElement> & Pick<SlideElement, "type" | "content">): SlideElement {
  return {
    id: uid("el"),
    x: SAFE_MARGIN,
    y: SAFE_MARGIN,
    width: 1080 - SAFE_MARGIN * 2,
    height: 120,
    fontSize: 42,
    fontWeight: 500,
    color: "#ffffff",
    backgroundColor: "transparent",
    textAlign: "left",
    zIndex: 1,
    isLocked: false,
    isBrandElement: false,
    opacity: 1,
    rotation: 0,
    letterSpacing: 0,
    lineHeight: 1.25,
    radius: 24,
    ...e,
  };
}

export function chrome(tpl: TemplatePreset, brand: BrandKit, h: number, index: number, total: number, opts: { watermark: boolean; progress: boolean; headerScale?: number | undefined; footerScale?: number | undefined }): SlideElement[] {
  const out: SlideElement[] = [];
  const hs = opts.headerScale ?? 1;
  const fs = opts.footerScale ?? 1;
  const r = Math.round;
  if (opts.watermark) {
    if (brand.logos[brand.activeLogoIndex]) {
      out.push(
        makeEl({
          type: "logo",
          content: brand.logos[brand.activeLogoIndex]!,
          x: SAFE_MARGIN,
          y: r(56 * hs),
          width: r(64 * hs),
          height: r(64 * hs),
          radius: r(32 * hs),
          isBrandElement: true,
          isLocked: true,
          zIndex: 40,
        }),
      );
    }
    out.push(
      makeEl({
        type: "badge",
        content: brand.handle,
        x: brand.logos[brand.activeLogoIndex] ? SAFE_MARGIN + r(82 * hs) : SAFE_MARGIN,
        y: r(68 * hs),
        width: r(420 * hs),
        height: r(40 * hs),
        fontSize: r(26 * hs),
        fontWeight: 600,
        color: tpl.theme.muted,
        backgroundColor: "transparent",
        isBrandElement: true,
        isLocked: true,
        zIndex: 40,
      }),
    );
  }
  if (opts.progress) {
    out.push(
      makeEl({
        type: "badge",
        content: `${index + 1} / ${total}`,
        x: 1080 - SAFE_MARGIN - r(220 * fs),
        y: h - r(110 * fs),
        width: r(220 * fs),
        height: r(44 * fs),
        fontSize: r(24 * fs),
        fontWeight: 600,
        textAlign: "right",
        color: tpl.theme.muted,
        isBrandElement: true,
        isLocked: true,
        zIndex: 40,
      }),
    );
  }
  return out;
}

interface BuildCtx {
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

export function buildSlide(raw: RawSlideContent, ctx: BuildCtx): Slide {
  const { tpl, brand, ratio, index, total } = ctx;
  const { h } = CANVAS_SIZES[ratio];
  const t = tpl.theme;
  const heading = tpl.typographyPairing.heading;
  const body = tpl.typographyPairing.body;
  const layout = tpl.layoutEngineId;
  const centered = layout === "centered" || layout === "billboard" || layout === "quote";
  const align: SlideElement["textAlign"] = centered ? "center" : "left";
  const inner = 1080 - SAFE_MARGIN * 2;

  const elements: SlideElement[] = [...chrome(tpl, brand, h, index, total, { watermark: ctx.watermark, progress: ctx.progress, headerScale: ctx.headerScale, footerScale: ctx.footerScale })];
  const top = 210;

  if (raw.type === "hook") {
    elements.push(
      makeEl({
        type: "badge",
        content: ctx.category,
        x: centered ? (1080 - 320) / 2 : SAFE_MARGIN,
        y: top,
        width: 320,
        height: 62,
        fontSize: 26,
        fontWeight: 700,
        letterSpacing: 2,
        color: t.accentFg,
        backgroundColor: t.accent,
        textAlign: "center",
        radius: 31,
        zIndex: 5,
      }),
    );
    const hSize = autoFit(raw.title, { width: inner, height: h * 0.44 }, 96, 54);
    const hHeight = textHeight(raw.title, hSize, inner, 1.1);
    elements.push(
      makeEl({
        type: "heading",
        content: raw.title,
        x: SAFE_MARGIN,
        y: top + 110,
        width: inner,
        height: hHeight,
        fontSize: hSize,
        fontWeight: 800,
        lineHeight: 1.1,
        letterSpacing: -1,
        color: t.fg,
        textAlign: align,
        fontFamily: heading,
        zIndex: 5,
      }),
    );
    if (raw.body) {
      const bSize = autoFit(raw.body, { width: inner, height: 200 }, 38, 26);
      elements.push(
        makeEl({
          type: "subheading",
          content: raw.body,
          x: SAFE_MARGIN,
          y: top + 140 + hHeight,
          width: inner,
          height: textHeight(raw.body, bSize, inner, 1.35),
          fontSize: bSize,
          fontWeight: 500,
          lineHeight: 1.35,
          color: t.muted,
          textAlign: align,
          fontFamily: body,
          zIndex: 5,
        }),
      );
    }
    elements.push(
      makeEl({
        type: "shape",
        content: "",
        x: SAFE_MARGIN,
        y: h - 210,
        width: 160,
        height: 8,
        radius: 4,
        backgroundColor: t.accent,
        zIndex: 3,
      }),
    );
  } else if (raw.type === "code_breakdown") {
    elements.push(
      makeEl({
        type: "subheading",
        content: raw.title,
        x: SAFE_MARGIN,
        y: top,
        width: inner,
        height: textHeight(raw.title, autoFit(raw.title, { width: inner, height: 180 }, 54, 34), inner, 1.2),
        fontSize: autoFit(raw.title, { width: inner, height: 180 }, 54, 34),
        lineHeight: 1.2,
        fontWeight: 700,
        color: t.fg,
        fontFamily: heading,
        textAlign: align,
        zIndex: 5,
      }),
    );
    const code = raw.code ?? "";
    const codeSize = autoFit(code, { width: inner - 80, height: h * 0.45 }, 32, 18);
    elements.push(
      makeEl({
        type: "code",
        content: code,
        x: SAFE_MARGIN,
        y: top + 160,
        width: inner,
        height: Math.min(h * 0.55, textHeight(code, codeSize, inner - 80, 1.55) + 150),
        fontSize: codeSize,
        color: "#e2e8f0",
        backgroundColor: "#0b1120",
        radius: 28,
        zIndex: 5,
      }),
    );
  } else if (raw.type === "comparison") {
    const c = raw.compare!;
    elements.push(
      makeEl({
        type: "subheading",
        content: raw.title,
        x: SAFE_MARGIN,
        y: top,
        width: inner,
        height: textHeight(raw.title, 48, inner, 1.2),
        fontSize: 48,
        lineHeight: 1.2,
        fontWeight: 700,
        color: t.fg,
        fontFamily: heading,
        textAlign: align,
        zIndex: 5,
      }),
    );
    const colW = (inner - 40) / 2;
    const colH = Math.min(h * 0.46, 520);
    [
      { title: c.leftTitle, text: c.left, x: SAFE_MARGIN },
      { title: c.rightTitle, text: c.right, x: SAFE_MARGIN + colW + 40 },
    ].forEach((col, i) => {
      elements.push(
        makeEl({
          type: "shape",
          content: "",
          x: col.x,
          y: top + 150,
          width: colW,
          height: colH,
          backgroundColor: t.surface,
          radius: 28,
          zIndex: 3,
        }),
        makeEl({
          type: "subheading",
          content: col.title,
          x: col.x + 36,
          y: top + 190,
          width: colW - 72,
          height: 56,
          fontSize: 38,
          fontWeight: 700,
          color: t.accent,
          fontFamily: heading,
          zIndex: 6,
        }),
        makeEl({
          type: "body",
          content: col.text,
          x: col.x + 36,
          y: top + 265,
          width: colW - 72,
          height: colH - 145,
          fontSize: autoFit(col.text, { width: colW - 72, height: colH - 145 }, 32, 20),
          fontWeight: 500,
          lineHeight: 1.55,
          color: t.fg,
          fontFamily: body,
          zIndex: 6,
        }),
      );
    });
  } else if (raw.type === "cta") {
    const cardH = Math.min(640, h * 0.5);
    const cardY = (h - cardH) / 2;
    elements.push(
      makeEl({
        type: "shape",
        content: "",
        x: SAFE_MARGIN,
        y: cardY,
        width: inner,
        height: cardH,
        backgroundColor: t.surface,
        radius: 40,
        zIndex: 3,
      }),
    );
    if (brand.logos[brand.activeLogoIndex]) {
      elements.push(
        makeEl({
          type: "logo",
          content: brand.logos[brand.activeLogoIndex]!,
          x: (1080 - 140) / 2,
          y: cardY + 60,
          width: 140,
          height: 140,
          radius: 70,
          zIndex: 6,
        }),
      );
    }
    elements.push(
      makeEl({
        type: "heading",
        content: raw.title,
        x: SAFE_MARGIN + 60,
        y: cardY + 230,
        width: inner - 120,
        height: textHeight(raw.title, autoFit(raw.title, { width: inner - 120, height: 200 }, 58, 36), inner - 120, 1.2),
        fontSize: autoFit(raw.title, { width: inner - 120, height: 200 }, 58, 36),
        lineHeight: 1.2,
        fontWeight: 800,
        textAlign: "center",
        color: t.fg,
        fontFamily: heading,
        zIndex: 6,
      }),
      makeEl({
        type: "body",
        content: raw.body ?? `Follow ${brand.handle} for more`,
        x: SAFE_MARGIN + 60,
        y: cardY + 360,
        width: inner - 120,
        height: 90,
        fontSize: 34,
        textAlign: "center",
        color: t.muted,
        fontFamily: body,
        zIndex: 6,
      }),
      makeEl({
        type: "badge",
        content: `Follow ${brand.handle}`,
        x: (1080 - 520) / 2,
        y: cardY + cardH - 140,
        width: 520,
        height: 84,
        fontSize: 32,
        fontWeight: 700,
        textAlign: "center",
        color: t.accentFg,
        backgroundColor: t.accent,
        radius: 42,
        zIndex: 6,
      }),
      makeEl({
        type: "icon",
        content: "bookmark",
        x: (1080 - 200) / 2,
        y: h - 230,
        width: 200,
        height: 64,
        color: t.muted,
        zIndex: 6,
      }),
    );
  } else {
    // content
    const numbered = layout === "numbered" || layout === "checklist";
    if (numbered) {
      elements.push(
        makeEl({
          type: "badge",
          content: String(index).padStart(2, "0"),
          x: SAFE_MARGIN,
          y: top,
          width: 120,
          height: 120,
          fontSize: 56,
          fontWeight: 800,
          textAlign: "center",
          color: t.accentFg,
          backgroundColor: t.accent,
          radius: 60,
          fontFamily: heading,
          zIndex: 5,
        }),
      );
    }
    const titleY = top + (numbered ? 160 : 0);
    const tSize = autoFit(raw.title, { width: inner, height: 260 }, 66, 38);
    const tH = textHeight(raw.title, tSize, inner, 1.15);
    elements.push(
      makeEl({
        type: "heading",
        content: raw.title,
        x: SAFE_MARGIN,
        y: titleY,
        width: inner,
        height: tH,
        fontSize: tSize,
        fontWeight: 800,
        lineHeight: 1.15,
        letterSpacing: -0.5,
        color: t.fg,
        textAlign: align,
        fontFamily: heading,
        zIndex: 5,
      }),
      makeEl({
        type: "shape",
        content: "",
        x: centered ? (1080 - 120) / 2 : SAFE_MARGIN,
        y: titleY + tH + 32,
        width: 120,
        height: 6,
        radius: 3,
        backgroundColor: t.accent,
        zIndex: 4,
      }),
    );
    const text = raw.body ?? "";
    const boxH = h - (titleY + tH + 80) - 180;
    const bSize = autoFit(text, { width: inner, height: boxH }, 40, 24);
    elements.push(
      makeEl({
        type: "body",
        content: text,
        x: SAFE_MARGIN,
        y: titleY + tH + 80,
        width: inner,
        height: Math.min(boxH, textHeight(text, bSize, inner, 1.5)),
        fontSize: bSize,
        fontWeight: 500,
        lineHeight: 1.5,
        color: t.muted,
        textAlign: align,
        fontFamily: body,
        zIndex: 5,
      }),
    );
  }

  return {
    id: uid("slide"),
    slideNumber: index + 1,
    type: raw.type,
    elements,
    background: backgroundFor(t, tpl.backgroundType),
    notes: raw.title,
  };
}

/** Re-applies a template to existing slides, preserving all text content. */
export function reflowSlides(slides: Slide[], ctx: Omit<BuildCtx, "index" | "total">): Slide[] {
  const raws: RawSlideContent[] = slides.map((s) => extractRaw(s));
  return raws.map((raw, i) =>
    buildSlide(raw, { ...ctx, index: i, total: raws.length }),
  );
}

export function extractRaw(slide: Slide): RawSlideContent {
  const live = slide.elements.filter((e) => !e.isBrandElement);
  const title =
    live.find((e) => e.type === "heading")?.content ??
    live.find((e) => e.type === "subheading")?.content ??
    slide.notes;
  const code = live.find((e) => e.type === "code")?.content;
  const bodies = live.filter((e) => e.type === "body" || e.type === "subheading");
  if (slide.type === "comparison") {
    const subs = live.filter((e) => e.type === "subheading");
    const texts = live.filter((e) => e.type === "body");
    return {
      type: "comparison",
      title,
      compare: {
        leftTitle: subs[1]?.content ?? "A",
        left: texts[0]?.content ?? "",
        rightTitle: subs[2]?.content ?? "B",
        right: texts[1]?.content ?? "",
      },
    };
  }
  return {
    type: slide.type,
    title,
    body: bodies.find((b) => b.content !== title)?.content,
    code,
  };
}
