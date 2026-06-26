<?php

namespace App\Models;

use Database\Factories\TagSynonymFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An alternative name a tag is also found by when searching — e.g. the tag
 * "Research" carrying the synonym "Evaluation". Synonyms only widen lookup;
 * they never appear as tags in their own right.
 *
 * @property int $id
 * @property int $tag_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tag_id', 'name'])]
class TagSynonym extends Model
{
    /** @use HasFactory<TagSynonymFactory> */
    use HasFactory;

    /**
     * The tag this synonym resolves to.
     *
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
