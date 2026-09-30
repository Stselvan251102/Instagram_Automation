export interface GenerateInput {
  topic: string;
  count: number;
  tone: string;
  apiKey?: string | undefined;
  apiProvider?: string | undefined;
}

export async function generateCarousel({ data }: { data: GenerateInput }) {
  try {
    const response = await fetch("/api/ai/generate.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        topic: data.topic,
        count: data.count,
        tone: data.tone,
        apiKey: data.apiKey || undefined,
        apiProvider: data.apiProvider || undefined,
      }),
    });

    if (!response.ok) {
      const errData = await response.json().catch(() => ({}));
      return {
        ok: false as const,
        error: errData.error || `Server responded with HTTP ${response.status}`,
      };
    }

    const result = await response.json();
    return result;
  } catch (e) {
    console.warn("AI generation request failed, falling back to client generator:", e);
    return {
      ok: false as const,
      error: e instanceof Error ? e.message : "Failed to connect to AI server.",
    };
  }
}
