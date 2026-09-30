import { colord, extend } from "colord";
import a11yPlugin from "colord/plugins/a11y";
import type { CarouselProject, Slide, SlideElement } from "@/types/carousel";
import { CANVAS_SIZES } from "@/types/carousel";
import { SAFE_MARGIN, textHeight } from "./layout-engine";

extend([a11yPlugin]);

export interface LintIssue {
  rule: "truncation" | "safezone" | "contrast" | "structure";
  level: "error" | "warn";
  slideIndex: number;
  elementId?: string;
  message: string;
}

export interface LintResult {
  issues: LintIssue[];
  passed: { rule: string; label: string; ok: boolean }[];
}

const SAFE_EDGE = 40;

function bgUnder(slide: Slide, element: SlideElement): string {
  const behind = slide.elements
    .filter(
      (e) =>
        e.id !== element.id &&
        e.type === "shape" &&
        e.backgroundColor !== "transparent" &&
        e.x <= element.x &&
        e.y <= element.y &&
        e.x + e.width >= element.x + element.width * 0.5 &&
        e.y + e.height >= element.y + element.height * 0.5,
    )
    .sort((a, b) => b.zIndex - a.zIndex)[0];
  const raw = behind?.backgroundColor ?? slide.background.baseColor;
  const c = colord(raw.length === 9 ? raw.slice(0, 7) : raw);
  return c.isValid() ? c.toHex() : slide.background.baseColor;
}

export function lintProject(project: CarouselProject): LintResult {
  const issues: LintIssue[] = [];
  const { w, h } = CANVAS_SIZES[project.aspectRatio];

  project.slides.forEach((slide, si) => {
    slide.elements.forEach((e) => {
      if (["heading", "subheading", "body", "badge", "code"].includes(e.type) && e.content) {
        const needed = textHeight(e.content, e.fontSize, e.width - (e.type === "code" ? 80 : 0), e.lineHeight ?? 1.25);
        if (needed > e.height + 4) {
          issues.push({
            rule: "truncation",
            level: "error",
            slideIndex: si,
            elementId: e.id,
            message: `Slide ${si + 1}: "${e.content.slice(0, 24)}…" overflows its box.`,
          });
        }
      }
      if (e.x < SAFE_EDGE || e.y < SAFE_EDGE || e.x + e.width > w - SAFE_EDGE || e.y + e.height > h - SAFE_EDGE) {
        issues.push({
          rule: "safezone",
          level: "warn",
          slideIndex: si,
          elementId: e.id,
          message: `Slide ${si + 1}: an element sits within 40px of the edge.`,
        });
      }
      if (["heading", "subheading", "body", "badge"].includes(e.type) && e.content) {
        const bg = e.backgroundColor !== "transparent" ? e.backgroundColor : bgUnder(slide, e);
        const fg = colord(e.color);
        const back = colord(bg);
        if (fg.isValid() && back.isValid()) {
          const ratio = fg.contrast(back);
          if (ratio < 4.5) {
            issues.push({
              rule: "contrast",
              level: "error",
              slideIndex: si,
              elementId: e.id,
              message: `Slide ${si + 1}: contrast ${ratio.toFixed(2)}:1 is below 4.5:1.`,
            });
          }
        }
      }
    });
  });

  const first = project.slides[0];
  const last = project.slides[project.slides.length - 1];
  if (!first || !first.elements.some((e) => e.type === "heading" && e.content.length > 8)) {
    issues.push({ rule: "structure", level: "error", slideIndex: 0, message: "Slide 1 has no strong hook headline." });
  }
  if (
    !last ||
    !last.elements.some((e) => e.type === "badge" && !e.isBrandElement && e.backgroundColor !== "transparent")
  ) {
    issues.push({
      rule: "structure",
      level: "error",
      slideIndex: Math.max(0, project.slides.length - 1),
      message: "Final slide is missing a CTA button with your handle.",
    });
  }

  const has = (r: LintIssue["rule"]) => issues.some((i) => i.rule === r);
  return {
    issues,
    passed: [
      { rule: "truncation", label: "No truncated text", ok: !has("truncation") },
      { rule: "safezone", label: "Safe zones respected", ok: !has("safezone") },
      { rule: "contrast", label: "WCAG contrast ≥ 4.5:1", ok: !has("contrast") },
      { rule: "structure", label: "Hook + CTA structure", ok: !has("structure") },
    ],
  };
}

/** Applies mechanical fixes: shrink overflowing text, pull elements into safe area, lift contrast. */
export function autoFixProject(project: CarouselProject): CarouselProject {
  const { w, h } = CANVAS_SIZES[project.aspectRatio];
  const slides = project.slides.map((slide) => ({
    ...slide,
    elements: slide.elements.map((e) => {
      let next = { ...e };
      if (["heading", "subheading", "body", "badge", "code"].includes(next.type) && next.content) {
        let size = next.fontSize;
        const pad = next.type === "code" ? 80 : 0;
        while (size > 16 && textHeight(next.content, size, next.width - pad, next.lineHeight ?? 1.25) > next.height) {
          size -= 1;
        }
        next.fontSize = size;
      }
      next.x = Math.min(Math.max(next.x, SAFE_MARGIN / 2), w - next.width - SAFE_MARGIN / 2);
      next.y = Math.min(Math.max(next.y, SAFE_MARGIN / 2), h - next.height - SAFE_MARGIN / 2);
      if (["heading", "subheading", "body", "badge"].includes(next.type) && next.content) {
        const bg = next.backgroundColor !== "transparent" ? next.backgroundColor : bgUnder(slide, next);
        const back = colord(bg);
        if (back.isValid() && colord(next.color).contrast(back) < 4.5) {
          next.color = back.isDark() ? "#ffffff" : "#0b1020";
        }
      }
      return next;
    }),
  }));
  return { ...project, slides };
}
