import type { ColorTheme, TemplateCategory, TemplatePreset, SlideBackground } from "@/types/carousel";

export const COLOR_THEMES: ColorTheme[] = [
  {
    id: "midnight",
    name: "Midnight",
    bg: "#0b1020",
    bgCss: "#0b1020",
    fg: "#f8fafc",
    muted: "#94a3b8",
    accent: "#a5b4fc",
    accentFg: "#0b1020",
    surface: "#151c33",
  },
  {
    id: "neon",
    name: "Neon Grid",
    bg: "#07070f",
    bgCss: "#07070f",
    fg: "#eafff8",
    muted: "#7dd3fc",
    accent: "#22d3ee",
    accentFg: "#04121a",
    surface: "#101024",
  },
  {
    id: "paper",
    name: "Paper White",
    bg: "#faf9f6",
    bgCss: "#faf9f6",
    fg: "#111113",
    muted: "#6b7280",
    accent: "#111113",
    accentFg: "#ffffff",
    surface: "#efece4",
  },
  {
    id: "pastel",
    name: "Pastel Editorial",
    bg: "#fdf2f8",
    bgCss: "#fdf2f8",
    fg: "#3b1f38",
    muted: "#8b6b86",
    accent: "#f472b6",
    accentFg: "#2b0f26",
    surface: "#fce7f3",
  },
  {
    id: "sunburst",
    name: "Bold Sunburst",
    bg: "#160d05",
    bgCss: "#160d05",
    fg: "#fff7ed",
    muted: "#fdba74",
    accent: "#fb923c",
    accentFg: "#1b0d02",
    surface: "#27170b",
  },
];

export const LAYOUT_ARCHETYPES = [
  { id: "stacked", name: "Stacked Hook" },
  { id: "split", name: "Split Frame" },
  { id: "centered", name: "Centered Card" },
  { id: "terminal", name: "Dev Terminal" },
  { id: "numbered", name: "Numbered Steps" },
  { id: "magazine", name: "Magazine Rule" },
  { id: "billboard", name: "Billboard" },
  { id: "checklist", name: "Checklist" },
  { id: "compare", name: "Comparison Grid" },
  { id: "quote", name: "Quote Block" },
];

export const AESTHETICS: { id: string; name: string; tag: string; category: TemplateCategory; bgType: SlideBackground["type"] }[] = [
  { id: "minimal", name: "Minimal", tag: "Minimalist", category: "Minimalist", bgType: "solid" },
  { id: "terminal", name: "Dev Terminal", tag: "Dev Terminal", category: "Tech & Coding", bgType: "solid" },
  { id: "editorial", name: "Bold Editorial", tag: "Bold Editorial", category: "Bold Viral", bgType: "solid" },
  { id: "corporate", name: "Corporate Modern", tag: "Corporate Modern", category: "Business & Growth", bgType: "gradient" },
  { id: "cyber", name: "Neon Cyberpunk", tag: "Neon Cyberpunk", category: "Tech & Coding", bgType: "mesh" },
  { id: "softgrad", name: "Soft Gradient", tag: "Minimalist", category: "Pastel Editorial", bgType: "gradient" },
  { id: "brutal", name: "Brutalist", tag: "Bold Editorial", category: "Bold Viral", bgType: "solid" },
  { id: "glass", name: "Glass Mesh", tag: "Corporate Modern", category: "Business & Growth", bgType: "mesh" },
  { id: "zine", name: "Zine Print", tag: "Bold Editorial", category: "Pastel Editorial", bgType: "solid" },
  { id: "aurora", name: "Aurora", tag: "Neon Cyberpunk", category: "Bold Viral", bgType: "mesh" },
];

export const TYPO_PAIRINGS = [
  { heading: "'Space Grotesk', sans-serif", body: "'Inter', sans-serif" },
  { heading: "'JetBrains Mono', monospace", body: "'Inter', sans-serif" },
  { heading: "'Archivo Black', sans-serif", body: "'Inter', sans-serif" },
  { heading: "'Fraunces', serif", body: "'Inter', sans-serif" },
  { heading: "'Inter', sans-serif", body: "'Inter', sans-serif" },
];

export const FILTER_TAGS = [
  "All",
  "Minimalist",
  "Dev Terminal",
  "Bold Editorial",
  "Corporate Modern",
  "Neon Cyberpunk",
];

export function backgroundFor(theme: ColorTheme, type: SlideBackground["type"]): SlideBackground {
  if (type === "gradient") {
    return {
      type,
      baseColor: theme.bg,
      value: `linear-gradient(145deg, ${theme.bg} 0%, ${theme.surface} 60%, ${theme.bg} 100%)`,
    };
  }
  if (type === "mesh") {
    return {
      type,
      baseColor: theme.bg,
      value: `radial-gradient(60% 45% at 15% 10%, ${theme.accent}33 0%, transparent 60%), radial-gradient(50% 40% at 90% 85%, ${theme.accent}26 0%, transparent 60%), ${theme.bg}`,
    };
  }
  return { type: "solid", baseColor: theme.bg, value: theme.bg };
}

function buildTemplates(): TemplatePreset[] {
  const out: TemplatePreset[] = [];
  LAYOUT_ARCHETYPES.forEach((layout, li) => {
    AESTHETICS.forEach((aes, ai) => {
      COLOR_THEMES.forEach((theme, ti) => {
        out.push({
          id: `${layout.id}-${aes.id}-${theme.id}`,
          name: `${aes.name} ${layout.name}`,
          category: aes.category,
          layoutEngineId: layout.id,
          tags: [aes.tag, theme.name],
          theme,
          typographyPairing: TYPO_PAIRINGS[(li + ai + ti) % TYPO_PAIRINGS.length]!,
          backgroundType: aes.bgType,
        });
      });
    });
  });
  return out;
}

export const TEMPLATES: TemplatePreset[] = buildTemplates();

export function getTemplate(id: string): TemplatePreset {
  return TEMPLATES.find((t) => t.id === id) ?? TEMPLATES[0]!;
}
