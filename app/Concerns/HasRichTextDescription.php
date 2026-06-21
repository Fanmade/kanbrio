<?php

namespace App\Concerns;

use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the model's `description` as sanitized rich-text (HTML).
 *
 * Descriptions are authored with the Flux editor and may also be written
 * directly through the MCP/API, which bypasses the editor. Sanitizing on save
 * (every write path) keeps stored HTML on an allow-list so it is safe to return
 * over the API and render. {@see RichTextSanitizer}
 *
 * @phpstan-require-extends Model
 */
trait HasRichTextDescription
{
    public static function bootHasRichTextDescription(): void
    {
        static::saving(static function (Model $model): void {
            $description = $model->getAttribute('description');

            if ($model->isDirty('description') && filled($description)) {
                $model->setAttribute('description', app(RichTextSanitizer::class)->sanitize((string) $description));
            }
        });
    }
}
