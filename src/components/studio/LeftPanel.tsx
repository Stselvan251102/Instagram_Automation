import { useMemo, useRef, useState } from "react";
import { KeyRound, Plus, Sparkles, Trash2, Upload, Wand2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Slider } from "@/components/ui/slider";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";
import { useStudio } from "@/store/studio";
import { FILTER_TAGS, TEMPLATES } from "@/lib/templates";
import { GEN_STEPS, TONES, type Tone } from "@/lib/generator";
import { generateCarousel } from "@/lib/ai.functions";

function ColorField({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
  return (
    <label className="flex items-center justify-between gap-2 rounded-lg border border-border px-2.5 py-1.5">
      <span className="text-xs text-muted-foreground">{label}</span>
      <input
        type="color"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="size-6 cursor-pointer rounded border border-border bg-transparent"
      />
    </label>
  );
}

export function LeftPanel() {
  const {
    brandKits,
    activeBrandKitId,
    brand,
    setBrand,
    addBrandKit,
    selectBrandKit,
    deleteBrandKit,
    project,
    applyTemplate,
    generate,
    generating,
    setGenerating,
    genLog,
    pushLog,
    apiKey,
    setApiKey,
    apiProvider,
    setApiProvider,
  } = useStudio();
  const kit = brand();
  const fileRef = useRef<HTMLInputElement>(null);

  const [topic, setTopic] = useState(project.topic || "Python Data Types");
  const [count, setCount] = useState(project.slides.length || 7);
  const [tone, setTone] = useState<Tone>("Educational");
  const [filter, setFilter] = useState("All");
  const [keyOpen, setKeyOpen] = useState(false);

  const templates = useMemo(() => {
    const list = filter === "All" ? TEMPLATES : TEMPLATES.filter((t) => t.tags.includes(filter));
    return list.slice(0, 60);
  }, [filter]);

  const onUpload = (files: FileList | null) => {
    if (!files) return;
    Array.from(files).forEach((file) => {
      const reader = new FileReader();
      reader.onload = () => {
        setBrand({ logos: [...brand().logos, String(reader.result)] });
      };
      reader.readAsDataURL(file);
    });
  };

  const runGenerate = async () => {
    setGenerating(true);
    const steps = GEN_STEPS(topic, count);
    let i = 0;
    pushLog(steps[i++]!);
    const ticker = setInterval(() => {
      if (i < steps.length) pushLog(steps[i++]!);
    }, 1600);
    try {
      const res = await generateCarousel({ data: { topic: topic.trim() || "Your Topic", count, tone } });
      if (res.ok) {
        generate(topic, count, tone, { slides: res.slides, category: res.category, title: res.title });
        toast.success(`Generated ${res.slides.length} slides for "${topic}"`);
      } else {
        generate(topic, count, tone);
        toast.warning(`${res.error} Showing built-in sample content instead.`);
      }
    } catch {
      generate(topic, count, tone);
      toast.warning("Couldn't reach AI — showing built-in sample content instead.");
    } finally {
      clearInterval(ticker);
      setGenerating(false);
    }
  };

  return (
    <aside className="flex h-full w-[330px] shrink-0 flex-col border-r border-border bg-card/30">
      <Tabs defaultValue="ai" className="flex min-h-0 flex-1 flex-col">
        <TabsList className="m-2 grid grid-cols-3">
          <TabsTrigger value="ai">AI</TabsTrigger>
          <TabsTrigger value="brand">Brand</TabsTrigger>
          <TabsTrigger value="templates">Templates</TabsTrigger>
        </TabsList>

        <TabsContent value="ai" className="min-h-0 flex-1 space-y-4 overflow-y-auto px-3 pb-6">
          <div className="space-y-2">
            <Label>Topic or keyword</Label>
            <Input
              value={topic}
              onChange={(e) => setTopic(e.target.value)}
              placeholder="e.g. Salesforce Architecture"
            />
            <div className="flex flex-wrap gap-1.5">
              {["Python Data Types", "Salesforce Architecture"].map((s) => (
                <button
                  key={s}
                  onClick={() => setTopic(s)}
                  className="rounded-full border border-border px-2 py-0.5 text-[11px] text-muted-foreground hover:text-foreground"
                >
                  {s}
                </button>
              ))}
            </div>
          </div>

          <div className="space-y-2">
            <Label>Slides: {count}</Label>
            <Slider value={[count]} min={3} max={10} step={1} onValueChange={(v) => setCount(v[0] ?? count)} />
          </div>

          <div className="space-y-2">
            <Label>Tone</Label>
            <Select value={tone} onValueChange={(v) => setTone(v as Tone)}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TONES.map((t) => (
                  <SelectItem key={t} value={t}>
                    {t}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <Button className="w-full" onClick={runGenerate} disabled={generating}>
            <Sparkles className="mr-1.5 size-4" />
            {generating ? "Generating…" : "Generate carousel"}
          </Button>

          <Dialog open={keyOpen} onOpenChange={setKeyOpen}>
            <DialogTrigger asChild>
              <Button variant="outline" className="w-full">
                <KeyRound className="mr-1.5 size-4" /> Bring your own API key
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Bring your own key</DialogTitle>
                <DialogDescription>
                  Stored in this browser only. Without a key the studio uses realistic mock generation.
                </DialogDescription>
              </DialogHeader>
              <Select value={apiProvider} onValueChange={(v) => setApiProvider(v as "openai" | "anthropic")}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="openai">OpenAI</SelectItem>
                  <SelectItem value="anthropic">Anthropic</SelectItem>
                </SelectContent>
              </Select>
              <Input
                type="password"
                placeholder="sk-…"
                value={apiKey}
                onChange={(e) => setApiKey(e.target.value)}
              />
              <DialogFooter>
                <Button
                  onClick={() => {
                    setKeyOpen(false);
                    toast.success(apiKey ? "Key saved for this session" : "Using mock generation");
                  }}
                >
                  Save
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          {genLog.length > 0 && (
            <div className="space-y-1 rounded-lg border border-border bg-background/60 p-2.5">
              {genLog.map((l, i) => (
                <p key={i} className="text-[11px] text-muted-foreground">
                  ▸ {l}
                </p>
              ))}
            </div>
          )}
        </TabsContent>

        <TabsContent value="brand" className="min-h-0 flex-1 space-y-4 overflow-y-auto px-3 pb-6">
          <div className="flex gap-2">
            <Select value={activeBrandKitId} onValueChange={selectBrandKit}>
              <SelectTrigger>
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
            <Button size="icon" variant="outline" onClick={addBrandKit}>
              <Plus className="size-4" />
            </Button>
            <Button size="icon" variant="outline" onClick={() => deleteBrandKit(activeBrandKitId)}>
              <Trash2 className="size-4" />
            </Button>
          </div>

          <div
            onDragOver={(e) => e.preventDefault()}
            onDrop={(e) => {
              e.preventDefault();
              onUpload(e.dataTransfer.files);
            }}
            onClick={() => fileRef.current?.click()}
            className="cursor-pointer rounded-xl border-2 border-dashed border-border p-4 text-center text-xs text-muted-foreground hover:border-primary"
          >
            <Upload className="mx-auto mb-1 size-5" />
            Drag & drop logos, or click to upload
            <input
              ref={fileRef}
              type="file"
              accept="image/*"
              multiple
              hidden
              onChange={(e) => onUpload(e.target.files)}
            />
          </div>

          {kit.logos.length > 0 && (
            <div className="flex flex-wrap gap-2">
              {kit.logos.map((logo, i) => (
                <button
                  key={i}
                  onClick={() => setBrand({ activeLogoIndex: i })}
                  className={`relative size-14 overflow-hidden rounded-lg border-2 ${
                    kit.activeLogoIndex === i ? "border-primary" : "border-border"
                  }`}
                >
                  <img src={logo} alt="" className="size-full object-cover" />
                  {kit.activeLogoIndex === i && (
                    <span className="absolute inset-x-0 bottom-0 bg-primary text-[9px] text-primary-foreground">
                      active
                    </span>
                  )}
                </button>
              ))}
            </div>
          )}

          <div className="space-y-2">
            <Label>Brand name</Label>
            <Input value={kit.name} onChange={(e) => setBrand({ name: e.target.value })} />
            <Label>Instagram handle</Label>
            <Input value={kit.handle} onChange={(e) => setBrand({ handle: e.target.value })} />
            <Label>Website / profile URL</Label>
            <Input value={kit.profileUrl} onChange={(e) => setBrand({ profileUrl: e.target.value })} />
          </div>

          <div className="grid grid-cols-2 gap-2">
            <ColorField label="Primary" value={kit.primaryColor} onChange={(v) => setBrand({ primaryColor: v })} />
            <ColorField label="Secondary" value={kit.secondaryColor} onChange={(v) => setBrand({ secondaryColor: v })} />
            <ColorField label="Accent" value={kit.accentColor} onChange={(v) => setBrand({ accentColor: v })} />
            <ColorField label="Canvas" value={kit.canvasColor} onChange={(v) => setBrand({ canvasColor: v })} />
          </div>
          <p className="text-[11px] text-muted-foreground">Brand presets are saved to this browser automatically.</p>
        </TabsContent>

        <TabsContent value="templates" className="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 pb-6">
          <div className="flex flex-wrap gap-1.5">
            {FILTER_TAGS.map((t) => (
              <button
                key={t}
                onClick={() => setFilter(t)}
                className={`rounded-full border px-2.5 py-1 text-[11px] transition-colors ${
                  filter === t
                    ? "border-primary bg-primary text-primary-foreground"
                    : "border-border text-muted-foreground hover:text-foreground"
                }`}
              >
                {t}
              </button>
            ))}
          </div>
          <p className="text-[11px] text-muted-foreground">
            {TEMPLATES.length} generated styles · showing {templates.length}
          </p>
          <div className="grid grid-cols-2 gap-2">
            {templates.map((t) => (
              <button
                key={t.id}
                onClick={() => {
                  applyTemplate(t.id);
                  toast.success(`Applied ${t.name}`);
                }}
                className={`overflow-hidden rounded-lg border-2 text-left transition-colors ${
                  project.templateId === t.id ? "border-primary" : "border-border hover:border-muted-foreground"
                }`}
              >
                <div
                  className="flex h-20 flex-col justify-end gap-1 p-2"
                  style={{ background: t.theme.bgCss }}
                >
                  <span
                    className="h-2 w-10 rounded"
                    style={{ background: t.theme.accent }}
                  />
                  <span className="h-1.5 w-16 rounded" style={{ background: t.theme.fg, opacity: 0.8 }} />
                  <span className="h-1.5 w-12 rounded" style={{ background: t.theme.muted, opacity: 0.6 }} />
                </div>
                <div className="p-1.5">
                  <p className="truncate text-[11px] font-medium">{t.name}</p>
                  <p className="truncate text-[10px] text-muted-foreground">{t.theme.name}</p>
                </div>
              </button>
            ))}
          </div>
          <Button variant="outline" className="w-full" onClick={() => applyTemplate(project.templateId)}>
            <Wand2 className="mr-1.5 size-4" /> Re-flow current content
          </Button>
        </TabsContent>
      </Tabs>
    </aside>
  );
}
