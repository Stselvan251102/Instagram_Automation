import type { SlideData } from "@/types/carousel";

/** Legacy flat shape — still used to read back hand-made slides. */
export interface RawSlideContent {
  type: "hook" | "content" | "code_breakdown" | "comparison" | "cta";
  title: string;
  body?: string | undefined;
  code?: string | undefined;
  compare?: { leftTitle: string; left: string; rightTitle: string; right: string };
}

export interface ContentBankEntry {
  topic: string;
  aliases: string[];
  title: string;
  category: string;
  slides: Omit<SlideData, "slideNumber">[];
}

export const CONTENT_BANK: ContentBankEntry[] = [
  {
    topic: "python data types",
    aliases: ["python", "mutability", "python types"],
    title: "Python Data Types",
    category: "PYTHON",
    slides: [
      {
        archetype: "hook",
        headline: "Your Python function just changed data it never touched.",
        subheadline: "Mutability explained in 7 slides — with the bug that bites seniors too.",
        difficulty: "Intermediate",
        readTime: "2 min read",
      },
      {
        archetype: "deep_dive",
        icon: "layers",
        headerBadge: "Core Concept",
        headline: "Mutable vs Immutable",
        definition:
          "An immutable object cannot change after creation; any 'change' builds a new object. Mutable objects are modified in place.",
        keyTakeaways: [
          "**Immutable:** int, float, str, tuple, frozenset, bytes",
          "**Mutable:** list, dict, set, bytearray and most custom classes",
          "**Hashable** usually means immutable — only those can be dict keys",
        ],
        useCase: "Config objects passed across threads are frozen dataclasses so no worker can silently mutate shared state.",
      },
      {
        archetype: "code_syntax",
        headerBadge: "Python · Gotcha",
        headline: "The mutable default argument trap",
        definition: "Default values are evaluated once, when the def statement runs — not on every call.",
        syntaxSnippet: "def fn(arg=None):\n    arg = [] if arg is None else arg",
        codeExample: {
          language: "python",
          code: "def add(item, bucket=[]):\n    bucket.append(item)\n    return bucket\n\nprint(add(1))\nprint(add(2))",
          output: "[1]  then  [1, 2]",
        },
        proTipOrGotcha: "Linters flag this as B006. Use None as the sentinel and create the list inside the body.",
        tipKind: "gotcha",
      },
      {
        archetype: "comparison_diff",
        comparisonMode: "a_vs_b",
        headerBadge: "list vs tuple",
        headline: "When to reach for each",
        comparisonData: {
          leftTitle: "list",
          leftContent: ["Mutable, resizable", "append / pop are O(1)", "Unhashable", "Homogeneous sequences"],
          rightTitle: "tuple",
          rightContent: ["Immutable", "Usable as dict keys", "Smaller memory footprint", "Fixed-shape records"],
        },
        verdict: "Default to tuple for records you won't change; use list when the size changes.",
      },
      {
        archetype: "tech_update",
        headerBadge: "Python 3.13",
        headline: "Free-threaded CPython lands (experimental)",
        whatChanged:
          "PEP 703 ships an optional build without the GIL (python3.13t), plus a new colour REPL and an experimental JIT (PEP 744).",
        impactMetric: "No GIL",
        whyItMatters: "CPU-bound threads can finally run in parallel on multiple cores — no multiprocessing hacks.",
        beforeAfter: {
          before: "# threads serialised by GIL\nThreadPoolExecutor(8)\n# ~1 core busy",
          after: "# python3.13t\nThreadPoolExecutor(8)\n# up to 8 cores busy",
        },
      },
      {
        archetype: "code_syntax",
        headerBadge: "Python · copy module",
        headline: "Shallow vs deep copy",
        definition: "A shallow copy duplicates the container only; nested objects are still shared references.",
        syntaxSnippet: "copy.copy(obj)      # shallow\ncopy.deepcopy(obj)  # recursive",
        codeExample: {
          language: "python",
          code: "import copy\n\na = [[1, 2], [3]]\nb = copy.copy(a)\nc = copy.deepcopy(a)\na[0].append(99)\nprint(b[0], c[0])",
          output: "[1, 2, 99] [1, 2]",
        },
        proTipOrGotcha: "deepcopy respects __deepcopy__ — implement it for objects holding sockets, locks or caches.",
        tipKind: "tip",
      },
      {
        archetype: "cta",
        headline: "Save this before your next interview",
        keyTakeaways: [
          "Know which types are mutable",
          "Never use [] as a default argument",
          "tuple for records, list for growth",
          "deepcopy when nesting",
        ],
      },
    ],
  },
  {
    topic: "salesforce architecture",
    aliases: ["salesforce", "apex", "sfdc"],
    title: "Salesforce Architecture",
    category: "SALESFORCE",
    slides: [
      {
        archetype: "hook",
        headline: "Salesforce architecture, explained without the jargon",
        subheadline: "The multi-tenant model every admin and dev should actually understand.",
        difficulty: "Beginner → Intermediate",
        readTime: "3 min read",
      },
      {
        archetype: "deep_dive",
        icon: "cloud",
        headerBadge: "Platform Core",
        headline: "Multi-tenant by design",
        definition:
          "Thousands of orgs share one codebase and infrastructure; each org's data is isolated by OrgId, and its app is defined by metadata.",
        keyTakeaways: [
          "**Governor limits** protect shared resources from any single org",
          "**Three releases a year** upgrade every org at once",
          "**Metadata-driven** apps survive upgrades without migrations",
        ],
        useCase: "A bank and a startup run on the same pod — the bank's 50M-record batch job can't starve the startup's API calls.",
      },
      {
        archetype: "deep_dive",
        icon: "database",
        headerBadge: "Metadata Layer",
        headline: "Your app is rows, not tables",
        definition:
          "Custom objects, fields, flows and layouts are stored as metadata; the runtime engine interprets them per org at request time.",
        keyTakeaways: [
          "**Custom objects** map to shared, pivoted data tables",
          "**Metadata API** lets you deploy config like code",
          "**Source tracking** in scratch orgs powers CI/CD",
        ],
        useCase: "Teams version object and flow metadata in Git and deploy with sf project deploy start through their pipelines.",
      },
      {
        archetype: "comparison_diff",
        comparisonMode: "a_vs_b",
        headerBadge: "Flow vs Apex",
        headline: "Declarative or code?",
        comparisonData: {
          leftTitle: "Record-Triggered Flow",
          leftContent: ["No code, admin-owned", "Fast to ship", "Before-save is very fast", "Harder to unit test"],
          rightTitle: "Apex Trigger",
          rightContent: ["Full control of logic", "Complex bulk processing", "Needs 75% test coverage", "Developer-owned"],
        },
        verdict: "Flow first for field updates and simple logic; Apex when logic is complex or performance-critical.",
      },
      {
        archetype: "code_syntax",
        headerBadge: "Apex · Bulkification",
        headline: "Never query inside a loop",
        definition: "Each transaction allows 100 SOQL queries; querying per record fails as soon as a 200-row batch arrives.",
        syntaxSnippet: "Map<Id, SObject> m = new Map<Id, SObject>([SOQL]);",
        codeExample: {
          language: "apex",
          code: "Set<Id> accIds = new Set<Id>();\nfor (Contact c : Trigger.new) {\n    accIds.add(c.AccountId);\n}\nMap<Id, Account> accs = new Map<Id, Account>(\n    [SELECT Id, Name FROM Account WHERE Id IN :accIds]\n);",
          output: "1 SOQL query for 200 records",
        },
        proTipOrGotcha: "The same rule applies to DML: collect records in a List and update once outside the loop.",
        tipKind: "gotcha",
      },
      {
        archetype: "tech_update",
        headerBadge: "Spring '24 · Apex",
        headline: "Null coalescing operator (??) in Apex",
        whatChanged: "Apex now supports ?? — it returns the left operand when it isn't null, otherwise the right one.",
        impactMetric: "−4 lines",
        whyItMatters: "Replaces verbose null checks and ternaries, especially around single-row SOQL results.",
        beforeAfter: {
          before: "String n = acc.Name != null\n    ? acc.Name\n    : 'Unknown';",
          after: "String n = acc.Name ?? 'Unknown';",
        },
      },
      {
        archetype: "cta",
        headline: "Save this before your next build",
        keyTakeaways: ["Respect governor limits", "Metadata is your app", "Flow first, Apex when needed", "Bulkify everything"],
      },
    ],
  },
];

export function findEntry(topic: string): ContentBankEntry | undefined {
  const t = topic.toLowerCase().trim();
  if (!t) return undefined;
  return (
    CONTENT_BANK.find((e) => t.includes(e.topic) || e.topic.includes(t)) ??
    CONTENT_BANK.find((e) => e.aliases.some((a) => t === a || t.split(/\s+/).includes(a)))
  );
}
