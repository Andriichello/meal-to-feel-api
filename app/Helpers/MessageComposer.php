<?php

namespace App\Helpers;

use App\Enums\ResultStatus;
use App\Models\Meal;
use App\Models\Result;

/**
 * Class MessageComposer.
 */
class MessageComposer
{
    /**
     * Escape special characters with a backslash.
     *
     * @param string|null $string
     * @param array|null $search
     * @param array|null $replace
     *
     * @return string|null
     */
    public static function escape(
        ?string $string,
        ?array $search = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
        ?array $replace = ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!']
    ): ?string {
        if (empty($string)) {
            return $string;
        }

        return (string) str_replace($search, $replace, $string);
    }

    /**
     * Compose a message about the dish estimation result.
     *
     * @param Result $result
     *
     * @return array{chat_id: int, photo: ?string, text: string, parse_mode: string}
     */
    public static function result(Result $result): array
    {
        $errorStatus = "❌ *Failed to process image*";

        if ($result->status === ResultStatus::FileIsTooBig) {
            $errorStatus = "❌ *Photo is too big*";
        }

        if ($result->status === ResultStatus::Unrecognized) {
            $errorStatus = "❌ *Failed to recognize meal*";
        }

        if ($result->status === ResultStatus::Processed) {
            $payload = $result->payload;

            if ($payload) {
                $title = data_get($payload, 'meal');
                $description = data_get($payload, 'description');
                $error = data_get($payload, 'error');

                if (!empty($error)) {
                    $error = static::escape($error);
                }

                $errorStatus = empty($error)
                    ? "✅ *Processed*"
                    : "❌ *$error*";

                $totalsFormatted = static::totals(data_get($payload, 'total'));
            }
        }

        $message = $errorStatus . "\n\n";

        if (!empty($title)) {
            $message .= "*Meal:* " . static::escape($title) . "\n";
        }

        if (!empty($description)) {
            $message .= "*Description:* " . static::escape($description) . "\n";
        }

        if (!empty($totalsFormatted)) {
            $message .= "\n*Totals:*\n" . $totalsFormatted . "\n";
        }

        return [
            'chat_id' => $result->message->chat_id,
            'photo' => $result->file?->file_id,
            'text' => $message,
            'parse_mode' => 'MarkdownV2',
        ];
    }

    /**
     * Compose a message about the meal estimation result.
     *
     * @param Meal $meal
     *
     * @return null|array{chat_id: int, text: string, parse_mode: string}
     */
    public static function meal(Meal $meal): ?array
    {
        $total = (array) data_get($meal->metadata, 'summary.total');

        $hasValues = false;

        foreach ($total as $value) {
            if (empty($value)) {
                continue;
            }

            $hasValues = true;
        }

        if (!$hasValues) {
            $message = "\n❌ *Summary:*\n\n"
                . static::escape("Failed to estimate calories (might be due to food not being recognized).");

            return [
                'chat_id' => $meal->chat->unique_id,
                'text' => $message,
                'parse_mode' => 'MarkdownV2',
            ];
        }

        $totalsFormatted = static::totals($total);

        if (!empty($totalsFormatted)) {
            $message = "\n✅ *Summary:*\n\n" . $totalsFormatted;

            return [
                'chat_id' => $meal->chat->unique_id,
                'text' => $message,
                'parse_mode' => 'MarkdownV2',
            ];
        }

        return null;
    }

    /**
     * Compose totals string.
     *
     * @param mixed $total
     *
     * @return string|null
     */
    protected static function totals(mixed $total): ?string
    {
        $totalsFormatted = "";

        $value = data_get($total, 'weight');
        if (!empty($value)) {
            $totalsFormatted .= "• *Weight:* " . static::escape(sprintf("%dg", $value)) . "\n";
        }

        $value = data_get($total, 'calories');
        if (!empty($value)) {
            $totalsFormatted .= "• *Calories:* " . static::escape(sprintf("%d kcal", $value)) . "\n";
        }

        $value = data_get($total, 'carbohydrates');
        if (!empty($value)) {
            $totalsFormatted .= "• *Carbohydrates:* " . static::escape(sprintf("%.2fg", $value)) . "\n";
        }

        $value = data_get($total, 'fiber');
        if (!empty($value)) {
            $totalsFormatted .= "• *Fiber:* " . static::escape(sprintf("%.2fg", $value)) . "\n";
        }

        $value = data_get($total, 'sugar');
        if (!empty($value)) {
            $totalsFormatted .= "• *Sugar:* " . static::escape(sprintf("%.2fg", $value)) . "\n";
        }

        $value = data_get($total, 'protein');
        if (!empty($value)) {
            $totalsFormatted .= "• *Protein:* " . static::escape(sprintf("%.2fg", $value)) . "\n";
        }

        $value = data_get($total, 'fat');
        if (!empty($value)) {
            $totalsFormatted .= "• *Fat:* " . static::escape(sprintf("%.2fg", $value)) . "\n";
        }

        return empty($totalsFormatted) ? null : $totalsFormatted;
    }
}
