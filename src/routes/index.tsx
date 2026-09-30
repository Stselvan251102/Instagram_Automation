import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { Check, Layers3, Loader2, PanelLeft, PanelRight, Save } from "lucide-react";
import { toast } from "sonner";
import { CANVAS_SIZES } from "@/types/carousel";
import { useStudio } from "@/store/studio";
import { LeftPanel } from "@/components/studio/LeftPanel";
import { RightPanel } from "@/components/studio/RightPanel";
import { CanvasStage } from "@/components/studio/CanvasStage";
import { Filmstrip } from "@/components/studio/Filmstrip";
import { ExportDialog } from "@/components/studio/ExportDialog";
import { SlideRenderer } from "@/components/studio/SlideRenderer";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "Carouselfy — AI Instagram Carousel Studio" },
      {
        name: "description",
        content:
          "Generate, design and export scroll-stopping Instagram carousels with an AI content engine, 500+ templates and retina PNG, ZIP and PDF export.",
      },
      { property: "og:title", content: "Carouselfy — AI Instagram Carousel Studio" },
      {
        property: "og:description",
        content:
          "AI carousel generation, a Canva-style editor and one-click ZIP or PDF export for Instagram creators.",
      },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: Studio,
});

export function Studio() {
  const { project, saveStatus, saveProject } = useStudio();
  const { w, h } = CANVAS_SIZES[project.aspectRatio];
  const [leftOpen, setLeftOpen] = useState(false);
  const [rightOpen, setRightOpen] = useState(false);

  return (
    <div className="flex h-screen flex-col overflow-hidden bg-background text-foreground">
      <header className="flex items-center gap-3 border-b border-border bg-card/50 px-3 py-2">
        <Sheet open={leftOpen} onOpenChange={setLeftOpen}>
          <SheetTrigger asChild>
            <Button size="icon" variant="ghost" className="lg:hidden">
              <PanelLeft className="size-4" />
            </Button>
          </SheetTrigger>
          <SheetContent side="left" className="w-[340px] p-0">
            <LeftPanel />
          </SheetContent>
        </Sheet>

        <div className="flex items-center gap-2">
          <span className="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground">
            <Layers3 className="size-4" />
          </span>
          <div className="leading-tight">
            <p className="text-sm font-semibold">Carouselfy</p>
            <p className="hidden text-[11px] text-muted-foreground sm:block">
              {project.title} · {project.slides.length} slides · {project.aspectRatio}
            </p>
          </div>
        </div>

        <div className="ml-auto flex items-center gap-2">
          <span className="hidden items-center gap-1 text-xs text-muted-foreground sm:flex" aria-live="polite">
            {saveStatus === "saving" ? (
              <><Loader2 className="size-3.5 animate-spin" /> Saving…</>
            ) : saveStatus === "saved" ? (
              <><Check className="size-3.5 text-primary" /> Saved</>
            ) : (
              <>Unsaved changes</>
            )}
          </span>
          <Button
            size="sm"
            variant="outline"
            disabled={saveStatus === "saving"}
            onClick={async () => {
              await saveProject();
              toast.success("Project saved");
            }}
          >
            <Save className="mr-1 size-3.5" /> Save
          </Button>
          <ExportDialog />
          <Sheet open={rightOpen} onOpenChange={setRightOpen}>
            <SheetTrigger asChild>
              <Button size="icon" variant="ghost" className="lg:hidden">
                <PanelRight className="size-4" />
              </Button>
            </SheetTrigger>
            <SheetContent side="right" className="w-[340px] p-0">
              <RightPanel />
            </SheetContent>
          </Sheet>
        </div>
      </header>

      <div className="flex min-h-0 flex-1">
        <div className="hidden lg:flex">
          <LeftPanel />
        </div>
        <main className="flex min-w-0 flex-1 flex-col">
          <div className="min-h-0 flex-1">
            <CanvasStage />
          </div>
          <Filmstrip />
        </main>
        <div className="hidden lg:flex">
          <RightPanel />
        </div>
      </div>

      {/* Off-screen full-resolution render targets for export */}
      <div style={{ position: "fixed", top: 0, left: -100000, opacity: 1, pointerEvents: "none" }} aria-hidden>
        {project.slides.map((slide, i) => (
          <div key={slide.id} id={`export-slide-${i}`} style={{ width: w, height: h }}>
            <SlideRenderer slide={slide} width={w} height={h} />
          </div>
        ))}
      </div>
    </div>
  );
}
