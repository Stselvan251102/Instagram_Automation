import {
  Bookmark,
  Boxes,
  Cloud,
  Code2,
  Cpu,
  Database,
  Heart,
  Layers,
  Lightbulb,
  Send,
  Shield,
  Sparkles,
  Workflow,
  Zap,
  type LucideIcon,
} from "lucide-react";
import Prism from "prismjs";
import "prismjs/components/prism-python";
import "prismjs/components/prism-typescript";
import "prismjs/components/prism-sql";
import "prismjs/components/prism-apex";
import "prismjs/components/prism-rust";
import "prismjs/components/prism-go";
import "prismjs/components/prism-java";
import "prismjs/components/prism-bash";
import type { Slide, SlideElement } from "@/types/carousel";

const LANG_ALIAS: Record<string, string> = {
  js: "javascript",
  ts: "typescript",
  py: "python",
  sh: "bash",
  shell: "bash",
  apex: "apex",
  salesforce: "apex",
  golang: "go",
};

function highlight(code: string, language?: string) {
  const lang = LANG_ALIAS[language?.toLowerCase() ?? ""] ?? language?.toLowerCase() ?? "";
  const grammar = Prism.languages[lang];
  if (grammar) return Prism.highlight(code, grammar, lang);
  return code.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

const ICONS: Record<string, LucideIcon> = {
  layers: Layers,
  cpu: Cpu,
  lightbulb: Lightbulb,
  database: Database,
  shield: Shield,
  zap: Zap,
  code: Code2,
  workflow: Workflow,
  boxes: Boxes,
  cloud: Cloud,
  sparkles: Sparkles,
};

function renderRich(text: string) {
  return text.split(/(\*\*[^*]+\*\*)/g).map((part, i) =>
    part.startsWith("**") && part.endsWith("**") ? (
      <strong key={i} style={{ fontWeight: 800 }}>
        {part.slice(2, -2)}
      </strong>
    ) : (
      <span key={i}>{part}</span>
    ),
  );
}

interface Props {
  slide: Slide;
  width: number;
  height: number;
  interactive?: boolean;
  selectedId?: string | null;
  onSelect?: (id: string | null) => void;
  onPointerDownElement?: (e: React.PointerEvent, el: SlideElement, mode: "move" | "resize") => void;
  onEditText?: (el: SlideElement) => void;
  editingId?: string | null;
  onCommitText?: (id: string, value: string) => void;
}

export function SlideRenderer({
  slide,
  width,
  height,
  interactive,
  selectedId,
  onSelect,
  onPointerDownElement,
  onEditText,
  editingId,
  onCommitText,
}: Props) {
  const ordered = [...slide.elements].sort((a, b) => a.zIndex - b.zIndex);

  return (
    <div
      style={{ width, height, background: slide.background.value, position: "relative", overflow: "hidden" }}
      onPointerDown={interactive ? () => onSelect?.(null) : undefined}
    >
      {ordered.map((el) => {
        const selected = interactive && selectedId === el.id;
        const common: React.CSSProperties = {
          position: "absolute",
          left: el.x,
          top: el.y,
          width: el.width,
          height: el.height,
          zIndex: el.zIndex,
          opacity: el.opacity ?? 1,
          transform: el.rotation ? `rotate(${el.rotation}deg)` : undefined,
          cursor: interactive && !el.isLocked ? "move" : "default",
        };

        const start = (e: React.PointerEvent) => {
          if (!interactive) return;
          e.stopPropagation();
          onSelect?.(el.id);
          if (!el.isLocked) onPointerDownElement?.(e, el, "move");
        };

        let inner: React.ReactNode = null;

        if (el.type === "shape") {
          inner = (
            <div
              style={{
                width: "100%",
                height: "100%",
                background: el.backgroundColor,
                borderRadius: el.radius,
              }}
            />
          );
        } else if (el.type === "logo" || el.type === "image") {
          inner = el.content ? (
            <img
              src={el.content}
              alt=""
              crossOrigin="anonymous"
              style={{ width: "100%", height: "100%", objectFit: "cover", borderRadius: el.radius }}
            />
          ) : (
            <div
              style={{
                width: "100%",
                height: "100%",
                borderRadius: el.radius,
                background: "rgba(148,163,184,.25)",
              }}
            />
          );
        } else if (el.type === "icon" && el.content !== "bookmark" && ICONS[el.content]) {
          const Ico = ICONS[el.content]!;
          inner = (
            <div style={{ display: "flex", alignItems: "center", justifyContent: "center", height: "100%", color: el.color }}>
              <Ico size={Math.min(el.width, el.height)} strokeWidth={2} />
            </div>
          );
        } else if (el.type === "icon") {
          inner = (
            <div
              style={{
                display: "flex",
                gap: 28,
                alignItems: "center",
                justifyContent: "center",
                height: "100%",
                color: el.color,
              }}
            >
              <Heart size={el.height * 0.7} strokeWidth={1.8} />
              <Bookmark size={el.height * 0.7} strokeWidth={1.8} />
              <Send size={el.height * 0.7} strokeWidth={1.8} />
            </div>
          );
        } else if (el.type === "code") {
          inner = (
            <div
              style={{
                width: "100%",
                height: "100%",
                background: el.backgroundColor,
                borderRadius: el.radius,
                overflow: "hidden",
                border: "1px solid rgba(148,163,184,.25)",
              }}
            >
              <div
                style={{
                  height: 56,
                  display: "flex",
                  alignItems: "center",
                  gap: 12,
                  padding: "0 24px",
                  background: "rgba(148,163,184,.12)",
                }}
              >
                {["#ff5f57", "#febc2e", "#28c840"].map((c) => (
                  <span
                    key={c}
                    style={{ width: 16, height: 16, borderRadius: 8, background: c, display: "block" }}
                  />
                ))}
                {el.language && (
                  <span
                    style={{
                      marginLeft: "auto",
                      fontFamily: "'JetBrains Mono', ui-monospace, monospace",
                      fontSize: 20,
                      color: "#94a3b8",
                      textTransform: "lowercase",
                    }}
                  >
                    {el.language}
                  </span>
                )}
              </div>
              <div
                className="cf-code"
                style={{
                  display: "flex",
                  padding: "24px 32px 24px 20px",
                  fontFamily: "'JetBrains Mono', ui-monospace, monospace",
                  fontSize: el.fontSize,
                  lineHeight: 1.55,
                }}
              >
                <pre
                  aria-hidden
                  style={{
                    margin: 0,
                    paddingRight: 20,
                    textAlign: "right",
                    color: "#475569",
                    userSelect: "none",
                    font: "inherit",
                  }}
                >
                  {el.content.split("\n").map((_, i) => i + 1).join("\n")}
                </pre>
                <pre
                  style={{ margin: 0, flex: 1, minWidth: 0, color: el.color, whiteSpace: "pre-wrap", font: "inherit" }}
                  dangerouslySetInnerHTML={{ __html: highlight(el.content, el.language) }}
                />
              </div>
            </div>
          );
        } else {
          const textStyle: React.CSSProperties = {
            width: "100%",
            height: "100%",
            display: "flex",
            alignItems: el.type === "badge" ? "center" : "flex-start",
            justifyContent:
              el.textAlign === "center" ? "center" : el.textAlign === "right" ? "flex-end" : "flex-start",
            textAlign: el.textAlign,
            fontFamily: el.fontFamily ?? "'Inter', sans-serif",
            fontSize: el.fontSize,
            fontWeight: el.fontWeight,
            fontStyle: el.fontStyle ?? "normal",
            color: el.color,
            background: el.backgroundColor,
            borderRadius: el.radius,
            padding: el.backgroundColor !== "transparent" ? "0 28px" : 0,
            letterSpacing: el.letterSpacing,
            lineHeight: el.lineHeight ?? 1.25,
            whiteSpace: "pre-wrap",
            wordBreak: "break-word",
          };
          inner =
            editingId === el.id ? (
              <textarea
                autoFocus
                defaultValue={el.content}
                onPointerDown={(e) => e.stopPropagation()}
                onBlur={(e) => onCommitText?.(el.id, e.target.value)}
                style={{
                  ...textStyle,
                  display: "block",
                  resize: "none",
                  outline: "2px solid #6366f1",
                  background: "rgba(0,0,0,.35)",
                }}
              />
            ) : (
              <div style={textStyle}>{el.rich ? <span>{renderRich(el.content)}</span> : el.content}</div>
            );
        }

        return (
          <div
            key={el.id}
            style={common}
            onPointerDown={start}
            onDoubleClick={(e) => {
              if (!interactive) return;
              e.stopPropagation();
              if (["heading", "subheading", "body", "badge", "code"].includes(el.type) && !el.isLocked) {
                onEditText?.(el);
              }
            }}
          >
            {inner}
            {selected && (
              <>
                <div
                  style={{
                    position: "absolute",
                    inset: -4,
                    border: "3px solid #6366f1",
                    borderRadius: 8,
                    pointerEvents: "none",
                  }}
                />
                {!el.isLocked && (
                  <div
                    onPointerDown={(e) => {
                      e.stopPropagation();
                      onPointerDownElement?.(e, el, "resize");
                    }}
                    style={{
                      position: "absolute",
                      right: -14,
                      bottom: -14,
                      width: 28,
                      height: 28,
                      borderRadius: 14,
                      background: "#6366f1",
                      border: "3px solid #fff",
                      cursor: "nwse-resize",
                    }}
                  />
                )}
              </>
            )}
          </div>
        );
      })}
    </div>
  );
}
