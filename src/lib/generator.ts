import type { SlideData } from "@/types/carousel";
import { findEntry } from "./content-bank";

export type Tone = "Educational" | "Provocative / Viral" | "Step-by-Step Tutorial" | "Cheat Sheet";

export const TONES: Tone[] = ["Educational", "Provocative / Viral", "Step-by-Step Tutorial", "Cheat Sheet"];

const HOOKS: Record<Tone, (t: string) => string> = {
  Educational: (t) => `${t}, explained in plain English`,
  "Provocative / Viral": (t) => `You're probably doing ${t} wrong`,
  "Step-by-Step Tutorial": (t) => `${t}: the step-by-step playbook`,
  "Cheat Sheet": (t) => `The only ${t} cheat sheet you need`,
};

/** Offline fallback. Uses the curated bank when the topic matches, otherwise a varied generic structure. */
export function generateMock(topic: string, count: number, tone: Tone): SlideData[] {
  const clean = topic.trim() || "Your Topic";
  const entry = findEntry(clean);
  const middleCount = Math.max(1, count - 2);
  let slides: Omit<SlideData, "slideNumber">[];
  if (entry) {
    const middle = entry.slides.filter((s) => s.archetype !== "hook" && s.archetype !== "cta");
    const body = middle.slice(0, middleCount);
    while (body.length < middleCount) body.push(middle[body.length % middle.length]!);
    slides = [entry.slides[0]!, ...body, entry.slides[entry.slides.length - 1]!];
  } else {
    const lc = clean.toLowerCase();
    const pool: Omit<SlideData, "slideNumber">[] = [
      {
        archetype: "deep_dive",
        icon: "lightbulb",
        headerBadge: "Core Concept",
        headline: `What ${clean} really is`,
        definition: `${clean} is best understood by its inputs, its outputs and the constraints between them — not by its buzzwords.`,
        keyTakeaways: [
          "**Inputs:** what it consumes and from where",
          "**Rules:** the invariants it must never break",
          "**Outputs:** what downstream systems rely on",
        ],
        useCase: `Teams that document ${lc} this way onboard new engineers in days instead of weeks.`,
      },
      {
        archetype: "comparison_diff",
        comparisonMode: "old_vs_new",
        headline: `${clean}: old way vs modern way`,
        comparisonData: {
          leftTitle: "Old Way",
          leftContent: ["Manual, ad-hoc steps", "Tribal knowledge", "Fix issues in production"],
          rightTitle: "Modern Way",
          rightContent: ["Automated & repeatable", "Documented decisions", "Catch issues in CI"],
        },
        verdict: "Automate the repeatable parts first — it compounds fastest.",
      },
      {
        archetype: "deep_dive",
        icon: "workflow",
        headerBadge: "How it works",
        headline: `The ${lc} workflow in 3 moves`,
        definition: "Break the process into small, observable steps so every failure has an obvious owner.",
        keyTakeaways: ["**Plan:** define the smallest useful outcome", "**Build:** ship behind a flag", "**Measure:** keep what the data supports"],
        useCase: "Product teams use this loop to ship weekly without big-bang releases.",
      },
      {
        archetype: "tech_update",
        headerBadge: "What's changed",
        headline: `How ${lc} evolved recently`,
        whatChanged: "Tooling moved from manual configuration to opinionated defaults with sensible escape hatches.",
        impactMetric: "Faster",
        whyItMatters: "Less setup means more time on the problem that actually differentiates you.",
        beforeAfter: { before: "Configure everything\nby hand", after: "Sensible defaults,\noverride when needed" },
      },
    ];
    const body = Array.from({ length: middleCount }, (_, i) => pool[i % pool.length]!);
    slides = [
      {
        archetype: "hook",
        headline: HOOKS[tone](clean),
        subheadline: `${middleCount} slides that change how you think about ${lc}.`,
        difficulty: "Intermediate",
        readTime: `${Math.max(1, Math.round(count / 3))} min read`,
      },
      ...body,
      {
        archetype: "cta",
        headline: "Save this for later",
        keyTakeaways: body.map((b) => b.headline).slice(0, 3),
      },
    ];
  }
  return slides.map((s, i) => ({ ...s, slideNumber: i + 1 }));
}

export function categoryFor(topic: string) {
  const entry = findEntry(topic);
  return entry?.category ?? (topic.split(/\s+/)[0] || "INSIGHT").toUpperCase().slice(0, 16);
}

export function titleFor(topic: string) {
  const entry = findEntry(topic);
  return entry?.title ?? (topic.trim() || "Untitled Carousel");
}

export const GEN_STEPS = (topic: string, count: number) => [
  `Researching "${topic || "your topic"}"…`,
  "Picking the right slide type for each idea…",
  `Structuring ${count} slides…`,
  "Writing definitions, code samples and outputs…",
  "Checking facts and edge cases…",
  "Applying brand kit and auto-layout…",
];
