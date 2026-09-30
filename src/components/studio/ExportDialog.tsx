import { useState } from "react";
import { Download, FileImage, FileText, Package } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import { useStudio } from "@/store/studio";
import { exportCurrent, exportPdf, exportZip } from "@/lib/exporter";

export function ExportDialog() {
  const { project, activeSlide } = useStudio();
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState<string | null>(null);
  const [progress, setProgress] = useState(0);

  const run = async (label: string, fn: () => Promise<void>) => {
    setBusy(label);
    setProgress(5);
    try {
      await fn();
      setProgress(100);
      toast.success(`${label} ready`);
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "Export failed");
    } finally {
      setBusy(null);
      setTimeout(() => setProgress(0), 600);
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button size="sm">
          <Download className="mr-1.5 size-4" /> Export
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Export carousel</DialogTitle>
          <DialogDescription>
            Rendered off-screen at true {project.aspectRatio} resolution, 2× for retina.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-2">
          <Button
            variant="outline"
            disabled={!!busy}
            className="justify-start"
            onClick={() =>
              run("ZIP", () => exportZip(project.slides.length, project.aspectRatio, project.title, setProgress))
            }
          >
            <Package className="mr-2 size-4" /> Download all as ZIP ({project.slides.length} PNGs)
          </Button>
          <Button
            variant="outline"
            disabled={!!busy}
            className="justify-start"
            onClick={() =>
              run("PDF", () => exportPdf(project.slides.length, project.aspectRatio, project.title, setProgress))
            }
          >
            <FileText className="mr-2 size-4" /> Download multi-page PDF
          </Button>
          <Button
            variant="outline"
            disabled={!!busy}
            className="justify-start"
            onClick={() =>
              run("PNG", () => exportCurrent(activeSlide, project.aspectRatio, "png", project.title))
            }
          >
            <FileImage className="mr-2 size-4" /> Download current slide (PNG)
          </Button>
          <Button
            variant="outline"
            disabled={!!busy}
            className="justify-start"
            onClick={() =>
              run("JPG", () => exportCurrent(activeSlide, project.aspectRatio, "jpg", project.title))
            }
          >
            <FileImage className="mr-2 size-4" /> Download current slide (JPG)
          </Button>
        </div>
        {busy && (
          <div className="space-y-2">
            <Progress value={progress} />
            <p className="text-xs text-muted-foreground">Rendering {busy}… {progress}%</p>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
