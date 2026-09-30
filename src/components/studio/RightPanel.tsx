import { useMemo } from "react";
import { AlertTriangle, CheckCircle2, ShieldCheck, Wand2, XCircle } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Slider } from "@/components/ui/slider";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";
import { useStudio } from "@/store/studio";
import { lintProject } from "@/lib/preflight";
import { TYPO_PAIRINGS } from "@/lib/templates";

function NumField({
  label,
  value,
  onChange,
  step = 1,
}: {
  label: string;
  value: number;
  onChange: (v: number) => void;
  step?: number;
}) {
  return (
    <div className="space-y-1">
      <Label className="text-[11px] text-muted-foreground">{label}</Label>
      <Input
        type="number"
        step={step}
        value={Math.round(value * 100) / 100}
        onChange={(e) => onChange(Number(e.target.value))}
        className="h-8"
      />
    </div>
  );
}

export function RightPanel() {
  const {
    project,
    activeSlide,
    selectedElementId,
    updateElement,
    brandKits,
    activeBrandKitId,
    selectBrandKit,
    setBrand,
    brand,
    toggleChrome,
    setChromeScale,
    autoFix,
    setActiveSlide,
    select,
  } = useStudio();
  const el = project.slides[activeSlide]?.elements.find((e) => e.id === selectedElementId) ?? null;
  const lint = useMemo(() => lintProject(project), [project]);
  const kit = brand();

  return (
    <aside className="flex h-full w-[320px] shrink-0 flex-col border-l border-border bg-card/30">
      <Tabs defaultValue="inspector" className="flex min-h-0 flex-1 flex-col">
        <TabsList className="m-2 grid grid-cols-2">
          <TabsTrigger value="inspector">Inspector</TabsTrigger>
          <TabsTrigger value="preflight">
            Pre-flight
            {lint.issues.length > 0 && (
              <span className="ml-1 rounded-full bg-destructive px-1.5 text-[10px] text-destructive-foreground">
                {lint.issues.length}
              </span>
            )}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="inspector" className="min-h-0 flex-1 space-y-4 overflow-y-auto px-3 pb-6">
          {el ? (
            <div className="space-y-3">
              <p className="text-xs uppercase tracking-wide text-muted-foreground">{el.type}</p>
              <div className="grid grid-cols-2 gap-2">
                <NumField label="X" value={el.x} onChange={(v) => updateElement(el.id, { x: v })} />
                <NumField label="Y" value={el.y} onChange={(v) => updateElement(el.id, { y: v })} />
                <NumField label="Width" value={el.width} onChange={(v) => updateElement(el.id, { width: v })} />
                <NumField label="Height" value={el.height} onChange={(v) => updateElement(el.id, { height: v })} />
                <NumField
                  label="Rotation"
                  value={el.rotation ?? 0}
                  onChange={(v) => updateElement(el.id, { rotation: v })}
                />
                <NumField
                  label="Font size"
                  value={el.fontSize}
                  onChange={(v) => updateElement(el.id, { fontSize: v })}
                />
              </div>
              <div className="space-y-1">
                <Label className="text-[11px] text-muted-foreground">
                  Opacity {Math.round((el.opacity ?? 1) * 100)}%
                </Label>
                <Slider
                  value={[(el.opacity ?? 1) * 100]}
                  min={10}
                  max={100}
                  onValueChange={(v) => updateElement(el.id, { opacity: (v[0] ?? 100) / 100 })}
                />
              </div>
              <div className="space-y-1">
                <Label className="text-[11px] text-muted-foreground">Letter spacing {el.letterSpacing ?? 0}px</Label>
                <Slider
                  value={[el.letterSpacing ?? 0]}
                  min={-5}
                  max={20}
                  onValueChange={(v) => updateElement(el.id, { letterSpacing: v[0] ?? 0 })}
                />
              </div>
              <div className="space-y-1">
                <Label className="text-[11px] text-muted-foreground">Line height {(el.lineHeight ?? 1.25).toFixed(2)}</Label>
                <Slider
                  value={[(el.lineHeight ?? 1.25) * 100]}
                  min={90}
                  max={220}
                  onValueChange={(v) => updateElement(el.id, { lineHeight: (v[0] ?? 125) / 100 })}
                />
              </div>
              <div className="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                <Label className="text-xs">Lock element</Label>
                <Switch
                  checked={el.isLocked}
                  onCheckedChange={(v) => updateElement(el.id, { isLocked: v })}
                />
              </div>
            </div>
          ) : (
            <p className="rounded-lg border border-dashed border-border p-4 text-center text-xs text-muted-foreground">
              Select an element on the canvas to edit its properties.
            </p>
          )}

          <div className="space-y-3 border-t border-border pt-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Carousel settings</p>
            <div className="space-y-1">
              <Label className="text-[11px] text-muted-foreground">Active brand kit</Label>
              <Select value={activeBrandKitId} onValueChange={selectBrandKit}>
                <SelectTrigger className="h-8">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {brandKits.map((b) => (
                    <SelectItem key={b.id} value={b.id}>
                      {b.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1">
              <Label className="text-[11px] text-muted-foreground">Font pairing</Label>
              <Select
                value={kit.fontHeading}
                onValueChange={(v) => {
                  const pair = TYPO_PAIRINGS.find((p) => p.heading === v)!;
                  setBrand({ fontHeading: pair.heading, fontBody: pair.body });
                }}
              >
                <SelectTrigger className="h-8">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {TYPO_PAIRINGS.map((p) => (
                    <SelectItem key={p.heading} value={p.heading}>
                      {p.heading.replace(/'/g, "").split(",")[0]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-center justify-between rounded-lg border border-border px-3 py-2">
              <Label className="text-xs">Watermark handle</Label>
              <Switch checked={project.showWatermark} onCheckedChange={() => toggleChrome("showWatermark")} />
            </div>
            <div className="flex items-center justify-between rounded-lg border border-border px-3 py-2">
              <Label className="text-xs">Progress indicator</Label>
              <Switch checked={project.showProgress} onCheckedChange={() => toggleChrome("showProgress")} />
            </div>
            <div className="space-y-3 rounded-lg border border-border p-3">
              <p className="text-xs font-medium">Header &amp; Footer Layout</p>
              {(
                [
                  ["headerScale", "Header height & spacing"],
                  ["footerScale", "Footer height & spacing"],
                ] as const
              ).map(([key, label]) => {
                const v = project[key] ?? 1;
                return (
                  <div key={key} className="space-y-1.5">
                    <div className="flex justify-between text-[11px] text-muted-foreground">
                      <span>{label}</span>
                      <span className="tabular-nums">{Math.round(v * 100)}%</span>
                    </div>
                    <Slider
                      value={[v]}
                      min={0.6}
                      max={1.8}
                      step={0.05}
                      onValueChange={(x) => setChromeScale(key, x[0] ?? 1)}
                    />
                  </div>
                );
              })}
              <p className="text-[10px] text-muted-foreground">Applies to every slide at once.</p>
            </div>
          </div>
        </TabsContent>

        <TabsContent value="preflight" className="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 pb-6">
          <div className="flex items-center gap-2 text-sm font-medium">
            <ShieldCheck className="size-4 text-primary" /> Quality linter
          </div>
          <div className="space-y-1.5">
            {lint.passed.map((p) => (
              <div
                key={p.rule}
                className={`flex items-center gap-2 rounded-lg border px-2.5 py-2 text-xs ${
                  p.ok ? "border-primary/40 bg-primary/10" : "border-destructive/40 bg-destructive/10"
                }`}
              >
                {p.ok ? (
                  <CheckCircle2 className="size-4 text-primary" />
                ) : (
                  <XCircle className="size-4 text-destructive" />
                )}
                {p.label}
              </div>
            ))}
          </div>

          {lint.issues.length > 0 && (
            <div className="space-y-1.5">
              {lint.issues.slice(0, 24).map((i, idx) => (
                <button
                  key={idx}
                  onClick={() => {
                    setActiveSlide(i.slideIndex);
                    if (i.elementId) select(i.elementId);
                  }}
                  className="flex w-full items-start gap-2 rounded-lg border border-border px-2.5 py-2 text-left text-[11px] text-muted-foreground hover:text-foreground"
                >
                  <AlertTriangle
                    className={`mt-0.5 size-3.5 shrink-0 ${
                      i.level === "error" ? "text-destructive" : "text-muted-foreground"
                    }`}
                  />
                  {i.message}
                </button>
              ))}
            </div>
          )}

          <Button
            className="w-full"
            onClick={() => {
              autoFix();
              toast.success("Layout auto-fixed");
            }}
          >
            <Wand2 className="mr-1.5 size-4" /> Auto-fix layout
          </Button>
        </TabsContent>
      </Tabs>
    </aside>
  );
}
