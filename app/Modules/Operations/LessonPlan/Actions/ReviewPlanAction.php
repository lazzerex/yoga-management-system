<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\AiSuggestion;
use App\Models\LessonPlan;
use App\Models\User;
use App\Support\Ai\GeminiClient;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReviewPlanAction
{
    private const SYSTEM = 'You are helping a yoga centre manager review a lesson plan written by one of their teachers. '
        .'Return exactly four sections, headed :headings. '
        .'For each, describe what is missing or risky in two or three sentences, naming the postures or minutes involved '
        .'and why it matters for this class type and level. Then give a concrete fix in two or three sentences: '
        .'which postures to add, remove or move, with hold lengths or timings. Rate each: ok when nothing needs changing, '
        .'note for a minor improvement, warn for something that should be fixed before the class runs. '
        .'Also grade the asana sequence as written: an integer from 0 to 100, and one or two sentences of at most '
        .'forty words saying what that score reflects. The grade is advisory for the manager only. '
        .'Never approve or reject the plan, never give medical advice, and never address the teacher directly.';

    private const HEADINGS = [
        'en' => 'Warm-up and cool-down, Sequencing, Level and duration, and Safety',
        'vi' => 'Khởi động và thả lỏng, Trình tự tư thế, Cấp độ và thời lượng, và An toàn',
    ];

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'sections' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'heading' => ['type' => 'string'],
                        'issue' => ['type' => 'string'],
                        'fix' => ['type' => 'string'],
                        'severity' => ['type' => 'string', 'enum' => ['ok', 'note', 'warn']],
                    ],
                    'required' => ['heading', 'issue', 'fix', 'severity'],
                ],
            ],
            'grade' => [
                'type' => 'object',
                'properties' => [
                    'score' => ['type' => 'integer'],
                    'verdict' => ['type' => 'string'],
                ],
                'required' => ['score', 'verdict'],
            ],
        ],
        'required' => ['sections', 'grade'],
    ];

    private const FAKE = ['grade' => [
        'score' => 72,
        'verdict' => 'Sound structure, but the opening needs work before this class runs.',
    ], 'sections' => [
        [
            'heading' => 'Warm-up and cool-down',
            'severity' => 'warn',
            'issue' => 'The sequence opens straight into standing postures with no joint mobility first.',
            'fix' => 'Add three to five minutes of cat-cow and gentle lunges before the first standing pose.',
        ],
        [
            'heading' => 'Sequencing',
            'severity' => 'note',
            'issue' => 'The backbends sit directly beside the deep forward folds with nothing between them.',
            'fix' => 'Place a neutral pose such as a supine twist between the two groups.',
        ],
        [
            'heading' => 'Level and duration',
            'severity' => 'note',
            'issue' => 'The posture count reads long for the stated duration at this level.',
            'fix' => 'Drop two postures or extend the class by ten minutes.',
        ],
        [
            'heading' => 'Safety',
            'severity' => 'ok',
            'issue' => 'Each standing posture already names a hold length.',
            'fix' => 'Name an alternative for the deepest posture so everyone still has something to do.',
        ],
    ]];

    public function __construct(private GeminiClient $client) {}

    /**
     * @return array{sections: array<int, array<string, string>>, grade: array{score: int, verdict: string}|null}|null
     */
    public function execute(User $user, LessonPlan $plan, ?Media $image = null): ?array
    {
        $prompt = implode("\n", [
            'Title: '.$plan->title,
            'Class type: '.$plan->classType->name,
            'Level: '.__('operations.level'.ucfirst($plan->level)),
            'Duration: '.$plan->duration_minutes.' minutes',
            'Objective: '.($plan->objective ?: 'not stated'),
            'Asana sequence:',
            $plan->asana_sequence,
        ]);

        $imagePart = null;

        if ($image !== null) {
            // The image itself never lands in ai_suggestions: the row records that one went.
            $prompt .= "\nAttached image sent by reviewer opt-in: #".$image->id.' '.$image->file_name;

            $imagePart = ['inline_data' => [
                'mime_type' => $image->mime_type,
                'data' => base64_encode(file_get_contents($image->getPath())),
            ]];
        }

        $system = str_replace(':headings', self::HEADINGS[app()->getLocale()] ?? self::HEADINGS['en'], self::SYSTEM);

        $response = $this->client->generate($system.GeminiClient::replyLanguage(), self::SCHEMA, $prompt, self::FAKE, $imagePart);
        $sections = $response['sections'] ?? null;

        if (! is_array($sections) || $sections === []) {
            return null;
        }

        AiSuggestion::create([
            'user_id' => $user->id,
            'kind' => AiSuggestion::KIND_REVIEW,
            'prompt' => $prompt,
            'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'model' => GeminiClient::model(),
        ]);

        return ['sections' => $sections, 'grade' => $this->grade($response['grade'] ?? null)];
    }

    /**
     * The findings are the review; a missing or out-of-scale grade must not lose them.
     *
     * @return array{score: int, verdict: string}|null
     */
    private function grade(mixed $grade): ?array
    {
        if (! is_array($grade) || ! isset($grade['score']) || ! is_numeric($grade['score'])) {
            return null;
        }

        return [
            'score' => max(0, min(100, (int) $grade['score'])),
            'verdict' => (string) ($grade['verdict'] ?? ''),
        ];
    }
}
