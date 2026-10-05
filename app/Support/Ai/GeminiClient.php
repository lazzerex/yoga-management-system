<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * One HTTP call, shared by every AI caller. Returns null on any failure so a caller
 * never has to catch: no suggestion is a normal outcome, not an exception.
 *
 * Every caller asks for JSON against a schema, so the response can be rendered as
 * real markup instead of the Markdown the model would otherwise return.
 */
class GeminiClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * A clone ships without a key, so 'fake' is what makes the buttons render there.
     */
    public static function isConfigured(): bool
    {
        return config('services.gemini.driver') === 'fake'
            || filled(config('services.gemini.key'));
    }

    public static function model(): string
    {
        return config('services.gemini.driver') === 'fake'
            ? 'fake'
            : (string) config('services.gemini.model');
    }

    /** Appended to a system instruction so the reply reads in the viewer's language. */
    public static function replyLanguage(): string
    {
        return app()->getLocale() === 'vi'
            ? ' Write every piece of text, headings included, in natural Vietnamese, keeping asana names in their usual Sanskrit or English form.'
            : ' Write every piece of text in English.';
    }

    /**
     * @param  array<string, mixed>  $schema  an OpenAPI subset schema the reply must match
     * @param  array<string, mixed>  $fakeResponse  what the 'fake' driver returns, same shape
     * @param  array<string, mixed>|null  $imagePart  an inline_data part, already base64 encoded
     * @return array<string, mixed>|null
     */
    public function generate(string $systemInstruction, array $schema, string $prompt, array $fakeResponse, ?array $imagePart = null): ?array
    {
        if (config('services.gemini.driver') === 'fake') {
            return $fakeResponse;
        }

        $parts = [['text' => $prompt]];

        if ($imagePart !== null) {
            $parts[] = $imagePart;
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
                ->timeout((int) config('services.gemini.timeout'))
                ->post(self::ENDPOINT.config('services.gemini.model').':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 4096,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                    ],
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        // A prompt blocked by safety filters comes back 200 with no candidate at all,
        // and a reply cut off by the token ceiling carries truncated, unparsable JSON.
        $candidate = $response->json('candidates.0');

        if ($candidate === null || ($candidate['finishReason'] ?? null) !== 'STOP') {
            return null;
        }

        $decoded = json_decode((string) ($candidate['content']['parts'][0]['text'] ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }
}
