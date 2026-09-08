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
        .'Return exactly four sections, headed Warm-up and cool-down, Sequencing, Level and duration, and Safety. '
        .'For each, state what is missing or risky and how to fix it, and rate it: ok when nothing needs changing, '
        .'note for a minor improvement, warn for something that should be fixed before the class runs. '
        .'Never approve or reject the plan, never give medical advice, and never address the teacher directly.';

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
        ],
        'required' => ['sections'],
    ];

    private const FAKE = ['sections' => [
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
     * @return array<int, array<string, string>>|null
     */
    public function execute(User $user, LessonPlan $plan, ?Media $image = null): ?array
    {
        $prompt = implode("\n", [
            'Title: '.$plan->title,
            'Class type: '.$plan->classType->name,
            'Level: '.$plan->level,
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

        $response = $this->client->generate(self::SYSTEM, self::SCHEMA, $prompt, self::FAKE, $imagePart);
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

        return $sections;
    }
}
