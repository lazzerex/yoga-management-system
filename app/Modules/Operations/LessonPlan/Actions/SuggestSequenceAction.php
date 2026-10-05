<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\AiSuggestion;
use App\Models\User;
use App\Support\Ai\GeminiClient;

class SuggestSequenceAction
{
    private const SYSTEM = 'You are assisting a qualified yoga teacher who is drafting a lesson plan. '
        .'Return one entry per posture, in teaching order, opening with a warm-up and closing with a cool-down. '
        .'Keep the whole sequence inside the stated duration, and give no medical advice. '
        .'The cue is one short teaching instruction, not a description of the posture.';

    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'steps' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'sanskrit' => ['type' => 'string'],
                        'duration' => ['type' => 'string'],
                        'cue' => ['type' => 'string'],
                    ],
                    'required' => ['name', 'sanskrit', 'duration', 'cue'],
                ],
            ],
        ],
        'required' => ['steps'],
    ];

    private const FAKE = ['steps' => [
        ['name' => 'Easy Seat', 'sanskrit' => 'Sukhasana', 'duration' => '10 breaths', 'cue' => 'Settle the sitting bones, lengthen through the crown.'],
        ['name' => 'Cat-Cow', 'sanskrit' => 'Marjaryasana-Bitilasana', 'duration' => '8 rounds', 'cue' => 'Let the breath lead the spine, not the shoulders.'],
        ['name' => 'Downward-Facing Dog', 'sanskrit' => 'Adho Mukha Svanasana', 'duration' => '5 breaths', 'cue' => 'Bend the knees freely to keep the spine long.'],
        ['name' => 'Low Lunge', 'sanskrit' => 'Anjaneyasana', 'duration' => '5 breaths each side', 'cue' => 'Draw the back hip forward before sinking down.'],
        ['name' => 'Warrior II', 'sanskrit' => 'Virabhadrasana II', 'duration' => '5 breaths each side', 'cue' => 'Front knee tracks over the second toe.'],
        ['name' => 'Triangle', 'sanskrit' => 'Trikonasana', 'duration' => '5 breaths each side', 'cue' => 'Reach long before tipping, keep both sides of the waist even.'],
        ['name' => 'Bridge', 'sanskrit' => 'Setu Bandha Sarvangasana', 'duration' => '3 rounds of 5 breaths', 'cue' => 'Press through the heels, keep the chin off the chest.'],
        ['name' => 'Supine Twist', 'sanskrit' => 'Supta Matsyendrasana', 'duration' => '8 breaths each side', 'cue' => 'Let the top shoulder stay heavy rather than forcing the knee down.'],
        ['name' => 'Corpse Pose', 'sanskrit' => 'Savasana', 'duration' => '5 minutes', 'cue' => 'Name the end of the practice out loud before the room settles.'],
    ]];

    public function __construct(private GeminiClient $client) {}

    /**
     * @param  array{class_type: string, level: string, duration_minutes: int, objective: string}  $inputs
     * @return array<int, array<string, string>>|null
     */
    public function execute(User $user, array $inputs): ?array
    {
        // Built from four named values, never from a model or an array that could grow
        // a student field later. This is the whole of the privacy guarantee.
        $prompt = implode("\n", [
            'Class type: '.$inputs['class_type'],
            'Level: '.__('operations.level'.ucfirst($inputs['level'])),
            'Duration: '.$inputs['duration_minutes'].' minutes',
            'Objective: '.($inputs['objective'] !== '' ? $inputs['objective'] : 'general balanced practice'),
        ]);

        $response = $this->client->generate(self::SYSTEM.GeminiClient::replyLanguage(), self::SCHEMA, $prompt, self::FAKE);
        $steps = $response['steps'] ?? null;

        if (! is_array($steps) || $steps === []) {
            return null;
        }

        AiSuggestion::create([
            'user_id' => $user->id,
            'kind' => AiSuggestion::KIND_SEQUENCE,
            'prompt' => $prompt,
            'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'model' => GeminiClient::model(),
        ]);

        return $steps;
    }
}
