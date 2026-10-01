<?php
/**
 * AI Carousel Generator Service
 * Multi-Provider LLM Integration (OpenAI, Groq, Google Gemini, Anthropic, OpenRouter)
 * with Intelligent Topic-Aware Offline Content Engine.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

class AIService {
    const ARCHETYPES = ['code_syntax', 'tech_update', 'comparison_diff', 'deep_dive', 'hook', 'cta'];

    /**
     * Generate Carousel via AI with automatic fallback
     */
    public static function generate(
        string $topic,
        int $count = 7,
        string $tone = 'Educational',
        ?string $clientApiKey = null,
        ?string $clientApiProvider = null
    ): array {
        $topic = trim($topic);
        if (empty($topic)) {
            $topic = 'Healthy Morning Habits';
        }
        $count = max(3, min(10, $count));

        // 1. Resolve AI Key and Provider
        $customKey = !empty($clientApiKey) ? trim($clientApiKey) : null;
        $customProvider = !empty($clientApiProvider) ? strtolower(trim($clientApiProvider)) : null;

        $serverKey = OPENAI_API_KEY ?: (GROQ_API_KEY ?: (GEMINI_API_KEY ?: OPENROUTER_API_KEY));
        $hasKey = !empty($customKey) || !empty($serverKey);

        $lastError = null;

        if ($hasKey) {
            try {
                $aiResult = self::callAI($topic, $count, $tone, $customKey, $customProvider);
                if (!empty($aiResult['slides'])) {
                    return [
                        'ok' => true,
                        'source' => 'ai',
                        'provider' => $aiResult['provider'] ?? 'ai',
                        'model' => $aiResult['model'] ?? 'default',
                        'title' => $aiResult['title'] ?? self::titleFor($topic),
                        'category' => $aiResult['category'] ?? self::categoryFor($topic),
                        'slides' => $aiResult['slides'],
                    ];
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                error_log("AI Generation API call failed: " . $lastError);
            }
        }

        // 2. High-quality topic-aware fallback generator
        $mock = self::generateFallback($topic, $count, $tone);
        $notice = null;
        if (!empty($lastError)) {
            $notice = "AI Provider error: {$lastError}. Generated with topic-adaptive engine.";
        } elseif (!$hasKey) {
            $notice = "No API key configured. Generated using built-in topic engine. Add an OpenAI, Groq, or Gemini key in Settings for live LLM generation.";
        }

        return [
            'ok' => true,
            'source' => 'fallback',
            'title' => $mock['title'],
            'category' => $mock['category'],
            'slides' => $mock['slides'],
            'notice' => $notice,
            'warning' => $lastError
        ];
    }

    /**
     * Call AI API (Supports OpenAI, Groq, Gemini, Anthropic, OpenRouter)
     */
    private static function callAI(
        string $topic,
        int $count,
        string $tone,
        ?string $customKey = null,
        ?string $customProvider = null
    ): array {
        $apiKey = $customKey;
        $providerName = $customProvider;

        // Fall back to server environment variables if no client key provided
        if (empty($apiKey)) {
            if (!empty(GROQ_API_KEY)) {
                $apiKey = GROQ_API_KEY;
                $providerName = 'groq';
            } elseif (!empty(GEMINI_API_KEY)) {
                $apiKey = GEMINI_API_KEY;
                $providerName = 'gemini';
            } elseif (!empty(OPENAI_API_KEY)) {
                $apiKey = OPENAI_API_KEY;
            } elseif (!empty(OPENROUTER_API_KEY)) {
                $apiKey = OPENROUTER_API_KEY;
                $providerName = 'openrouter';
            } else {
                throw new Exception("No AI API key configured.");
            }
        }

        // Auto-detect or override provider by key format (essential if user put Groq/Gemini key in OPENAI_API_KEY)
        if (str_starts_with($apiKey, 'gsk_')) {
            $providerName = 'groq';
        } elseif (str_starts_with($apiKey, 'AIzaSy')) {
            $providerName = 'gemini';
        } elseif (str_starts_with($apiKey, 'sk-ant-')) {
            $providerName = 'anthropic';
        } elseif (str_starts_with($apiKey, 'sk-or-')) {
            $providerName = 'openrouter';
        } elseif (empty($providerName)) {
            $providerName = 'openai';
        }

        $domain = self::detectDomain($topic);
        $systemPrompt = self::buildSystemPrompt($topic, $domain);
        $userPrompt = "Generate an Instagram carousel about '{$topic}'.\nTone: {$tone}.\nSlide count: exactly {$count} slides (Slide 1 is hook, Slide {$count} is cta, slides 2 to " . ($count - 1) . " are body slides).\nRespond ONLY with valid JSON.";

        if ($providerName === 'anthropic') {
            return self::callAnthropic($apiKey, $systemPrompt, $userPrompt, $topic, $count);
        }

        // OpenAI-compatible Chat Completions (OpenAI, Groq, Gemini, OpenRouter)
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $model = OPENAI_MODEL ?: 'gpt-4o-mini';

        if ($providerName === 'groq') {
            $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
            $model = GROQ_MODEL ?: 'openai/gpt-oss-120b';
        } elseif ($providerName === 'gemini') {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions';
            $model = GEMINI_MODEL ?: 'gemini-1.5-flash';
        } elseif ($providerName === 'openrouter') {
            $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
            $model = OPENROUTER_MODEL ?: 'openai/gpt-4o-mini';
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
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

        if ($httpCode !== 200 && $providerName === 'groq' && $model !== 'openai/gpt-oss-20b') {
            $payload['model'] = 'openai/gpt-oss-20b';
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
            if ($httpCode === 200) {
                $model = 'openai/gpt-oss-20b';
            }
        }

        if ($curlError) {
            throw new Exception("cURL Error ({$providerName}): " . $curlError);
        }
        if ($httpCode !== 200) {
            $errDetail = $response;
            $errJson = json_decode($response, true);
            if (!empty($errJson['error']['message'])) {
                $errDetail = $errJson['error']['message'];
            }
            throw new Exception("AI Provider ({$providerName}) returned HTTP {$httpCode}: " . substr($errDetail, 0, 200));
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            throw new Exception("Empty response from AI model.");
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed) || empty($parsed['slides'])) {
            throw new Exception("Invalid JSON structure from AI model.");
        }

        $slides = self::normalizeSlides($parsed['slides']);
        if (count($slides) < 2) {
            throw new Exception("Model returned too few slides.");
        }

        $slides[0]['archetype'] = 'hook';
        $slides[count($slides) - 1]['archetype'] = 'cta';

        return [
            'provider' => $providerName,
            'model' => $model,
            'category' => strtoupper(substr(trim($parsed['category'] ?? self::categoryFor($topic)), 0, 24)),
            'title' => substr(trim($parsed['title'] ?? self::titleFor($topic)), 0, 80),
            'slides' => $slides,
        ];
    }

    /**
     * Call Anthropic Messages API
     */
    private static function callAnthropic(string $apiKey, string $systemPrompt, string $userPrompt, string $topic, int $count): array {
        $endpoint = 'https://api.anthropic.com/v1/messages';
        $model = 'claude-3-5-haiku-20241022';

        $payload = [
            'model' => $model,
            'max_tokens' => 4000,
            'system' => $systemPrompt . "\nYou MUST output ONLY valid JSON without markdown fences.",
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
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
            throw new Exception("Anthropic cURL Error: " . $curlError);
        }
        if ($httpCode !== 200) {
            throw new Exception("Anthropic returned HTTP {$httpCode}: " . substr($response, 0, 200));
        }

        $decoded = json_decode($response, true);
        $rawText = $decoded['content'][0]['text'] ?? '';
        $rawText = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $rawText));

        $parsed = json_decode($rawText, true);
        if (!is_array($parsed) || empty($parsed['slides'])) {
            throw new Exception("Invalid JSON returned by Anthropic model.");
        }

        $slides = self::normalizeSlides($parsed['slides']);
        return [
            'provider' => 'anthropic',
            'model' => $model,
            'category' => strtoupper(substr(trim($parsed['category'] ?? self::categoryFor($topic)), 0, 24)),
            'title' => substr(trim($parsed['title'] ?? self::titleFor($topic)), 0, 80),
            'slides' => $slides,
        ];
    }

    /**
     * Build domain-aware System Prompt for LLM
     */
    private static function buildSystemPrompt(string $topic, string $domain): string {
        $archetypesGuide = ($domain === 'coding')
            ? "Middle slides must mix archetypes: code_syntax (with real syntax and working codeExample), tech_update, comparison_diff, deep_dive."
            : "Middle slides must mix archetypes suitable for {$domain}: deep_dive (in-depth concepts, frameworks, 3 key takeaways formatted '**Keyword:** explanation'), comparison_diff (old vs modern, amateur vs pro, or myth vs fact), tech_update (what changed / before vs after). DO NOT use code_syntax for non-programming topics.";

        return <<<EOT
You are an expert Instagram carousel creator and content strategist specialized in "{$topic}".
Return ONLY a valid JSON object matching this schema:
{
  "category": string (1-2 words, uppercase, e.g. "MARKETING", "WELLNESS", "CODING", "FINANCE", "MINDSET"),
  "title": string (catchy, 3-7 words),
  "slides": GeneratedSlideData[]
}

GeneratedSlideData schema:
- slideNumber: number (1 to N)
- archetype: "hook" | "code_syntax" | "tech_update" | "comparison_diff" | "deep_dive" | "cta"
- headerBadge?: string (short uppercase badge, max 4 words)
- headline: string (punchy, max 10 words, highly relevant to {$topic})
- subheadline?: string (hook only: 1 engaging sentence, max 14 words)
- difficulty?: string (hook only, e.g. "Beginner Friendly", "Actionable Guide")
- readTime?: string (hook only, e.g. "2 min read")
- definition?: string (concise insight or explanation, 15-28 words)
- syntaxSnippet?: string (only if coding topic)
- codeExample?: { language: string, code: string, output: string } (only if coding topic)
- comparisonData?: { leftTitle: string, leftContent: string[3-4 items, max 6 words each], rightTitle: string, rightContent: string[3-4 items] }
- comparisonMode?: "old_vs_new" | "a_vs_b"
- verdict?: string (key takeaway from comparison, max 18 words)
- proTipOrGotcha?: string (practical tip or trap to avoid, max 25 words)
- tipKind?: "tip" | "gotcha"
- keyTakeaways?: string[] (deep_dive: exactly 3 bullets formatted "**Key:** Explanation", max 12 words each; cta: 3-4 recap points)
- whatChanged?: string, impactMetric?: string (short, e.g. "+40% Growth", "Zero Fluff"), whyItMatters?: string, beforeAfter?: { before: string, after: string }
- icon?: "layers"|"cpu"|"lightbulb"|"database"|"shield"|"zap"|"code"|"workflow"|"boxes"|"cloud"
- useCase?: string (real-world application or outcome, max 25 words)

Rules:
- Slide 1 must be "hook", last slide must be "cta".
- {$archetypesGuide}
- Tailor every single slide specifically to the user's topic: "{$topic}". Do NOT use generic placeholders or irrelevant code.
- Keep text concise, punchy, visually scannable, and formatted for maximum Instagram engagement.
EOT;
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
                'impactMetric' => !empty($o['impactMetric']) ? substr(trim($o['impactMetric']), 0, 20) : null,
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
     * Topic Domain Classifier
     */
    public static function detectDomain(string $topic): string {
        $t = strtolower($topic);
        if (preg_match('/(python|javascript|typescript|react|vue|angular|node|php|sql|database|rust|golang|docker|kubernetes|aws|css|html|git|backend|frontend|api|linux|c\+\+|java|devops)/', $t)) {
            return 'coding';
        }
        if (preg_match('/(morning|habit|health|fitness|workout|diet|nutrition|sleep|exercise|weight|gym|run|walk|cardio|mental|mindfulness|yoga|wellness|water|energy)/', $t)) {
            return 'health';
        }
        if (preg_match('/(marketing|instagram|social media|growth|sales|brand|audience|copywriting|seo|business|content|creator|reach|followers|viral|ad|agency)/', $t)) {
            return 'marketing';
        }
        if (preg_match('/(money|finance|invest|crypto|bitcoin|stock|wealth|budget|real estate|passive income|cash|portfolio|dividend)/', $t)) {
            return 'finance';
        }
        if (preg_match('/(productivity|focus|time management|discipline|goal|routine|study|learning|book|mindset|career|deep work|procrastination)/', $t)) {
            return 'productivity';
        }
        return 'general';
    }

    /**
     * Domain-Specific Fallback Content Generator
     */
    public static function generateFallback(string $topic, int $count, string $tone): array {
        $domain = self::detectDomain($topic);
        $category = self::categoryFor($topic);
        $title = self::titleFor($topic);
        $cleanTopic = ucwords(trim($topic));

        // 1. Hook Slide tailored to tone and domain
        $hookHeadlines = [
            'Educational' => "Mastering {$cleanTopic} in 2026",
            'Provocative / Viral' => "You're Doing {$cleanTopic} Completely Wrong",
            'Step-by-Step Tutorial' => "The Ultimate {$cleanTopic} Playbook",
            'Cheat Sheet' => "The Essential {$cleanTopic} Cheat Sheet",
        ];
        $hookHeadline = $hookHeadlines[$tone] ?? "The Complete Guide to {$cleanTopic}";

        $hookSubheadlines = [
            'health' => "Science-backed daily protocols for long-term energy and sustained results.",
            'marketing' => "Proven growth frameworks top creators and modern brands use to win.",
            'finance' => "Practical wealth-building principles to accelerate your financial freedom.",
            'productivity' => "High-leverage systems to eliminate friction and achieve 10x output.",
            'coding' => "Everything you need to know from idiomatic patterns to production best practices.",
            'general' => "Actionable frameworks and key principles broken down step-by-step."
        ];
        $hookSub = $hookSubheadlines[$domain] ?? $hookSubheadlines['general'];

        $hook = [
            'slideNumber' => 1,
            'archetype' => 'hook',
            'headerBadge' => strtoupper($category) . ' GUIDE',
            'headline' => $hookHeadline,
            'subheadline' => $hookSub,
            'difficulty' => 'Actionable',
            'readTime' => max(1, round($count / 3)) . ' min read',
        ];

        // 2. Middle Body Pool tailored to the topic domain
        $pool = self::buildDomainCandidates($cleanTopic, $domain);

        $middleNeeded = max(1, $count - 2);
        $slides = [$hook];
        for ($i = 0; $i < $middleNeeded; $i++) {
            $candidate = $pool[$i % count($pool)];
            $candidate['slideNumber'] = count($slides) + 1;
            $slides[] = $candidate;
        }

        // 3. CTA Slide tailored to domain
        $ctaTakeaways = [
            'health' => [
                "Anchor {$cleanTopic} into your daily routine",
                "Prioritize consistency over unsustainable intensity",
                "Track small compounding daily wins",
                "Share this with an accountability partner"
            ],
            'marketing' => [
                "Focus on saves, retention, and audience trust",
                "Test variations of your primary hook",
                "Deliver dense value with zero fluff",
                "Save this framework for content planning"
            ],
            'finance' => [
                "Automate contributions before discretionary spend",
                "Think in 5-to-10-year compounding horizons",
                "Control downside risk and reduce recurring drag",
                "Save this roadmap to review regularly"
            ],
            'productivity' => [
                "Eliminate friction on your highest-leverage task",
                "Protect uninterrupted morning focus blocks",
                "Review weekly progress with honest metrics",
                "Share with someone building better systems"
            ],
            'coding' => [
                "Understand core idioms and avoid anti-patterns",
                "Benchmark and profile before optimizing",
                "Build observable, testable boundaries",
                "Share with fellow engineers"
            ],
            'general' => [
                "Understand the core principle behind {$cleanTopic}",
                "Take immediate action on the first micro-step",
                "Iterate based on real-world feedback",
                "Save this guide for future reference"
            ]
        ];

        $cta = [
            'slideNumber' => count($slides) + 1,
            'archetype' => 'cta',
            'headerBadge' => 'ACTION & RECAP',
            'headline' => "Save This {$cleanTopic} Guide",
            'subheadline' => "Tap the save icon below to revisit these principles anytime.",
            'keyTakeaways' => $ctaTakeaways[$domain] ?? $ctaTakeaways['general']
        ];
        $slides[] = $cta;

        return [
            'category' => $category,
            'title' => $title,
            'slides' => $slides
        ];
    }

    /**
     * Build rich candidate pool by domain
     */
    private static function buildDomainCandidates(string $topic, string $domain): array {
        switch ($domain) {
            case 'health':
                return [
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'CORE FOUNDATION',
                        'icon' => 'zap',
                        'headline' => "The science behind {$topic}",
                        'definition' => "A structured approach to {$topic} primes circadian rhythm, balances hormones, and eliminates energy crashes before midday.",
                        'keyTakeaways' => [
                            '**Hydration First:** Replenish lost electrolytes before any stimulant intake.',
                            '**Light Exposure:** Natural morning sunlight signals cortisol and melatonin reset.',
                            '**Movement Anchor:** 5 to 10 minutes of mobility activates cellular oxygenation.'
                        ],
                        'useCase' => "Used by top health professionals to maintain peak physical and cognitive vitality."
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'HABIT CONTRAST',
                        'headline' => "Common pitfalls vs optimal {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Common Mistakes',
                            'leftContent' => ['Immediate phone scrolling', 'Inconsistent daily timing', 'Overambitious 2-hour routines', 'Skipping hydration'],
                            'rightTitle' => 'High-Leverage Way',
                            'rightContent' => ['Frictionless 20-min routine', 'Consistent wake anchor', 'Sunlight + deliberate hydration', 'Zero screen time in first 30m']
                        ],
                        'verdict' => 'A simple 15-minute routine done every single day beats an extreme routine done twice a week.'
                    ],
                    [
                        'archetype' => 'tech_update',
                        'headerBadge' => 'THE SHIFT',
                        'headline' => "What changes when you master {$topic}",
                        'whatChanged' => "Transitioning from reactive morning fatigue to intentional biological alignment.",
                        'impactMetric' => '+45% Energy',
                        'whyItMatters' => "Eliminates brain fog, lowers baseline cortisol, and builds unstoppably consistent momentum.",
                        'beforeAfter' => [
                            'before' => "Groggy wake-up, reactive scrolling,\nsluggish energy until noon.",
                            'after' => "Clear mental acuity, active focus,\nsustained natural energy all day."
                        ]
                    ],
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'STEP-BY-STEP',
                        'icon' => 'workflow',
                        'headline' => "The 3-phase execution protocol",
                        'definition' => "Break down {$topic} into frictionless micro-steps that don't depend on fluctuating motivation.",
                        'keyTakeaways' => [
                            '**Phase 1 (Wake + Hydrate):** 500ml water with pinch of sea salt.',
                            '**Phase 2 (Optical Reset):** Step outside into direct natural light.',
                            '**Phase 3 (Physical Prime):** Mobility, stretching, or light bodyweight work.'
                        ],
                        'useCase' => "Proven to create lasting habits that withstand travel and busy schedules."
                    ],
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'PRO TIP',
                        'icon' => 'shield',
                        'headline' => "The golden rule of sustainable habits",
                        'definition' => "Never negotiate with your alarm clock. The second you press snooze, you prime your brain for procrastination.",
                        'keyTakeaways' => [
                            '**Preparation:** Set up your clothes and water the night before.',
                            '**Threshold:** Make the first step so easy you cannot say no.',
                            '**Compound Effect:** 1% better each morning equals 37x growth in a year.'
                        ],
                        'proTipOrGotcha' => 'Never miss two days in a row — that is how bad habits accidentally form.',
                        'tipKind' => 'gotcha'
                    ]
                ];

            case 'marketing':
                return [
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'STRATEGY ENGINE',
                        'icon' => 'lightbulb',
                        'headline' => "The modern mechanics of {$topic}",
                        'definition' => "Winning with {$topic} is about building high-retention audience trust and generating saves, not chasing fleeting vanity metrics.",
                        'keyTakeaways' => [
                            '**Hook Mastery:** First 3 seconds dictate 80% of distribution reach.',
                            '**Value Density:** Provide actionable steps with zero fluff or generic filler.',
                            '**Conversion Bridge:** Direct warm attention to an owned channel or list.'
                        ],
                        'useCase' => "Adopted by 7-figure creators and modern brands to compound organic reach."
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'GROWTH PLAYBOOK',
                        'headline' => "Old tactics vs modern high-ROI {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Outdated Playbook',
                            'leftContent' => ['Posting without intent', 'Obsessing over follower counts', 'Surface-level generic advice', 'Spamming engagement pods'],
                            'rightTitle' => 'Modern System',
                            'rightContent' => ['Audience-specific hooks', 'Optimizing for saves & shares', 'Deep authoritative breakdowns', 'Automated funnel capture']
                        ],
                        'verdict' => 'Create content people urgently want to bookmark, not just passively scroll past.'
                    ],
                    [
                        'archetype' => 'tech_update',
                        'headerBadge' => 'ALGORITHM SHIFT',
                        'headline' => "How {$topic} evolved in 2026",
                        'whatChanged' => "Platforms now heavily reward watch time, swipe completion, and outbound DM shares over basic likes.",
                        'impactMetric' => '3.8x Reach',
                        'whyItMatters' => "Carousels that get saved repeatedly continue generating inbound followers for months.",
                        'beforeAfter' => [
                            'before' => "Generic static quotes that\nget forgotten in 10 seconds.",
                            'after' => "Structured visual carousels that\nget saved and referenced for months."
                        ]
                    ],
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'CONTENT FORMULA',
                        'icon' => 'workflow',
                        'headline' => "The 3-stage carousel framework",
                        'definition' => "A battle-tested blueprint to take readers from curious scroller to loyal follower in under 60 seconds.",
                        'keyTakeaways' => [
                            '**Slide 1 (The Hook):** Call out a specific pain point or counter-intuitive truth.',
                            '**Slides 2-5 (The Meat):** Step-by-step framework with clear actionable visuals.',
                            '**Final Slide (The CTA):** Explicit call-to-action to save, share, or follow.'
                        ],
                        'useCase' => "Consistently drives 4x higher save rates compared to standard static posts."
                    ]
                ];

            case 'finance':
                return [
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'WEALTH PRINCIPLE',
                        'icon' => 'database',
                        'headline' => "The core engine of {$topic}",
                        'definition' => "True wealth accumulation is about asymmetric upside, intelligent asset allocation, and ruthlessly minimizing recurring fees.",
                        'keyTakeaways' => [
                            '**Automation:** Direct paycheck splits to investments before lifestyle creep.',
                            '**Compounding:** Long-term time horizon beats speculative short-term timing.',
                            '**Asymmetry:** Cap downside risk while maintaining unlimited upside.'
                        ],
                        'useCase' => "Enables consistent net worth growth regardless of broader market volatility."
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'MONEY MINDSET',
                        'headline' => "Average habits vs wealthy {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Common Financial Traps',
                            'leftContent' => ['Saving only what remains', 'Financing depreciating assets', 'Emotional panic selling', 'Single income stream reliance'],
                            'rightTitle' => 'Wealth Builder Rules',
                            'rightContent' => ['Pay yourself first automatically', 'Acquiring cashflow assets', 'Dollar-cost averaging down', 'Building scalable equity']
                        ],
                        'verdict' => 'Wealth is what you do not see — it is assets compounding silently in the background.'
                    ],
                    [
                        'archetype' => 'tech_update',
                        'headerBadge' => 'STRATEGY SHIFT',
                        'headline' => "The compounding rule of {$topic}",
                        'whatChanged' => "Replacing speculative gambling with structured, automated wealth accumulation.",
                        'impactMetric' => 'Compound Alpha',
                        'whyItMatters' => "Consistent $500 monthly contributions into diversified index vehicles outpaces 95% of active traders.",
                        'beforeAfter' => [
                            'before' => "Manual guessing and timing markets,\nwasting money on high fee funds.",
                            'after' => "Automated, low-cost asset allocation\ncompounding on autopilot."
                        ]
                    ]
                ];

            case 'productivity':
                return [
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'LEVERAGE SYSTEM',
                        'icon' => 'cpu',
                        'headline' => "Mastering {$topic} with systems",
                        'definition' => "Productivity is not about working 14 hours; it is about designing an environment where deep, focused work becomes effortless.",
                        'keyTakeaways' => [
                            '**Energy Management:** Schedule deep work during peak circadian alertness.',
                            '**Batching:** Group low-cognitive administrative tasks into a single window.',
                            '**Constraint Design:** Put your phone in another room during focus blocks.'
                        ],
                        'useCase' => "Allows solo operators and executives to accomplish in 4 hours what takes others all week."
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'SYSTEM AUDIT',
                        'headline' => "Being busy vs high-output {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Fake Productivity',
                            'leftContent' => ['Endless to-do lists', 'Constant tab switching', 'Checking email every 10 mins', 'Over-optimizing tools'],
                            'rightTitle' => 'High-Leverage Focus',
                            'rightContent' => ['1 single priority per day', '90-minute monastic blocks', 'Asynchronous communication', 'Shipping tangible output']
                        ],
                        'verdict' => 'Busyness is a form of cognitive laziness — focus only on tasks that move the needle.'
                    ]
                ];

            case 'coding':
                return [
                    [
                        'archetype' => 'code_syntax',
                        'headerBadge' => 'IDIOMATIC PATTERN',
                        'headline' => "Clean implementation for {$topic}",
                        'definition' => "Modern patterns prioritizing type safety, predictable error handling, and low allocations.",
                        'syntaxSnippet' => "// Modern idiomatic pattern\nconst [data, err] = await safeExecute(task);\nif (err) handleFailure(err);",
                        'codeExample' => [
                            'language' => 'typescript',
                            'code' => "// Production pattern\nexport async function handlePayload<T>(req: Request): Promise<T> {\n  const parsed = await req.json();\n  return validateSchema(parsed);\n}",
                            'output' => '{ success: true, count: 42 }'
                        ],
                        'proTipOrGotcha' => 'Always validate data at runtime boundaries to prevent silent downstream errors.',
                        'tipKind' => 'tip'
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'ARCHITECTURE',
                        'headline' => "Legacy approach vs modern {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Legacy Pattern',
                            'leftContent' => ['Deep nested callbacks', 'Implicit mutable state', 'Uncaught promise rejections', 'Manual resource leaks'],
                            'rightTitle' => 'Modern Architecture',
                            'rightContent' => ['Async/await & streams', 'Immutable functional flows', 'Structured concurrency', 'Automatic lifecycle cleanup']
                        ],
                        'verdict' => 'Modern paradigms cut runtime defects by 65% while drastically improving readability.'
                    ],
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'UNDER THE HOOD',
                        'icon' => 'cpu',
                        'headline' => "Performance & lifecycle mechanics",
                        'definition' => "How memory management, event loop dispatch, and caching layers interact under high concurrent load.",
                        'keyTakeaways' => [
                            '**Memory Locality:** Avoid creating ephemeral objects inside tight loops.',
                            '**Concurrency:** Use thread pools or worker isolates for CPU-bound computations.',
                            '**Resilience:** Implement circuit breakers and graceful timeout boundaries.'
                        ],
                        'useCase' => "Powers microservices serving over 20,000 requests per second at sub-20ms latency."
                    ]
                ];

            default:
                return [
                    [
                        'archetype' => 'deep_dive',
                        'headerBadge' => 'CORE CONCEPT',
                        'icon' => 'lightbulb',
                        'headline' => "What {$topic} really means",
                        'definition' => "{$topic} is best mastered by understanding fundamental first principles rather than blindly copying superficial tactics.",
                        'keyTakeaways' => [
                            '**Inputs & Triggers:** Understand what sets {$topic} in motion.',
                            '**Core Principles:** The fundamental rules that never change.',
                            '**Feedback Loop:** Measuring results and adjusting your strategy.'
                        ],
                        'useCase' => "Applied consistently by top industry practitioners to outperform competitors."
                    ],
                    [
                        'archetype' => 'comparison_diff',
                        'headerBadge' => 'KEY CONTRAST',
                        'headline' => "Amateur habits vs mastering {$topic}",
                        'comparisonMode' => 'old_vs_new',
                        'comparisonData' => [
                            'leftTitle' => 'Amateur Trap',
                            'leftContent' => ['Inconsistent application', 'Expecting instant results', 'Following unproven advice', 'No feedback tracking'],
                            'rightTitle' => 'Mastery Approach',
                            'rightContent' => ['Daily deliberate practice', 'Compounding mindset', 'Data-driven execution', 'Iterative improvements']
                        ],
                        'verdict' => 'Master the fundamentals first — advanced tactics only work when the foundation is solid.'
                    ],
                    [
                        'archetype' => 'tech_update',
                        'headerBadge' => 'THE EVOLUTION',
                        'headline' => "How {$topic} evolved recently",
                        'whatChanged' => "Moving away from bloated, complex processes towards clean, high-efficiency workflows.",
                        'impactMetric' => '2x Faster',
                        'whyItMatters' => "Less friction means more time focused on what truly drives results.",
                        'beforeAfter' => [
                            'before' => "Complicated, manual steps with\nhigh friction and frequent burnout.",
                            'after' => "Streamlined, repeatable system with\npredictable and consistent results."
                        ]
                    ]
                ];
        }
    }

    public static function categoryFor(string $topic): string {
        $domain = self::detectDomain($topic);
        switch ($domain) {
            case 'health': return 'WELLNESS';
            case 'marketing': return 'GROWTH';
            case 'finance': return 'WEALTH';
            case 'productivity': return 'PRODUCTIVITY';
            case 'coding': return 'CODING';
            default: return 'INSIGHTS';
        }
    }

    public static function titleFor(string $topic): string {
        return ucwords(trim($topic)) . " Guide";
    }
}
