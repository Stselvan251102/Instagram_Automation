export type AspectRatio = "4:5" | "1:1" | "9:16";

export interface BrandKit {
  id: string;
  name: string;
  handle: string;
  profileUrl: string;
  logos: string[];
  primaryColor: string;
  secondaryColor: string;
  accentColor: string;
  canvasColor: string;
  fontHeading: string;
  fontBody: string;
  activeLogoIndex: number;
}

export type ElementType =
  | "heading"
  | "subheading"
  | "body"
  | "image"
  | "logo"
  | "icon"
  | "code"
  | "badge"
  | "shape";

export interface SlideElement {
  id: string;
  type: ElementType;
  content: string;
  x: number;
  y: number;
  width: number;
  height: number;
  fontSize: number;
  fontWeight: number;
  color: string;
  backgroundColor: string;
  textAlign: "left" | "center" | "right";
  zIndex: number;
  isLocked: boolean;
  isBrandElement: boolean;
  fontFamily?: string;
  fontStyle?: "normal" | "italic";
  opacity?: number;
  rotation?: number;
  letterSpacing?: number;
  lineHeight?: number;
  radius?: number;
  /** Code language for syntax highlighting (code elements). */
  language?: string | undefined;
  /** Path into Slide.data that this element edits, e.g. "headline" or "keyTakeaways.1". */
  dataKey?: string | undefined;
  /** Render **bold** markers as bold keywords. */
  rich?: boolean | undefined;
}

export type SlideType = "hook" | "content" | "code_breakdown" | "comparison" | "cta";

export interface SlideBackground {
  type: "solid" | "gradient" | "mesh";
  value: string;
  baseColor: string;
}

export interface Slide {
  id: string;
  slideNumber: number;
  type: SlideType;
  elements: SlideElement[];
  background: SlideBackground;
  notes: string;
  /** Structured content payload this slide was built from (keeps text when templates change). */
  data?: SlideData | undefined;
}

export type Archetype = "code_syntax" | "tech_update" | "comparison_diff" | "deep_dive" | "hook" | "cta";

export interface SlideData {
  slideNumber: number;
  archetype: Archetype;
  headerBadge?: string | undefined;
  headline: string;
  subheadline?: string | undefined;
  definition?: string | undefined;
  syntaxSnippet?: string | undefined;
  codeExample?: { language: string | undefined; code: string; output: string };
  comparisonData?: { leftTitle: string | undefined; leftContent: string[]; rightTitle: string; rightContent: string[] };
  comparisonMode?: "old_vs_new" | "a_vs_b" | undefined;
  verdict?: string | undefined;
  proTipOrGotcha?: string | undefined;
  tipKind?: "gotcha" | "tip" | undefined;
  keyTakeaways?: string[] | undefined;
  // hook
  difficulty?: string | undefined;
  readTime?: string | undefined;
  // tech update
  whatChanged?: string | undefined;
  impactMetric?: string | undefined;
  whyItMatters?: string | undefined;
  beforeAfter?: { before: string | undefined; after: string };
  // deep dive
  icon?: string | undefined;
  useCase?: string | undefined;
}

export interface CarouselProject {
  id: string;
  title: string;
  topic: string;
  category?: string;
  aspectRatio: AspectRatio;
  templateId: string;
  slides: Slide[];
  brandKitId: string;
  showWatermark: boolean;
  showProgress: boolean;
  headerScale?: number;
  footerScale?: number;
}

export type TemplateCategory =
  | "Tech & Coding"
  | "Business & Growth"
  | "Minimalist"
  | "Bold Viral"
  | "Pastel Editorial";

export interface ColorTheme {
  id: string;
  name: string;
  bg: string;
  bgCss: string;
  fg: string;
  muted: string;
  accent: string;
  accentFg: string;
  surface: string;
}

export interface TemplatePreset {
  id: string;
  name: string;
  category: TemplateCategory;
  layoutEngineId: string;
  tags: string[];
  theme: ColorTheme;
  typographyPairing: { heading: string; body: string };
  backgroundType: SlideBackground["type"];
}

export const CANVAS_SIZES: Record<AspectRatio, { w: number; h: number; label: string }> = {
  "4:5": { w: 1080, h: 1350, label: "4:5 Portrait" },
  "1:1": { w: 1080, h: 1080, label: "1:1 Square" },
  "9:16": { w: 1080, h: 1920, label: "9:16 Stories" },
};
