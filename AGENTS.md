<!-- LOVABLE:BEGIN -->
> [!IMPORTANT]
> This project is connected to [Lovable](https://lovable.dev). Avoid rewriting
> published git history — force pushing, or rebasing/amending/squashing commits
> that are already pushed — as it rewrites history on Lovable's side and the
> user will likely lose their project history.
>
> Commits you push to the connected branch sync back to Lovable and show up in
> the editor, so keep the branch in a working state.
<!-- LOVABLE:END -->
- Slides are built from structured `SlideData` (archetypes in src/lib/archetypes.ts) stored on `Slide.data`; element edits sync back via `dataKey`. Why: templates can re-render any slide without losing text.
- Carousel generation calls Lovable AI server-side (src/lib/ai.functions.ts → ai.server.ts) and falls back to the local content bank on failure. Why: real topic depth, still works offline.
