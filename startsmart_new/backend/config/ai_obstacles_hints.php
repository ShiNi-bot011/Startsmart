<?php
// backend/config/ai_obstacles_hints.php
// Isolated AI & Subject-Context Helper strictly for US005 (Obstacles) & US009 (Hints)
// Named uniquely so teammates can build their own AI modules without merge conflicts.

define('US005_US009_AI_PROVIDER', 'gemini'); // Options: 'gemini', 'groq', 'openai', 'openrouter'
define('US005_US009_AI_API_KEY', '');        // Paste local API key here to enable live LLM; keep empty before git push
define('US005_US009_AI_MODEL', 'gemini-2.5-flash');

/**
 * Classifies a task into a Faculty of Computing academic context profile.
 */
function us005_us009_detectAcademicContext(array $task): array
{
    $subjectCode = strtoupper(trim($task['subjectCode'] ?? ''));
    $subjectName = trim($task['subjectName'] ?? 'Computing Course');
    $taskType    = trim($task['taskType'] ?? 'General');
    $title       = trim($task['title'] ?? '');
    $desc        = trim($task['description'] ?? '');
    $haystack    = strtolower("$subjectCode $subjectName $taskType $title $desc");

    $profile = 'general';
    if (preg_match('/\b(quiz|test|exam|midterm|final|revision|study)\b/', $haystack)) {
        $profile = 'exam_quiz';
    } elseif (preg_match('/\b(code|coding|program|php|java|c\+\+|python|web|database|sql|api|debug|lab|software|app|system|git|github|html|css|js|algorithm|data structure)\b/', $haystack)) {
        $profile = 'coding_tech';
    } elseif (preg_match('/\b(report|essay|srs|sdd|proposal|documentation|review|paper|writing|case study|article)\b/', $haystack)) {
        $profile = 'writing_report';
    } elseif (preg_match('/\b(present|presentation|slide|demo|pitch|group|sprint|team)\b/', $haystack)) {
        $profile = 'presentation_team';
    }

    return [
        'profile'     => $profile,
        'subjectCode' => $subjectCode,
        'subjectName' => $subjectName,
        'taskType'    => $taskType,
        'title'       => $title,
        'description' => $desc,
    ];
}

/**
 * Returns subject- and task-aware questions & obstacle options for US005.
 */
function us005_getSubjectAwareObstaclePromptSet(array $task): array
{
    $ctx = us005_us009_detectAcademicContext($task);
    $label = ($ctx['subjectCode'] !== '' ? $ctx['subjectCode'] . ' - ' : '') . $ctx['subjectName'];

    switch ($ctx['profile']) {
        case 'coding_tech':
            return [
                'contextLabel' => "Technical / Coding Task ({$label})",
                'q1Label' => "What technical blocker is slowing down \"{$ctx['title']}\"?",
                'q2Label' => "Which development stage is stuck? (optional)",
                'q3Label' => "Any environment, tool, or teammate dependency? (optional)",
                'categories' => [
                    'Debugging or syntax/runtime error',
                    'Database, SQL, or API integration issue',
                    'Environment, Git, or local server setup',
                    'Unclear system logic or architecture',
                    'Task scope feels too large to implement',
                    'Not enough time before deadline',
                ],
                'secondaryOptions' => [
                    'I do not understand the required logic or algorithm.',
                    'Local environment (XAMPP/MAMP/Git) is giving errors.',
                    'Database schema or backend connection is failing.',
                    'The task has too many features to build at once.',
                    'I am unsure which file or function to start coding first.',
                ],
            ];

        case 'exam_quiz':
            return [
                'contextLabel' => "Quiz / Exam Preparation ({$label})",
                'q1Label' => "What is your biggest hurdle preparing for \"{$ctx['title']}\"?",
                'q2Label' => "Which study area needs the most attention? (optional)",
                'q3Label' => "Any time or focus challenge? (optional)",
                'categories' => [
                    'Difficulty understanding core lecture concepts',
                    'Too many topics/chapters to revise at once',
                    'Lack of past-year or practice questions',
                    'Memorizing formulas, syntax, or definitions',
                    'Not enough revision time left',
                ],
                'secondaryOptions' => [
                    'Lecture slides are hard to follow without examples.',
                    'I keep forgetting key concepts after reading.',
                    'I have not practiced solving past exam/quiz questions.',
                    'Multiple deadlines are clashing with my revision.',
                    'I feel overwhelmed by the syllabus coverage.',
                ],
            ];

        case 'writing_report':
            return [
                'contextLabel' => "Report / Documentation Task ({$label})",
                'q1Label' => "What is blocking your progress on \"{$ctx['title']}\"?",
                'q2Label' => "Which part of writing/documentation is hardest? (optional)",
                'q3Label' => "Any rubric or reference issue? (optional)",
                'categories' => [
                    'Unclear assignment rubric or format requirements',
                    'Difficulty structuring sections or diagrams',
                    'Finding credible references or technical facts',
                    'Writer\'s block / unsure how to start first draft',
                    'Tight deadline / heavy workload',
                ],
                'secondaryOptions' => [
                    'I am unsure what the lecturer expects in each section.',
                    'Creating UML/ERD diagrams or tables is taking too long.',
                    'The report feels too long to write in one sitting.',
                    'I need to review lecture notes before writing.',
                    'Waiting on group members\' sections to align.',
                ],
            ];

        case 'presentation_team':
            return [
                'contextLabel' => "Presentation / Group Milestone ({$label})",
                'q1Label' => "What is holding back \"{$ctx['title']}\"?",
                'q2Label' => "Which preparation step needs help? (optional)",
                'q3Label' => "Any team coordination blocker? (optional)",
                'categories' => [
                    'Waiting on teammate deliverables or integration',
                    'Live demo or prototype not ready yet',
                    'Structuring slides within the time limit',
                    'Unsure how to explain technical parts clearly',
                    'Not enough time to rehearse',
                ],
                'secondaryOptions' => [
                    'Our code/parts are not merged or tested together yet.',
                    'Too much content to fit into a short presentation.',
                    'Unclear rubric criteria for the demo.',
                    'Difficulty coordinating schedules with teammates.',
                    'Nervous or unsure how to open the presentation.',
                ],
            ];

        default:
            return [
                'contextLabel' => "{$ctx['taskType']} ({$label})",
                'q1Label' => "What is the main obstacle for \"{$ctx['title']}\" in {$ctx['subjectName']}?",
                'q2Label' => "Another difficulty (optional)",
                'q3Label' => "Any other difficulty (optional)",
                'categories' => [
                    'Unclear task requirements',
                    'Not understanding a key concept',
                    'Task feels too big or overwhelming',
                    'Unsure where to start',
                    'Not enough time / procrastination',
                ],
                'secondaryOptions' => [
                    'I do not understand what is required.',
                    'The task feels too large.',
                    'I need help understanding a concept.',
                    'I am unsure how to begin.',
                    'Something else is getting in the way.',
                ],
            ];
    }
}

/**
 * Calls the configured LLM API if US005_US009_AI_API_KEY is set; otherwise returns null
 * so the caller cleanly uses the built-in context-aware fallback.
 */
function us005_us009_callLLM(string $systemPrompt, string $userPrompt): ?string
{
    if (trim(US005_US009_AI_API_KEY) === '' || !function_exists('curl_init')) {
        return null;
    }

    $provider = strtolower(trim(US005_US009_AI_PROVIDER));
    $url = '';
    $headers = ['Content-Type: application/json'];
    $payload = [];

    if ($provider === 'gemini') {
        $model = US005_US009_AI_MODEL ?: 'gemini-2.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $headers[] = 'x-goog-api-key: ' . trim(US005_US009_AI_API_KEY);
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 350],
        ];
    } else {
        if ($provider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
        } elseif ($provider === 'openrouter') {
            $url = 'https://openrouter.ai/api/v1/chat/completions';
        } else {
            $url = 'https://api.openai.com/v1/chat/completions';
        }

        $headers[] = 'Authorization: Bearer ' . trim(US005_US009_AI_API_KEY);
        $payload = [
            'model' => US005_US009_AI_MODEL ?: 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.5,
            'max_tokens' => 350,
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($raw, true);
    if ($provider === 'gemini') {
        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '') ?: null;
    }
    return trim($data['choices'][0]['message']['content'] ?? '') ?: null;
}

/**
 * Generates a subject-, task-, and obstacle-aware progressive hint for US009.
 */
function us009_generateContextAwareHint(array $task, ?array $obstacle, string $userQuestion, int $hintLevel = 1): array
{
    $ctx = us005_us009_detectAcademicContext($task);
    $subjectLabel = ($ctx['subjectCode'] !== '' ? $ctx['subjectCode'] . ' - ' : '') . $ctx['subjectName'];
    $obstacleType = $obstacle['obstacleType'] ?? 'Not specified yet';
    $obstacleDesc = trim($obstacle['description'] ?? '');

    $systemPrompt = "You are StartSmart AI, an academic study coach for Faculty of Computing students at UTM. "
        . "Give concise, actionable, step-by-step hints (3-4 bullet points max). "
        . "Guide the student on HOW to start or debug without writing the complete solution for them.";

    $userPrompt = "Subject: {$subjectLabel}\n"
        . "Task Type: {$ctx['taskType']}\n"
        . "Task Title: {$ctx['title']}\n"
        . "Task Description: " . ($ctx['description'] ?: 'None provided') . "\n"
        . "Identified Obstacle: {$obstacleType}" . ($obstacleDesc !== '' ? " ({$obstacleDesc})" : '') . "\n"
        . "Hint Depth: Level {$hintLevel} (1 = gentle starting nudge, 2 = concrete tactical breakdown)\n"
        . "Student's Specific Question: " . ($userQuestion !== '' ? $userQuestion : 'Help me get unstuck and take the next small step.');

    $aiReply = us005_us009_callLLM($systemPrompt, $userPrompt);
    if ($aiReply !== null) {
        return [
            'source' => 'AI Agent (' . strtoupper(US005_US009_AI_PROVIDER) . ')',
            'text'   => $aiReply,
        ];
    }

    $focusArea = $userQuestion !== '' ? " specifically around \"{$userQuestion}\"" : '';
    $bullets = [];

    if ($ctx['profile'] === 'coding_tech') {
        if ($hintLevel === 1) {
            $bullets[] = "Break \"{$ctx['title']}\" into one tiny vertical slice first (e.g., verify your database connection or single input/output flow before adding full features){$focusArea}.";
            $bullets[] = "Because your current blocker is **{$obstacleType}**, isolate the exact file/function involved and print/log intermediate values (`var_dump` / console log) to confirm where data stops matching expectations.";
            $bullets[] = "Set a 15-minute timer to get just that single test case passing before moving on.";
        } else {
            $bullets[] = "**Step 1 (Input Check):** Verify every variable name, SQL column name, and form `name` attribute in `{$subjectLabel}` matches your schema.";
            $bullets[] = "**Step 2 (Minimal Reproduction):** Comment out secondary logic and test the core query or function with one hardcoded sample input.";
            $bullets[] = "**Step 3 (Edge Case):** Once the happy path works, add validation for empty or unexpected inputs.";
        }
    } elseif ($ctx['profile'] === 'exam_quiz') {
        if ($hintLevel === 1) {
            $bullets[] = "For **{$subjectLabel}** ({$ctx['title']}), list the top 3 core concepts from the lecture slides instead of re-reading everything passively{$focusArea}.";
            $bullets[] = "To overcome **{$obstacleType}**, pick one worked example from your notes, cover the solution, and try reproducing the steps on blank paper.";
            $bullets[] = "Mark the exact step where you pause—that is the only concept you need to review right now.";
        } else {
            $bullets[] = "**Active Recall Drill:** Write 3 self-test questions based on lecture learning outcomes and answer them without notes.";
            $bullets[] = "**Compare & Patch:** Check your answers against lecture examples, note any missing keywords or formulas, and redo the problem once more.";
        }
    } elseif ($ctx['profile'] === 'writing_report') {
        if ($hintLevel === 1) {
            $bullets[] = "Open a blank document for \"{$ctx['title']}\" and write only the main section headings first—do not worry about full sentences yet{$focusArea}.";
            $bullets[] = "To address **{$obstacleType}**, add 2–3 bullet points under each heading describing what evidence, diagram, or explanation belongs there.";
            $bullets[] = "Draft the easiest section first (such as System Overview or Methodology) for 15 minutes to build momentum.";
        } else {
            $bullets[] = "**Rubric Alignment Check:** Match each heading directly to a requirement in your {$subjectLabel} assignment brief.";
            $bullets[] = "**PEEL Paragraph Pattern:** For each subsection, write 1 Point sentence, 1 Evidence/Diagram reference, and 1 Explanation linking back to the task goal.";
        }
    } else {
        if ($hintLevel === 1) {
            $bullets[] = "Define the smallest 15-minute deliverable for \"{$ctx['title']}\" in **{$subjectLabel}**{$focusArea}.";
            $bullets[] = "Since your obstacle is **{$obstacleType}**, list the 2 specific questions or sub-steps you need to clarify before doing the rest.";
            $bullets[] = "Complete just that first mini-step now and save your progress.";
        } else {
            $bullets[] = "**Concrete 3-Step Plan:** (1) Gather your notes/brief in one place, (2) complete a rough draft or skeleton of part 1, (3) review against the due date milestones.";
        }
    }

    return [
        'source' => 'Smart Context Engine (AI-Ready Offline Mode)',
        'text'   => implode("\n", array_map(fn($b) => "• " . $b, $bullets)),
    ];
}

/**
 * Logs prompt + response into ai_conversations and ai_messages tables.
 */
function us009_logAiInteraction(mysqli $conn, int $taskId, string $subjectTitle, string $userPrompt, string $aiResponse): void
{
    $safeSubject = mysqli_real_escape_string($conn, substr($subjectTitle, 0, 150));
    $convRes = mysqli_query($conn, "SELECT conversationId FROM ai_conversations WHERE taskId = $taskId ORDER BY conversationId DESC LIMIT 1");
    $conv = $convRes ? mysqli_fetch_assoc($convRes) : null;

    if ($conv) {
        $conversationId = (int) $conv['conversationId'];
    } else {
        mysqli_query($conn, "INSERT INTO ai_conversations (taskId, subject) VALUES ($taskId, '$safeSubject')");
        $conversationId = (int) mysqli_insert_id($conn);
    }

    if ($conversationId > 0) {
        $safeUser = mysqli_real_escape_string($conn, $userPrompt);
        $safeAi   = mysqli_real_escape_string($conn, $aiResponse);
        mysqli_query($conn, "INSERT INTO ai_messages (conversationId, content, sender) VALUES ($conversationId, '$safeUser', 'student')");
        mysqli_query($conn, "INSERT INTO ai_messages (conversationId, content, sender) VALUES ($conversationId, '$safeAi', 'ai_agent')");
    }
}