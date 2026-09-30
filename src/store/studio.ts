import { create } from "zustand";
import type {
  AspectRatio,
  BrandKit,
  CarouselProject,
  Slide,
  SlideData,
  SlideElement,
} from "@/types/carousel";
import { getTemplate, TEMPLATES } from "@/lib/templates";
import { uid } from "@/lib/layout-engine";
import { applyEditToData, buildFromData, reflowSlides } from "@/lib/archetypes";
import { categoryFor, generateMock, titleFor, type Tone } from "@/lib/generator";
import { autoFixProject } from "@/lib/preflight";

const BRAND_KEY = "carouselfy.brandkits";
const PROJECT_KEY = "carouselfy.project.v2";
const SAVED_KEY = "carouselfy.saved-projects";
const HISTORY_LIMIT = 100;

export const DEFAULT_BRAND: BrandKit = {
  id: "bk_default",
  name: "Carouselfy",
  handle: "@carouselfy",
  profileUrl: "https://carouselfy.app",
  logos: [],
  primaryColor: "#6366f1",
  secondaryColor: "#22d3ee",
  accentColor: "#f472b6",
  canvasColor: "#0b1020",
  fontHeading: "'Space Grotesk', sans-serif",
  fontBody: "'Inter', sans-serif",
  activeLogoIndex: 0,
};

const DEFAULT_TEMPLATE = "stacked-minimal-midnight";

function load<T>(key: string, fallback: T): T {
  if (typeof window === "undefined") return fallback;
  try {
    const raw = window.localStorage.getItem(key);
    return raw ? (JSON.parse(raw) as T) : fallback;
  } catch {
    return fallback;
  }
}

function save(key: string, value: unknown) {
  if (typeof window === "undefined") return;
  try {
    window.localStorage.setItem(key, JSON.stringify(value));
  } catch {
    /* quota */
  }
}

function makeProject(
  topic: string,
  count: number,
  tone: Tone,
  templateId: string,
  brand: BrandKit,
  ratio: AspectRatio,
  ai?: GeneratedResult,
): CarouselProject {
  const tpl = getTemplate(templateId);
  const raws = ai?.slides ?? generateMock(topic, count, tone);
  const category = ai?.category ?? categoryFor(topic);
  const slides = raws.map((raw, i) =>
    buildFromData(raw, {
      tpl,
      brand,
      ratio,
      index: i,
      total: raws.length,
      category,
      watermark: true,
      progress: true,
    }),
  );
  return {
    id: uid("proj"),
    title: ai?.title ?? titleFor(topic),
    topic,
    category,
    aspectRatio: ratio,
    templateId,
    slides,
    brandKitId: brand.id,
    showWatermark: true,
    showProgress: true,
  };
}

export interface GeneratedResult {
  slides: SlideData[];
  category?: string | undefined;
  title?: string | undefined;
}

interface StudioState {
  brandKits: BrandKit[];
  activeBrandKitId: string;
  project: CarouselProject;
  activeSlide: number;
  selectedElementId: string | null;
  zoom: number | "fit";
  showGuides: boolean;
  generating: boolean;
  genLog: string[];
  apiKey: string;
  apiProvider: "openai" | "groq" | "gemini" | "anthropic" | "openrouter";

  brand: () => BrandKit;
  setBrand: (patch: Partial<BrandKit>) => void;
  addBrandKit: () => void;
  selectBrandKit: (id: string) => void;
  deleteBrandKit: (id: string) => void;

  setProject: (p: CarouselProject) => void;
  generate: (topic: string, count: number, tone: Tone, ai?: GeneratedResult) => void;
  applyTemplate: (templateId: string) => void;
  setAspectRatio: (r: AspectRatio) => void;
  toggleChrome: (key: "showWatermark" | "showProgress") => void;

  setActiveSlide: (i: number) => void;
  addSlide: () => void;
  duplicateSlide: (i: number) => void;
  deleteSlide: (i: number) => void;
  reorderSlides: (from: number, to: number) => void;
  updateSlide: (i: number, patch: Partial<Slide>) => void;

  select: (id: string | null) => void;
  updateElement: (id: string, patch: Partial<SlideElement>) => void;
  addElement: (type: SlideElement["type"], content?: string) => void;
  deleteElement: (id: string) => void;
  duplicateElement: (id: string) => void;
  reorderElement: (id: string, dir: 1 | -1) => void;

  setZoom: (z: number | "fit") => void;
  toggleGuides: () => void;
  setApiKey: (k: string) => void;
  setApiProvider: (p: "openai" | "groq" | "gemini" | "anthropic" | "openrouter") => void;
  pushLog: (l: string) => void;
  setGenerating: (g: boolean) => void;
  autoFix: () => void;

  past: CarouselProject[];
  future: CarouselProject[];
  undo: () => void;
  redo: () => void;
  saveStatus: "saved" | "unsaved" | "saving";
  lastSavedAt: number | null;
  saveProject: () => Promise<void>;
  setChromeScale: (key: "headerScale" | "footerScale", v: number) => void;
}

const initialBrands = load<BrandKit[]>(BRAND_KEY, [DEFAULT_BRAND]);
const savedBundle = load<{ project: CarouselProject; brandKits: BrandKit[]; activeBrandKitId: string; savedAt: number } | null>(SAVED_KEY, null);
const initialProject = load<CarouselProject | null>(PROJECT_KEY, null) ?? savedBundle?.project ?? null;
const initialApiKey = load<string>("carouselfy.apiKey", "");
const initialApiProvider = load<"openai" | "groq" | "gemini" | "anthropic" | "openrouter">("carouselfy.apiProvider", "openai");

export const useStudio = create<StudioState>((set, get) => {
  let lastSnap = 0;
  let lastSnapKey = "";
  // Push the current project onto the undo stack. Rapid edits of the same kind
  // (e.g. dragging) within 500ms are coalesced into one history step.
  const snapshot = (key = "") => {
    const now = Date.now();
    if (key && key === lastSnapKey && now - lastSnap < 500) {
      lastSnap = now;
      return;
    }
    lastSnap = now;
    lastSnapKey = key;
    set({ past: [...get().past, get().project].slice(-HISTORY_LIMIT), future: [] });
  };
  const persist = () => {
    save(BRAND_KEY, get().brandKits);
    save(PROJECT_KEY, get().project);
    if (get().saveStatus !== "unsaved") set({ saveStatus: "unsaved" });
  };
  const withSlides = (fn: (slides: Slide[]) => Slide[], key = "") => {
    snapshot(key);
    const p = get().project;
    const slides = fn([...p.slides]).map((s, i) => ({ ...s, slideNumber: i + 1 }));
    set({ project: { ...p, slides } });
    persist();
  };

  return {
    brandKits: initialBrands.length ? initialBrands : [DEFAULT_BRAND],
    activeBrandKitId: (initialBrands[0] ?? DEFAULT_BRAND).id,
    project:
      initialProject ??
      makeProject(
        "Python Data Types",
        7,
        "Educational",
        DEFAULT_TEMPLATE,
        initialBrands[0] ?? DEFAULT_BRAND,
        "4:5",
      ),
    activeSlide: 0,
    selectedElementId: null,
    zoom: "fit",
    showGuides: true,
    generating: false,
    genLog: [],
    apiKey: initialApiKey,
    apiProvider: initialApiProvider,
    past: [],
    future: [],
    saveStatus: savedBundle ? "saved" : "unsaved",
    lastSavedAt: savedBundle?.savedAt ?? null,

    brand: () => get().brandKits.find((b) => b.id === get().activeBrandKitId) ?? DEFAULT_BRAND,
    setBrand: (patch) => {
      set({
        brandKits: get().brandKits.map((b) =>
          b.id === get().activeBrandKitId ? { ...b, ...patch } : b,
        ),
      });
      persist();
    },
    addBrandKit: () => {
      const kit: BrandKit = { ...DEFAULT_BRAND, id: uid("bk"), name: "New Brand", handle: "@newbrand" };
      set({ brandKits: [...get().brandKits, kit], activeBrandKitId: kit.id });
      persist();
    },
    selectBrandKit: (id) => {
      set({ activeBrandKitId: id, project: { ...get().project, brandKitId: id } });
      get().applyTemplate(get().project.templateId);
    },
    deleteBrandKit: (id) => {
      const rest = get().brandKits.filter((b) => b.id !== id);
      const kits = rest.length ? rest : [DEFAULT_BRAND];
      set({ brandKits: kits, activeBrandKitId: kits[0]!.id });
      persist();
    },

    setProject: (p) => {
      snapshot();
      set({ project: p });
      persist();
    },

    generate: (topic, count, tone, ai) => {
      snapshot();
      const p = makeProject(topic, count, tone, get().project.templateId, get().brand(), get().project.aspectRatio, ai);
      set({ project: p, activeSlide: 0, selectedElementId: null });
      persist();
    },

    applyTemplate: (templateId) => {
      snapshot("template");
      const p = get().project;
      const tpl = getTemplate(templateId);
      const slides = reflowSlides(p.slides, {
        tpl,
        brand: get().brand(),
        ratio: p.aspectRatio,
        category: p.category ?? categoryFor(p.topic),
        watermark: p.showWatermark,
        progress: p.showProgress,
        headerScale: p.headerScale,
        footerScale: p.footerScale,
      });
      set({ project: { ...p, templateId, slides }, selectedElementId: null });
      persist();
    },

    setAspectRatio: (r) => {
      snapshot("template");
      const p = { ...get().project, aspectRatio: r };
      set({ project: p });
      get().applyTemplate(p.templateId);
    },

    toggleChrome: (key) => {
      snapshot("template");
      const p = { ...get().project, [key]: !get().project[key] };
      set({ project: p });
      get().applyTemplate(p.templateId);
    },

    setActiveSlide: (i) => set({ activeSlide: i, selectedElementId: null }),

    addSlide: () => {
      const p = get().project;
      const tpl = getTemplate(p.templateId);
      const slide = buildFromData(
        {
          slideNumber: p.slides.length + 1,
          archetype: "deep_dive",
          icon: "sparkles",
          headerBadge: "NEW SLIDE",
          headline: "New slide title",
          definition: "Double-click any text on the canvas to edit it.",
        },
        {
          tpl,
          brand: get().brand(),
          ratio: p.aspectRatio,
          index: p.slides.length,
          total: p.slides.length + 1,
          category: p.category ?? categoryFor(p.topic),
          watermark: p.showWatermark,
          progress: p.showProgress,
          headerScale: p.headerScale,
          footerScale: p.footerScale,
        },
      );
      withSlides((s) => [...s, slide]);
      set({ activeSlide: get().project.slides.length - 1 });
    },

    duplicateSlide: (i) =>
      withSlides((s) => {
        const src = s[i];
        if (!src) return s;
        const copy: Slide = {
          ...src,
          id: uid("slide"),
          elements: src.elements.map((e) => ({ ...e, id: uid("el") })),
        };
        s.splice(i + 1, 0, copy);
        return s;
      }),

    deleteSlide: (i) => {
      if (get().project.slides.length <= 1) return;
      withSlides((s) => s.filter((_, idx) => idx !== i));
      set({ activeSlide: Math.max(0, Math.min(i, get().project.slides.length - 1)), selectedElementId: null });
    },

    reorderSlides: (from, to) =>
      withSlides((s) => {
        const [m] = s.splice(from, 1);
        if (m) s.splice(to, 0, m);
        return s;
      }),

    updateSlide: (i, patch) => withSlides((s) => s.map((sl, idx) => (idx === i ? { ...sl, ...patch } : sl))),

    select: (id) => set({ selectedElementId: id }),

    updateElement: (id, patch) =>
      withSlides(
        (s) =>
        s.map((sl, idx) =>
          idx === get().activeSlide
            ? (() => {
                const target = sl.elements.find((e) => e.id === id);
                const data =
                  sl.data && target?.dataKey && typeof patch.content === "string" && patch.content !== target.content
                    ? applyEditToData(sl.data, target.dataKey, patch.content)
                    : sl.data;
                return { ...sl, data, elements: sl.elements.map((e) => (e.id === id ? { ...e, ...patch } : e)) };
              })()
            : sl,
        ),
        `el:${id}:${Object.keys(patch).sort().join(",")}`,
      ),

    addElement: (type, content) => {
      const id = uid("el");
      const base: SlideElement = {
        id,
        type,
        content: content ?? (type === "code" ? "console.log('hello')" : type === "shape" ? "" : "New text"),
        x: 140,
        y: 400,
        width: type === "shape" ? 400 : 700,
        height: type === "code" ? 300 : type === "shape" ? 200 : 100,
        fontSize: type === "heading" ? 72 : 34,
        fontWeight: type === "heading" ? 800 : 500,
        color: "#ffffff",
        backgroundColor: type === "shape" ? "#6366f1" : type === "code" ? "#0b1120" : "transparent",
        textAlign: "left",
        zIndex: 20,
        isLocked: false,
        isBrandElement: false,
        opacity: 1,
        rotation: 0,
        letterSpacing: 0,
        lineHeight: 1.3,
        radius: 20,
      };
      withSlides((s) =>
        s.map((sl, idx) => (idx === get().activeSlide ? { ...sl, elements: [...sl.elements, base] } : sl)),
      );
      set({ selectedElementId: id });
    },

    deleteElement: (id) => {
      withSlides((s) =>
        s.map((sl, idx) =>
          idx === get().activeSlide ? { ...sl, elements: sl.elements.filter((e) => e.id !== id) } : sl,
        ),
      );
      set({ selectedElementId: null });
    },

    duplicateElement: (id) => {
      const newId = uid("el");
      withSlides((s) =>
        s.map((sl, idx) => {
          if (idx !== get().activeSlide) return sl;
          const src = sl.elements.find((e) => e.id === id);
          if (!src) return sl;
          return { ...sl, elements: [...sl.elements, { ...src, id: newId, x: src.x + 32, y: src.y + 32 }] };
        }),
      );
      set({ selectedElementId: newId });
    },

    reorderElement: (id, dir) =>
      withSlides((s) =>
        s.map((sl, idx) =>
          idx === get().activeSlide
            ? {
                ...sl,
                elements: sl.elements.map((e) =>
                  e.id === id ? { ...e, zIndex: Math.max(0, e.zIndex + dir) } : e,
                ),
              }
            : sl,
        ),
      ),

    setZoom: (z) => set({ zoom: z }),
    toggleGuides: () => set({ showGuides: !get().showGuides }),
    setApiKey: (k) => {
      set({ apiKey: k });
      save("carouselfy.apiKey", k);
    },
    setApiProvider: (p) => {
      set({ apiProvider: p });
      save("carouselfy.apiProvider", p);
    },
    pushLog: (l) => set({ genLog: [...get().genLog, l] }),
    setGenerating: (g) => set({ generating: g, genLog: g ? [] : get().genLog }),
    autoFix: () => {
      snapshot();
      set({ project: autoFixProject(get().project) });
      persist();
    },
    undo: () => {
      const past = get().past;
      const prev = past[past.length - 1];
      if (!prev) return;
      lastSnapKey = "";
      const slides = prev.slides.length;
      set({
        past: past.slice(0, -1),
        future: [get().project, ...get().future].slice(0, HISTORY_LIMIT),
        project: prev,
        selectedElementId: null,
        activeSlide: Math.min(get().activeSlide, slides - 1),
      });
      persist();
    },
    redo: () => {
      const [next, ...rest] = get().future;
      if (!next) return;
      lastSnapKey = "";
      set({
        future: rest,
        past: [...get().past, get().project].slice(-HISTORY_LIMIT),
        project: next,
        selectedElementId: null,
        activeSlide: Math.min(get().activeSlide, next.slides.length - 1),
      });
      persist();
    },
    saveProject: async () => {
      set({ saveStatus: "saving" });
      const savedAt = Date.now();
      const currentProject = get().project;
      const currentBrands = get().brandKits;
      save(SAVED_KEY, {
        project: currentProject,
        brandKits: currentBrands,
        activeBrandKitId: get().activeBrandKitId,
        savedAt,
      });
      save(BRAND_KEY, currentBrands);
      save(PROJECT_KEY, currentProject);

      try {
        await fetch("/api/projects/save.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            project: currentProject,
            brandKits: currentBrands,
          }),
        });
      } catch (err) {
        console.warn("Could not save project to remote database:", err);
      }

      await new Promise((r) => setTimeout(r, 200));
      set({ saveStatus: "saved", lastSavedAt: savedAt });
    },
    setChromeScale: (key, v) => {
      snapshot(`chrome:${key}`);
      const p = { ...get().project, [key]: v };
      const tpl = getTemplate(p.templateId);
      const slides = reflowSlides(p.slides, {
        tpl,
        brand: get().brand(),
        ratio: p.aspectRatio,
        category: p.category ?? categoryFor(p.topic),
        watermark: p.showWatermark,
        progress: p.showProgress,
        headerScale: p.headerScale,
        footerScale: p.footerScale,
      });
      set({ project: { ...p, slides } });
      persist();
    },
  };
});

export { TEMPLATES };
