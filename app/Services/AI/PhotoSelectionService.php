<?php

namespace App\Services\AI;

use App\Models\Reports\Monthly\DPPhoto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PhotoSelectionService
{
    /**
     * Select most relevant photos for aggregated reports using AI
     *
     * @param Collection $photos
     * @param int $limit
     * @param array $context
     * @return Collection
     */
    public static function selectRelevantPhotos(
        Collection $photos,
        int $limit,
        array $context = []
    ): Collection {
        try {
            if ($photos->isEmpty() || $limit <= 0) {
                return collect();
            }

            // If we have fewer photos than limit, return all
            if ($photos->count() <= $limit) {
                return $photos;
            }

            // Prepare photo data for AI analysis
            $photoData = self::preparePhotoData($photos, $context);

            // Get AI prompt for photo selection
            $prompt = self::getPhotoSelectionPrompt($photoData, $limit, $context);

            // Call OpenAI API
            $response = self::callOpenAIForPhotoSelection($prompt);
            $selectedIndices = self::parsePhotoSelectionResponse($response, $photos->count());

            // Select photos based on AI recommendations
            $selectedPhotos = collect();
            foreach ($selectedIndices as $index) {
                if (isset($photos[$index])) {
                    $selectedPhotos->push($photos[$index]);
                }
            }

            // If AI didn't select enough, fill with remaining photos
            if ($selectedPhotos->count() < $limit) {
                $remaining = $photos->diff($selectedPhotos)->take($limit - $selectedPhotos->count());
                $selectedPhotos = $selectedPhotos->merge($remaining);
            }

            Log::info('Photos selected using AI', [
                'total_photos' => $photos->count(),
                'selected_count' => $selectedPhotos->count(),
                'limit' => $limit
            ]);

            return $selectedPhotos->take($limit);

        } catch (\Exception $e) {
            Log::warning('AI photo selection failed, using fallback method', [
                'error' => $e->getMessage()
            ]);

            // Fallback: select photos with descriptions first, then by date
            return self::fallbackPhotoSelection($photos, $limit);
        }
    }

    /**
     * Prepare photo data for AI analysis
     *
     * @param Collection $photos
     * @param array $context
     * @return array
     */
    private static function preparePhotoData(Collection $photos, array $context): array
    {
        $photoData = [];
        $index = 0;

        foreach ($photos as $photo) {
            $photoData[] = [
                'index' => $index,
                'description' => $photo->description ?? '',
                'caption' => $photo->caption ?? '',
                'photo_name' => $photo->photo_name ?? '',
                'created_at' => $photo->created_at ? $photo->created_at->format('Y-m-d') : '',
                'has_description' => !empty($photo->description),
            ];
            $index++;
        }

        return [
            'photos' => $photoData,
            'context' => $context,
            'total_count' => count($photoData),
        ];
    }

    /**
     * Get prompt for photo selection
     *
     * @param array $photoData
     * @param int $limit
     * @param array $context
     * @return string
     */
    private static function getPhotoSelectionPrompt(array $photoData, int $limit, array $context): string
    {
        $jsonData = json_encode($photoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $contextInfo = !empty($context) ? json_encode($context, JSON_PRETTY_PRINT) : 'No specific context provided';

        return <<<PROMPT
Analyze the following collection of photos and select the {$limit} most relevant and representative photos for an aggregated report.

Selection Criteria:
1. Photos with meaningful descriptions should be prioritized
2. Ensure diversity - select photos representing different aspects/activities
3. Prioritize photos that show significant events or achievements
4. Avoid selecting similar/redundant photos
5. Consider the context provided

Photo Data:
{$jsonData}

Context:
{$contextInfo}

Provide your response in JSON format:
{
  "selected_indices": [0, 5, 12, ...],
  "reasoning": "Brief explanation of selection criteria used",
  "diversity_notes": "Notes on ensuring diversity in selection"
}

Return exactly {$limit} photo indices (0-based) that best represent the report period.
PROMPT;
    }

    /**
     * Parse photo selection response from AI
     *
     * @param string $response
     * @param int $maxIndex
     * @return array
     */
    private static function parsePhotoSelectionResponse(string $response, int $maxIndex): array
    {
        try {
            $json = ResponseParser::extractJson($response);

            if ($json && isset($json['selected_indices']) && is_array($json['selected_indices'])) {
                // Validate indices
                $indices = array_filter($json['selected_indices'], function ($index) use ($maxIndex) {
                    return is_numeric($index) && $index >= 0 && $index < $maxIndex;
                });

                return array_values(array_unique(array_map('intval', $indices)));
            }

            // Fallback: try to extract numbers from response
            preg_match_all('/\b(\d+)\b/', $response, $matches);
            if (!empty($matches[1])) {
                $indices = array_map('intval', $matches[1]);
                $indices = array_filter($indices, function ($index) use ($maxIndex) {
                    return $index >= 0 && $index < $maxIndex;
                });
                return array_values(array_unique(array_slice($indices, 0, 30))); // Limit to reasonable number
            }

            return [];

        } catch (\Exception $e) {
            Log::warning('Failed to parse photo selection response', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Fallback photo selection method
     *
     * @param Collection $photos
     * @param int $limit
     * @return Collection
     */
    private static function fallbackPhotoSelection(Collection $photos, int $limit): Collection
    {
        // Prioritize photos with descriptions
        $withDescriptions = $photos->filter(function ($photo) {
            return !empty($photo->description);
        });

        $withoutDescriptions = $photos->diff($withDescriptions);

        // Take from photos with descriptions first
        $selected = $withDescriptions->take($limit);

        // Fill remaining slots from photos without descriptions
        if ($selected->count() < $limit) {
            $remaining = $withoutDescriptions->take($limit - $selected->count());
            $selected = $selected->merge($remaining);
        }

        return $selected;
    }

    /**
     * Call OpenAI API for photo selection
     *
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
    private static function callOpenAIForPhotoSelection(string $prompt): string
    {
        if (!config('openai.api_key')) {
            throw new \Exception('OpenAI API key is not configured.');
        }

        $model = config('ai.openai.model', 'gpt-4o-mini');
        $maxTokens = 2000; // Lower for photo selection
        $temperature = config('ai.openai.temperature', 0.3);

        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at selecting representative photos for reports. Focus on diversity, relevance, and significance.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            $content = $response->choices[0]->message->content ?? '';

            if (empty($content)) {
                throw new \Exception('Empty response from OpenAI API');
            }

            return $content;
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed for photo selection', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
