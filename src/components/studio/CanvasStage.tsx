import { useEffect, useRef, useState } from "react";
import { Eye, EyeOff, Maximize2, Redo2, Undo2 } from "lucide-react";
import { CANVAS_SIZES, type AspectRatio, type SlideElement } from "@/types/carousel";
import { useStudio } from "@/store/studio";
import { SlideRenderer } from "./SlideRenderer";
import { SafeGuides } from "./SafeGuides";
import { ElementToolbar } from "./ElementToolbar";
import { Button } from "@/components/ui/button";

const ZOOMS = [0.5, 0.75, 1, 1.5];

export function CanvasStage() {
  const {
    project,
    activeSlide,
    selectedElementId,
    select,
    updateElement,
    zoom,
    setZoom,
    showGuides,
    toggleGuides,
    setAspectRatio,
    undo,
    redo,
    past,
    future,
  } = useStudio();
  const slide = project.slides[activeSlide];
  const { w, h } = CANVAS_SIZES[project.aspectRatio];
  const wrapRef = useRef<HTMLDivElement>(null);
  const [fitScale, setFitScale] = useState(0.3);
  const [editingId, setEditingId] = useState<string | null>(null);
  const drag = useRef<{ id: string; mode: "move" | "resize"; sx: number; sy: number; ox: number; oy: number; ow: number; oh: number; scale: number } | null>(null);

  useEffect(() => {
    const update = () => {
      const node = wrapRef.current;
      if (!node) return;
      const pad = 48;
      setFitScale(Math.min((node.clientWidth - pad) / w, (node.clientHeight - pad) / h));
    };
    update();
    const ro = new ResizeObserver(update);
    if (wrapRef.current) ro.observe(wrapRef.current);
    return () => ro.disconnect();
  }, [w, h]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const t = e.target as HTMLElement | null;
      if (t && (t.isContentEditable || ["INPUT", "TEXTAREA", "SELECT"].includes(t.tagName))) return;
      if (!(e.ctrlKey || e.metaKey)) return;
      const k = e.key.toLowerCase();
      if (k === "z" && !e.shiftKey) {
        e.preventDefault();
        undo();
      } else if (k === "y" || (k === "z" && e.shiftKey)) {
        e.preventDefault();
        redo();
      }
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [undo, redo]);

  const scale = zoom === "fit" ? fitScale : zoom;
  const selected = slide?.elements.find((e) => e.id === selectedElementId) ?? null;

  const onPointerDownElement = (e: React.PointerEvent, el: SlideElement, mode: "move" | "resize") => {
    (e.target as HTMLElement).setPointerCapture?.(e.pointerId);
    drag.current = {
      id: el.id,
      mode,
      sx: e.clientX,
      sy: e.clientY,
      ox: el.x,
      oy: el.y,
      ow: el.width,
      oh: el.height,
      scale,
    };
  };

  useEffect(() => {
    const move = (e: PointerEvent) => {
      const d = drag.current;
      if (!d) return;
      const dx = (e.clientX - d.sx) / d.scale;
      const dy = (e.clientY - d.sy) / d.scale;
      if (d.mode === "move") {
        updateElement(d.id, { x: Math.round(d.ox + dx), y: Math.round(d.oy + dy) });
      } else {
        updateElement(d.id, {
          width: Math.max(40, Math.round(d.ow + dx)),
          height: Math.max(30, Math.round(d.oh + dy)),
        });
      }
    };
    const up = () => (drag.current = null);
    window.addEventListener("pointermove", move);
    window.addEventListener("pointerup", up);
    return () => {
      window.removeEventListener("pointermove", move);
      window.removeEventListener("pointerup", up);
    };
  }, [updateElement]);

  if (!slide) return null;

  return (
    <div className="flex h-full min-h-0 flex-col">
      <div className="flex flex-wrap items-center gap-2 border-b border-border bg-card/40 px-3 py-2">
        <div className="flex rounded-lg border border-border p-0.5">
          {(Object.keys(CANVAS_SIZES) as AspectRatio[]).map((r) => (
            <button
              key={r}
              onClick={() => setAspectRatio(r)}
              className={`rounded-md px-2.5 py-1 text-xs font-medium transition-colors ${
                project.aspectRatio === r ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"
              }`}
            >
              {r}
            </button>
          ))}
        </div>
        <Button size="sm" variant={showGuides ? "default" : "outline"} onClick={toggleGuides}>
          {showGuides ? <Eye className="mr-1 size-3.5" /> : <EyeOff className="mr-1 size-3.5" />}
          Safe guides
        </Button>
        <div className="flex items-center gap-1">
          <Button size="icon" variant="ghost" className="size-8" disabled={!past.length} onClick={undo} title="Undo (Ctrl+Z)" aria-label="Undo">
            <Undo2 className="size-4" />
          </Button>
          <Button size="icon" variant="ghost" className="size-8" disabled={!future.length} onClick={redo} title="Redo (Ctrl+Y)" aria-label="Redo">
            <Redo2 className="size-4" />
          </Button>
        </div>
        <div className="ml-auto flex items-center gap-1">
          <Button size="sm" variant={zoom === "fit" ? "default" : "ghost"} onClick={() => setZoom("fit")}>
            <Maximize2 className="mr-1 size-3.5" />
            Fit
          </Button>
          {ZOOMS.map((z) => (
            <Button key={z} size="sm" variant={zoom === z ? "default" : "ghost"} onClick={() => setZoom(z)}>
              {z * 100}%
            </Button>
          ))}
        </div>
      </div>

      {selected && (
        <div className="border-b border-border bg-card/60 px-3 py-1.5">
          <ElementToolbar el={selected} />
        </div>
      )}

      <div ref={wrapRef} className="relative flex min-h-0 flex-1 items-center justify-center overflow-auto bg-background p-6">
        <div
          style={{
            width: w * scale,
            height: h * scale,
            position: "relative",
            flex: "0 0 auto",
          }}
        >
          <div
            style={{
              width: w,
              height: h,
              transform: `scale(${scale})`,
              transformOrigin: "top left",
              position: "absolute",
              top: 0,
              left: 0,
              boxShadow: "0 24px 80px rgba(0,0,0,.6)",
              borderRadius: 4,
              overflow: "hidden",
            }}
          >
            <SlideRenderer
              slide={slide}
              width={w}
              height={h}
              interactive
              selectedId={selectedElementId}
              onSelect={(id) => {
                select(id);
                if (!id) setEditingId(null);
              }}
              onPointerDownElement={onPointerDownElement}
              editingId={editingId}
              onEditText={(el) => setEditingId(el.id)}
              onCommitText={(id, value) => {
                updateElement(id, { content: value });
                setEditingId(null);
              }}
            />
            {showGuides && <SafeGuides width={w} height={h} />}
          </div>
        </div>
      </div>
    </div>
  );
}
