import {
  AlignCenter,
  AlignLeft,
  AlignRight,
  Bold,
  Copy,
  Italic,
  Layers,
  Trash2,
} from "lucide-react";
import type { SlideElement } from "@/types/carousel";
import { Slider } from "@/components/ui/slider";
import { Button } from "@/components/ui/button";
import { useStudio } from "@/store/studio";

export function ElementToolbar({ el }: { el: SlideElement }) {
  const { updateElement, duplicateElement, deleteElement, reorderElement } = useStudio();

  return (
    <div className="flex flex-wrap items-center gap-2">
      <div className="flex w-36 items-center gap-2 px-1">
        <span className="text-[10px] uppercase text-muted-foreground">Size</span>
        <Slider
          value={[el.fontSize]}
          min={12}
          max={140}
          step={1}
          onValueChange={(v) => updateElement(el.id, { fontSize: v[0] ?? el.fontSize })}
        />
        <span className="w-7 text-right text-[11px] tabular-nums text-muted-foreground">{el.fontSize}</span>
      </div>
      <Button
        size="icon"
        variant={el.fontWeight >= 700 ? "default" : "ghost"}
        onClick={() => updateElement(el.id, { fontWeight: el.fontWeight >= 700 ? 500 : 800 })}
      >
        <Bold className="size-4" />
      </Button>
      <Button
        size="icon"
        variant={el.fontStyle === "italic" ? "default" : "ghost"}
        onClick={() => updateElement(el.id, { fontStyle: el.fontStyle === "italic" ? "normal" : "italic" })}
      >
        <Italic className="size-4" />
      </Button>
      {(["left", "center", "right"] as const).map((a) => {
        const Icon = a === "left" ? AlignLeft : a === "center" ? AlignCenter : AlignRight;
        return (
          <Button
            key={a}
            size="icon"
            variant={el.textAlign === a ? "default" : "ghost"}
            onClick={() => updateElement(el.id, { textAlign: a })}
          >
            <Icon className="size-4" />
          </Button>
        );
      })}
      <input
        type="color"
        value={el.color}
        onChange={(e) => updateElement(el.id, { color: e.target.value })}
        className="size-8 cursor-pointer rounded border border-border bg-transparent"
        title="Text colour"
      />
      <input
        type="color"
        value={el.backgroundColor === "transparent" ? "#000000" : el.backgroundColor}
        onChange={(e) => updateElement(el.id, { backgroundColor: e.target.value })}
        className="size-8 cursor-pointer rounded border border-border bg-transparent"
        title="Fill colour"
      />
      <Button size="icon" variant="ghost" onClick={() => reorderElement(el.id, 1)} title="Bring forward">
        <Layers className="size-4" />
      </Button>
      <Button size="icon" variant="ghost" onClick={() => reorderElement(el.id, -1)} title="Send backward">
        <Layers className="size-4 rotate-180" />
      </Button>
      <Button size="icon" variant="ghost" onClick={() => duplicateElement(el.id)}>
        <Copy className="size-4" />
      </Button>
      <Button size="icon" variant="ghost" onClick={() => deleteElement(el.id)}>
        <Trash2 className="size-4 text-destructive" />
      </Button>
    </div>
  );
}
