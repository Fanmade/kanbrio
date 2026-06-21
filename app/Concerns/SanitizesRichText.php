<?php

namespace App\Concerns;

use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the model's rich-text (HTML) columns sanitized.
 *
 * Task/project descriptions and comment bodies are authored with the Flux editor
 * and may also be written directly through the MCP/API, which bypasses the
 * editor. Sanitizing on save (every write path) keeps stored HTML on an
 * allow-list so it is safe to return over the API and render. A model only
 * carries one of these columns; the missing one is simply skipped.
 *
 * @see RichTextSanitizer
 *
 * @phpstan-require-extends Model
 */
trait SanitizesRichText
{
    public static function bootSanitizesRichText(): void
    {
        static::saving(static function (Model $model): void {
            foreach (['description', 'body'] as $attribute) {
                $value = $model->getAttribute($attribute);

                if ($model->isDirty($attribute) && filled($value)) {
                    $model->setAttribute($attribute, app(RichTextSanitizer::class)->sanitize((string) $value));
                }
            }
        });
    }
}
