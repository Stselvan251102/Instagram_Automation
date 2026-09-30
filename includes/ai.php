<?php
/**
 * AI Carousel Generator Service
 * OpenAI API / Lovable AI Gateway Integration with Built-in Offline Fallback
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

class AIService {
    const ARCHETYPES = ['code_syntax', 'tech_update', 'comparison_diff', 'deep_dive', 'hook', 'cta'];

    const SYSTEM_PROMPT = <<<EOT
You are a senior engineer and technical educator writing Instagram carousels.
Return ONLY a valid JSON object: {"category": string (1-2 words, uppercase), "title": string, "slides": GeneratedSlideData[]}.

GeneratedSlideData fields:
- slideNumber: number
- archetype: "hook" | "code_syntax" | "tech_update" | "comparison_diff" | "deep_dive" | "cta"
- headerBadge?: string (e.g. "Python 3.12+ Feature", "Salesforce Flow", "Next.js 15")
- headline: string (max ~9 words)
- subheadline?: string (hook only, max 14 words)
- difficulty?: string, readTime?: string (hook only, e.g. "Intermediate", "2 min read")
- definition?: string (accurate technical definition, 15-25 words)
- syntaxSnippet?: string (exact syntax template, max 3 lines)
- codeExample?: { language: "python"|"javascript"|"typescript"|"sql"|"rust"|"go"|"apex"|"java"|"bash", code: string (real, working, max 9 lines, max 48 chars per line), output: string (exact output, one line) }
- comparisonData?: { leftTitle, leftContent: string[3-4, each max 6 words], rightTitle, rightContent: string[3-4] }
- comparisonMode?: "old_vs_new" | "a_vs_b"
- verdict?: string (comparison takeaway, max 18 words)
- proTipOrGotcha?: string (deep practical insight or edge case, max 25 words), tipKind?: "gotcha" | "tip"
- keyTakeaways?: string[] (deep_dive: exactly 3 bullets formatted "**Keyword:** explanation", max 12 words; cta: 3-4 short recap items)
- whatChanged?: string, impactMetric?: string (very short, e.g. "3x faster", "No GIL"), whyItMatters?: string, beforeAfter?: { before: string, after: string } (tech_update; short code or text, max 4 lines each)
- icon?: "layers"|"cpu"|"lightbulb"|"database"|"shield"|"zap"|"code"|"workflow"|"boxes"|"cloud" (deep_dive)
- useCase?: string (deep_dive: real production use case, max 25 words)

Rules:
- Slide 1 is "hook", last slide is "cta". Middle slides must mix archetypes to fit the content: code_syntax for programming/syntax, tech_update for releases/versions/new features, comparison_diff for tool vs tool or good vs bad, deep_dive for architecture/theory. Never use the same archetype more than twice in a row.
- Be specific and technically accurate: real version numbers, real APIs, real outputs. No generic filler.
- Every code example must run as written and its output must be exact.
EOT;

    /**
     * Generate Carousel via AI with automatic fallback
     */
    public static function generate(string $topic, int $count = 7, string $tone = 'Educational'): array {
        $topic = trim($topic);
        if (empty($topic)) {
            $topic = 'Python Data Types';
        }
        $count = max(3, min(10, $count));

        // 1. Try OpenAI if API key is set
        $openAiKey = OPENAI_API_KEY;
        $lovableKey = LOVABLE_API_KEY;

        if (!empty($openAiKey) || !empty($lovableKey)) {
            try {
                $aiResult = self::callAI($topic, $count, $tone);
                if (!empty($aiResult['slides'])) {
                    return [
                        'ok' => true,
                        'source' => 'ai',
                        'title' => $aiResult['title'] ?? self::titleFor($topic),
                        'category' => $aiResult['category'] ?? self::categoryFor($topic),
                        'slides' => $aiResult['slides'],
                    ];
                }
            } catch (Exception $e) {
                error_log("AI Generation API call failed: " . $e->getMessage());
                // Fall through to offline mock generator
            }
        }

        // 2. High-quality offline fallback generator
        $mock = self::generateFallback($topic, $count, $tone);
        return [
            'ok' => true,
            'source' => 'fallback',
            'title' => $mock['title'],
            'category' => $mock['category'],
            'slides' => $mock['slides'],
            'notice' => empty($openAiKey) ? 'Generated with built-in technical content bank. Configure OPENAI_API_KEY in Settings for custom generative AI.' : null
        ];
    }

    /**
     * Call OpenAI-compatible Chat Completion API
     */
    private static function callAI(string $topic, int $count, string $tone): array {
        $apiKey = OPENAI_API_KEY ?: LOVABLE_API_KEY;
        $endpoint = !empty(LOVABLE_API_KEY) && empty(OPENAI_API_KEY)
            ? 'https://ai.gateway.lovable.dev/v1/chat/completions'
            : 'https://api.openai.com/v1/chat/completions';
        
        $model = OPENAI_MODEL ?: 'gpt-4o-mini';

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => "Topic: {$topic}\nTone: {$tone}\nExactly {$count} slides."]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }
        if ($httpCode !== 200) {
            throw new Exception("OpenAI API returned status HTTP " . $httpCode . ": " . $response);
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            throw new Exception("Empty response from AI model.");
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed) || empty($parsed['slides'])) {
            throw new Exception("Invalid JSON format from AI model.");
        }

        $slides = self::normalizeSlides($parsed['slides']);
        if (count($slides) < 2) {
            throw new Exception("Model returned too few slides.");
        }

        $slides[0]['archetype'] = 'hook';
        $slides[count($slides) - 1]['archetype'] = 'cta';

        return [
            'category' => strtoupper(substr(trim($parsed['category'] ?? self::categoryFor($topic)), 0, 20)),
            'title' => substr(trim($parsed['title'] ?? self::titleFor($topic)), 0, 80),
            'slides' => $slides,
        ];
    }

    /**
     * Normalize slide properties
     */
    public static function normalizeSlides(array $raw): array {
        $out = [];
        foreach ($raw as $o) {
            if (!is_array($o) || empty($o['headline'])) continue;

            $archetype = in_array($o['archetype'] ?? '', self::ARCHETYPES) ? $o['archetype'] : 'deep_dive';

            $codeExample = null;
            if (!empty($o['codeExample']['code'])) {
                $codeExample = [
                    'language' => strtolower($o['codeExample']['language'] ?? 'code'),
                    'code' => substr($o['codeExample']['code'], 0, 700),
                    'output' => substr($o['codeExample']['output'] ?? '', 0, 160)
                ];
            }

            $comparisonData = null;
            if (!empty($o['comparisonData']['leftTitle'])) {
                $comparisonData = [
                    'leftTitle' => substr($o['comparisonData']['leftTitle'], 0, 30),
                    'leftContent' => array_slice((array)($o['comparisonData']['leftContent'] ?? []), 0, 5),
                    'rightTitle' => substr($o['comparisonData']['rightTitle'] ?? 'B', 0, 30),
                    'rightContent' => array_slice((array)($o['comparisonData']['rightContent'] ?? []), 0, 5),
                ];
            }

            $beforeAfter = null;
            if (!empty($o['beforeAfter']['before'])) {
                $beforeAfter = [
                    'before' => substr($o['beforeAfter']['before'], 0, 240),
                    'after' => substr($o['beforeAfter']['after'] ?? '', 0, 240)
                ];
            }

            $slide = [
                'slideNumber' => count($out) + 1,
                'archetype' => $archetype,
                'headline' => substr(trim($o['headline']), 0, 160),
                'headerBadge' => !empty($o['headerBadge']) ? substr(trim($o['headerBadge']), 0, 40) : null,
                'subheadline' => !empty($o['subheadline']) ? substr(trim($o['subheadline']), 0, 160) : null,
                'difficulty' => !empty($o['difficulty']) ? substr(trim($o['difficulty']), 0, 30) : null,
                'readTime' => !empty($o['readTime']) ? substr(trim($o['readTime']), 0, 20) : null,
                'definition' => !empty($o['definition']) ? substr(trim($o['definition']), 0, 260) : null,
                'syntaxSnippet' => !empty($o['syntaxSnippet']) ? substr(trim($o['syntaxSnippet']), 0, 200) : null,
                'codeExample' => $codeExample,
                'comparisonData' => $comparisonData,
                'comparisonMode' => in_array($o['comparisonMode'] ?? '', ['old_vs_new', 'a_vs_b']) ? $o['comparisonMode'] : null,
                'verdict' => !empty($o['verdict']) ? substr(trim($o['verdict']), 0, 200) : null,
                'proTipOrGotcha' => !empty($o['proTipOrGotcha']) ? substr(trim($o['proTipOrGotcha']), 0, 240) : null,
                'tipKind' => ($o['tipKind'] ?? '') === 'tip' ? 'tip' : 'gotcha',
                'keyTakeaways' => !empty($o['keyTakeaways']) ? array_slice((array)$o['keyTakeaways'], 0, 4) : null,
                'whatChanged' => !empty($o['whatChanged']) ? substr(trim($o['whatChanged']), 0, 260) : null,
                'impactMetric' => !empty($o['impactMetric']) ? substr(trim($o['impactMetric']), 0, 16) : null,
                'whyItMatters' => !empty($o['whyItMatters']) ? substr(trim($o['whyItMatters']), 0, 200) : null,
                'beforeAfter' => $beforeAfter,
                'icon' => !empty($o['icon']) ? substr(trim($o['icon']), 0, 20) : null,
                'useCase' => !empty($o['useCase']) ? substr(trim($o['useCase']), 0, 240) : null,
            ];

            $out[] = $slide;
        }
        return $out;
    }

    /**
     * Fallback generator when offline or no API key is provided
     */
    public static function generateFallback(string $topic, int $count, string $tone): array {
        $category = self::categoryFor($topic);
        $title = self::titleFor($topic);

        $hook = [
            'slideNumber' => 1,
            'archetype' => 'hook',
            'headerBadge' => strtoupper($category) . ' GUIDE',
            'headline' => "Mastering {$topic} in 2026",
            'subheadline' => "Everything you need to know from fundamentals to production best practices.",
            'difficulty' => 'Intermediate',
            'readTime' => '2 min read',
        ];

        $middleCandidates = [
            [
                'archetype' => 'code_syntax',
                'headerBadge' => 'CORE SYNTAX',
                'headline' => 'Idiomatic pattern & implementation',
                'definition' => 'Modern implementation pattern ensuring optimal memory utilization and clean readability.',
                'syntaxSnippet' => "// Clean syntax pattern\nconst result = await processData(input);\nconsole.log('Processed:', result.count);",
                'codeExample' => [
                    'language' => 'typescript',
                    'code' => "// Production implementation\nexport function transform(data) {\n  return data.map(item => ({\n    id: item.id,\n    score: item.weight * 1.5\n  }));\n}",
                    'output' => '[ { id: 101, score: 94.5 } ]'
                ],
                'proTipOrGotcha' => 'Avoid re-allocating memory inside loops; pre-allocate collections where possible.',
                'tipKind' => 'tip'
            ],
            [
                'archetype' => 'comparison_diff',
                'headerBadge' => 'BENCHMARK',
                'headline' => 'Legacy approach vs modern pattern',
                'comparisonMode' => 'old_vs_new',
                'comparisonData' => [
                    'leftTitle' => 'Traditional Method',
                    'leftContent' => ['Manual memory pooling', 'High CPU baseline', 'Complex concurrency', 'Boilerplate overhead'],
                    'rightTitle' => 'Modern Architecture',
                    'rightContent' => ['Zero-copy pipeline', '3.8x throughput', 'Lock-free primitives', 'Type-safe contracts']
                ],
                'verdict' => 'Modern approach delivers higher throughput with 60% lower latency under load.'
            ],
            [
                'archetype' => 'deep_dive',
                'headerBadge' => 'ARCHITECTURE',
                'headline' => 'Under the hood & internal flow',
                'icon' => 'cpu',
                'definition' => 'How the engine optimizes call graphs and leverages generational heuristics under concurrent traffic.',
                'keyTakeaways' => [
                    '**Pipeline:** Thread-safe work stealing queues prevent head-of-line blocking.',
                    '**Cache:** L1/L2 cache locality preserves low sub-millisecond response rates.',
                    '**Failover:** Graceful degradation prevents cascading outages.'
                ],
                'useCase' => 'Handles over 50,000 requests/sec in high-throughput payment settlement pipelines.'
            ],
            [
                'archetype' => 'tech_update',
                'headerBadge' => 'WHAT CHANGED',
                'headline' => 'Key optimizations & version upgrades',
                'whatChanged' => 'Refactored internal allocator to avoid global interpreter locks and reduce stop-the-world pauses.',
                'impactMetric' => '4.2x Faster',
                'whyItMatters' => 'Allows seamless multi-core scaling without requiring specialized external orchestrators.',
                'beforeAfter' => [
                    'before' => "// Legacy blocking execution\nconst task = runSync(payload);",
                    'after' => "// Non-blocking worker runtime\nconst task = await runStream(payload);"
                ]
            ],
            [
                'archetype' => 'code_syntax',
                'headerBadge' => 'GOTCHA & FIX',
                'headline' => 'Common edge cases and debugging',
                'definition' => 'Watch out for race conditions and unhandled null references during asynchronous dispatch.',
                'codeExample' => [
                    'language' => 'javascript',
                    'code' => "// Safe error boundary handler\ntry {\n  const res = executeSafe(ctx);\n  return res.unwrap();\n} catch (err) {\n  logTelemetry(err);\n}",
                    'output' => 'Telemetric incident recorded safely'
                ],
                'proTipOrGotcha' => 'Always wrap external network calls in circuit breakers to avoid worker exhaustion.',
                'tipKind' => 'gotcha'
            ],
            [
                'archetype' => 'deep_dive',
                'headerBadge' => 'PRODUCTION CHECKLIST',
                'headline' => 'Production deployment best practices',
                'icon' => 'shield',
                'definition' => 'Essential checklist before rolling out architecture changes to production traffic.',
                'keyTakeaways' => [
                    '**Metrics:** Monitor p99 latency and memory saturation closely.',
                    '**Rollback:** Maintain blue-green deployment parity with canary traffic.',
                    '**Observability:** Emit structured trace IDs across all boundary hops.'
                ],
                'useCase' => 'Standard operating procedure for tier-1 microservices across enterprise systems.'
            ]
        ];

        $middleNeeded = max(1, $count - 2);
        $slides = [$hook];
        for ($i = 0; $i < $middleNeeded; $i++) {
            $candidate = $middleCandidates[$i % count($middleCandidates)];
            $candidate['slideNumber'] = count($slides) + 1;
            $slides[] = $candidate;
        }

        $cta = [
            'slideNumber' => count($slides) + 1,
            'archetype' => 'cta',
            'headerBadge' => 'SUMMARY & ACTION',
            'headline' => "Save this guide for your next build",
            'subheadline' => "Tap save to revisit these patterns anytime.",
            'keyTakeaways' => [
                'Understand core idioms & avoid anti-patterns',
                'Benchmark before optimizing prematurely',
                'Implement robust circuit breakers & tracing',
                'Share with fellow engineers'
            ]
        ];
        $slides[] = $cta;

        return [
            'category' => $category,
            'title' => $title,
            'slides' => $slides
        ];
    }

    public static function categoryFor(string $topic): string {
        $t = strtolower($topic);
        if (preg_match('/(python|javascript|typescript|rust|go|java|c\+\+|sql|php)/', $t)) return 'CODING';
        if (preg_match('/(salesforce|cloud|aws|docker|kubernetes|infra)/', $t)) return 'DEV & INFRA';
        if (preg_match('/(system design|architecture|database|redis|kafka)/', $t)) return 'ARCHITECTURE';
        if (preg_match('/(growth|marketing|viral|brand|strategy)/', $t)) return 'GROWTH';
        return 'TECH GUIDE';
    }

    public static function titleFor(string $topic): string {
        return ucwords(trim($topic)) . " Guide";
    }
}
