import { createOpenAI } from "@ai-sdk/openai";
import { streamText } from "ai";
import type { SlideData } from "@/types/carousel";

const ARCHETYPES = ["code_syntax", "tech_update", "comparison_diff", "deep_dive", "hook", "cta"] as const;

const SYSTEM = `You are a senior engineer and technical educator writing Instagram carousels.
Return ONLY a JSON object: {"category": string (1-2 words, uppercase), "title": string, "slides": GeneratedSlideData[]}.

GeneratedSlideData fields:
- slideNumber: number
- archetype: "hook" | "code_syntax" | "tech_update" | "comparison_diff" | "deep_dive" | "cta"
- headerBadge?: string (e.g. "Python 3.12+ Feature", "Salesforce Flow", "Next.js 15")
- headline: string (max ~9 words)
- subheadline?: string (hook only, max 14 words)
- difficulty?: string, readTime?: string (hook only, e.g. "Intermediate", "2 min read")
- definition?: string (accurate technical definition, 15-25 words)
- syntaxSnippet?: string (exact syntax template, max 3 lines)
- codeExample?: { language: "python"|"javascript"|"typescript"|"sql"|"rust"|"go"|"apex"|"java"|"bash", code: string (real, working, max 9 lines, max 48 chars per line), output: string (exact output, one line) }
- comparisonData?: { leftTitle, leftContent: string[3-4, each max 6 words], rightTitle, rightContent: string[3-4] }
- comparisonMode?: "old_vs_new" | "a_vs_b"
- verdict?: string (comparison takeaway, max 18 words)
- proTipOrGotcha?: string (deep practical insight or edge case, max 25 words), tipKind?: "gotcha" | "tip"
- keyTakeaways?: string[] (deep_dive: exactly 3 bullets formatted "**Keyword:** explanation", max 12 words; cta: 3-4 short recap items)
- whatChanged?: string, impactMetric?: string (very short, e.g. "3x faster", "No GIL"), whyItMatters?: string, beforeAfter?: { before: string, after: string } (tech_update; short code or text, max 4 lines each)
- icon?: "layers"|"cpu"|"lightbulb"|"database"|"shield"|"zap"|"code"|"workflow"|"boxes"|"cloud" (deep_dive)
- useCase?: string (deep_dive: real production use case, max 25 words)

Rules:
- Slide 1 is "hook", last slide is "cta". Middle slides must mix archetypes to fit the content: code_syntax for programming/syntax, tech_update for releases/versions/new features, comparison_diff for tool vs tool or good vs bad, deep_dive for architecture/theory. Never use the same archetype more than twice in a row.
- Be specific and technically accurate: real version numbers, real APIs, real outputs. No generic filler.
- Every code example must run as written and its output must be exact.`;

function clean(v: unknown, max = 400): string | undefined {
  return typeof v === "string" && v.trim() ? v.trim().slice(0, max) : undefined;
}
function list(v: unknown, n: number): string[] | undefined {
  return Array.isArray(v) ? v.map((x) => clean(x, 160)).filter((x): x is string => !!x).slice(0, n) : undefined;
}

export function normalizeSlides(raw: unknown[]): SlideData[] {
  const out: SlideData[] = [];
  raw.forEach((r) => {
    if (!r || typeof r !== "object") return;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const o = r as { [K in keyof SlideData]?: any };
    const archetype = ARCHETYPES.includes(o.archetype as (typeof ARCHETYPES)[number])
      ? (o.archetype as SlideData["archetype"])
      : "deep_dive";
    const headline = clean(o.headline, 160);
    if (!headline) return;
    const ce = o.codeExample as Record<"code" | "language" | "output", unknown> | undefined;
    const cd = o.comparisonData as
      | Record<"leftTitle" | "leftContent" | "rightTitle" | "rightContent", unknown>
      | undefined;
    const ba = o.beforeAfter as Record<"before" | "after", unknown> | undefined;
    const slide = {
      slideNumber: out.length + 1,
      archetype,
      headline,
      headerBadge: clean(o.headerBadge, 40),
      subheadline: clean(o.subheadline, 160),
      difficulty: clean(o.difficulty, 30),
      readTime: clean(o.readTime, 20),
      definition: clean(o.definition, 260),
      syntaxSnippet: clean(o.syntaxSnippet, 200),
      codeExample:
        ce && clean(ce.code)
          ? { language: clean(ce.language, 20)?.toLowerCase() ?? "code", code: clean(ce.code, 700)!, output: clean(ce.output, 160) ?? "" }
          : undefined,
      comparisonData:
        cd && clean(cd.leftTitle)
          ? {
              leftTitle: clean(cd.leftTitle, 30)!,
              leftContent: list(cd.leftContent, 5) ?? [],
              rightTitle: clean(cd.rightTitle, 30) ?? "B",
              rightContent: list(cd.rightContent, 5) ?? [],
            }
          : undefined,
      comparisonMode: o.comparisonMode === "old_vs_new" ? "old_vs_new" : o.comparisonMode === "a_vs_b" ? "a_vs_b" : undefined,
      verdict: clean(o.verdict, 200),
      proTipOrGotcha: clean(o.proTipOrGotcha, 240),
      tipKind: o.tipKind === "tip" ? "tip" : "gotcha",
      keyTakeaways: list(o.keyTakeaways, 4),
      whatChanged: clean(o.whatChanged, 260),
      impactMetric: clean(o.impactMetric, 16),
      whyItMatters: clean(o.whyItMatters, 200),
      beforeAfter: ba && clean(ba.before) ? { before: clean(ba.before, 240)!, after: clean(ba.after, 240) ?? "" } : undefined,
      icon: clean(o.icon, 20),
      useCase: clean(o.useCase, 240),
    };
    out.push(JSON.parse(JSON.stringify(slide)) as SlideData);
  });
  return out;
}

export async function generateWithAI(topic: string, count: number, tone: string) {
  const apiKey = process.env["GROQ_API_KEY"] || process.env["OPENAI_API_KEY"];
  if (!apiKey) throw new Error("AI is not configured.");
  const isGroq = apiKey.startsWith("gsk_");
  const provider = createOpenAI({
    baseURL: isGroq ? "https://api.groq.com/openai/v1" : "https://api.openai.com/v1",
    apiKey,
  });
  const result = streamText({
    model: provider.chat(isGroq ? "openai/gpt-oss-120b" : "gpt-4o-mini"),
    system: SYSTEM,
    prompt: `Topic: ${topic}\nTone: ${tone}\nExactly ${count} slides.`,
  });
  const text = await result.text;
  const match = text.match(/\{[\s\S]*\}/);
  if (!match) throw new Error("The AI returned an unexpected format.");
  const parsed = JSON.parse(match[0]) as { category?: unknown; title?: unknown; slides?: unknown };
  const slides = normalizeSlides(Array.isArray(parsed.slides) ? parsed.slides : []);
  if (slides.length < 2) throw new Error("The AI returned too few slides.");
  if (slides[0]!.archetype !== "hook") slides[0]!.archetype = "hook";
  slides[slides.length - 1]!.archetype = "cta";
  return {
    category: clean(parsed.category, 18)?.toUpperCase(),
    title: clean(parsed.title, 80),
    slides,
  };
}
