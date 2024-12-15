<?php

namespace App\Helpers;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

/**
 * Class VisionApiHelper.
 */
class VisionApiHelper
{
    /**
     * Example of the JSON to be returned as the estimation.
     *
     * @return string
     */
    public static function json(): string
    {
        return '{' .
            '"meal":"Name the meal",' .
            '"description":"Describe if meal is healthy or not.",' .
            '"error":"Describe the error (might be no food on photo) or null here.",' .
            '"ingredients":[' .
                '{' .
                    '"name":"Ingredient",' .
                    '"name_en":"Ingredient name in English",' .
                    '"serving_size":"1 medium sized",' .
                    '"weight":130.5,' .
                    '"calories":62,' .
                    '"carbohydrates":15.4,' .
                    '"fiber":3.1,' .
                    '"sugar":12.2,' .
                    '"protein":1.2,' .
                    '"fat":0.2' .
                '}' .
            '],' .
            '"total":{' .
                '"weight":130.5,' .
                '"calories":62,' .
                '"carbohydrates":15.4,' .
                '"fiber":3.1,' .
                '"sugar":12.2,' .
                '"protein":1.2,' .
                '"fat":0.2' .
                '}' .
            '}';
    }

    /**
     * Example of the JSON to be returned as the estimation.
     *
     * @param string $language
     *
     * @return string
     */
    public static function prompt(string $language): string
    {
        $json = static::json();

        return "Here is a photo of the dish. Please estimate calories, nutrients."
            . " Please respond in JSON format (weight in grams): {$json}."
            . " Please respond in language with code: {$language}."
            . " If there is food always estimate it and return JSON (even if there are no ingredients),"
            . " don't ask for details.";
    }

    /**
     * Estimate calories and nutrients of the given image, in the given language.
     *
     * @param string $imageUrl
     * @param string $language
     * @param int $tokens
     *
     * @return CreateResponse
     */
    public static function estimate(string $imageUrl, string $language = 'en', int $tokens = 15000): CreateResponse
    {
        $prompt = static::prompt($language);

        return OpenAI::chat()
            ->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                        ],
                    ],
                ],
                'max_tokens' => $tokens,
            ]);
    }
}
