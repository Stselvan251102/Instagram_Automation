import { toPng, toJpeg } from "html-to-image";
import { jsPDF } from "jspdf";
import JSZip from "jszip";

import type { AspectRatio } from "@/types/carousel";
import { CANVAS_SIZES } from "@/types/carousel";

const PIXEL_RATIO = 2;

async function saveAs(data: Blob | string, filename: string) {
  const mod = await import("file-saver");
  const fn = (mod as unknown as { default?: { saveAs?: typeof mod.saveAs }; saveAs?: typeof mod.saveAs });
  (fn.saveAs ?? fn.default?.saveAs)!(data as Blob, filename);
}

function nodeFor(index: number) {
  return document.getElementById(`export-slide-${index}`);
}

async function render(index: number, ratio: AspectRatio, format: "png" | "jpg") {
  const node = nodeFor(index);
  if (!node) throw new Error(`Slide ${index + 1} is not ready to export.`);
  const { w, h } = CANVAS_SIZES[ratio];
  const opts = {
    width: w,
    height: h,
    pixelRatio: PIXEL_RATIO,
    cacheBust: true,
    backgroundColor: "#000000",
    style: { transform: "none", margin: "0" },
  };
  return format === "png" ? toPng(node, opts) : toJpeg(node, { ...opts, quality: 0.95 });
}

const pad = (n: number) => String(n + 1).padStart(2, "0");

export async function exportCurrent(
  index: number,
  ratio: AspectRatio,
  format: "png" | "jpg",
  title: string,
) {
  const data = await render(index, ratio, format);
  await saveAs(data, `${slug(title)}-slide-${pad(index)}.${format}`);
}

export async function exportZip(
  count: number,
  ratio: AspectRatio,
  title: string,
  onProgress: (p: number) => void,
) {
  const zip = new JSZip();
  for (let i = 0; i < count; i++) {
    const data = await render(i, ratio, "png");
    zip.file(`slide-${pad(i)}.png`, data.split(",")[1]!, { base64: true });
    onProgress(Math.round(((i + 1) / count) * 100));
  }
  const blob = await zip.generateAsync({ type: "blob" });
  await saveAs(blob, `${slug(title)}.zip`);
}

export async function exportPdf(
  count: number,
  ratio: AspectRatio,
  title: string,
  onProgress: (p: number) => void,
) {
  const { w, h } = CANVAS_SIZES[ratio];
  const pdf = new jsPDF({ orientation: h > w ? "portrait" : "landscape", unit: "px", format: [w, h] });
  for (let i = 0; i < count; i++) {
    const data = await render(i, ratio, "jpg");
    if (i > 0) pdf.addPage([w, h], h > w ? "portrait" : "landscape");
    pdf.addImage(data, "JPEG", 0, 0, w, h);
    onProgress(Math.round(((i + 1) / count) * 100));
  }
  pdf.save(`${slug(title)}.pdf`);
}

function slug(s: string) {
  return (
    s
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "") || "carousel"
  );
}
