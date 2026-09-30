import { useState } from "react";
import { Copy, Plus, Trash2 } from "lucide-react";
import { CANVAS_SIZES } from "@/types/carousel";
import { useStudio } from "@/store/studio";
import { SlideRenderer } from "./SlideRenderer";
import { Button } from "@/components/ui/button";

export function Filmstrip() {
  const { project, activeSlide, setActiveSlide, addSlide, duplicateSlide, deleteSlide, reorderSlides } =
    useStudio();
  const { w, h } = CANVAS_SIZES[project.aspectRatio];
  const thumbW = 96;
  const scale = thumbW / w;
  const [dragIndex, setDragIndex] = useState<number | null>(null);

  return (
    <div className="flex items-center gap-3 border-t border-border bg-card/40 px-3 py-3">
      <div className="flex flex-1 gap-3 overflow-x-auto pb-1">
        {project.slides.map((slide, i) => (
          <div
            key={slide.id}
            draggable
            onDragStart={() => setDragIndex(i)}
            onDragOver={(e) => e.preventDefault()}
            onDrop={() => {
              if (dragIndex !== null && dragIndex !== i) reorderSlides(dragIndex, i);
              setDragIndex(null);
            }}
            onClick={() => setActiveSlide(i)}
            className={`group relative shrink-0 cursor-pointer overflow-hidden rounded-lg border-2 transition-colors ${
              i === activeSlide ? "border-primary" : "border-border hover:border-muted-foreground"
            }`}
            style={{ width: thumbW, height: h * scale }}
          >
            <div style={{ width: w, height: h, transform: `scale(${scale})`, transformOrigin: "top left" }}>
              <SlideRenderer slide={slide} width={w} height={h} />
            </div>
            <span className="absolute left-1 top-1 rounded bg-background/80 px-1 text-[10px] font-semibold">
              {i + 1}
            </span>
            <div className="absolute bottom-1 right-1 hidden gap-1 group-hover:flex">
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  duplicateSlide(i);
                }}
                className="rounded bg-background/90 p-1"
                title="Duplicate slide"
              >
                <Copy className="size-3" />
              </button>
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  deleteSlide(i);
                }}
                className="rounded bg-background/90 p-1"
                title="Delete slide"
              >
                <Trash2 className="size-3 text-destructive" />
              </button>
            </div>
          </div>
        ))}
      </div>
      <Button onClick={addSlide} variant="outline" size="sm" className="shrink-0">
        <Plus className="mr-1 size-4" /> Add slide
      </Button>
    </div>
  );
}
