<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A CMS-editable marketing page — see Plan.md's Phase 2. Content lives in
 * `sections` (typed, JSON `data`), each rendered by a matching Blade block
 * component in resources/views/components/blocks/*. See
 * App\Filament\Resources\PageResource for the editor and
 * App\Http\Controllers\PageController for public rendering.
 *
 * @property PageStatus $status
 */
class Page extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
        ];
    }

    /**
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections(): void
    {
        // Social-share / SEO image — one per page. Not used for per-section
        // block images (those use plain FileUpload for now — see Plan.md).
        $this->addMediaCollection('featured_image')->singleFile();
    }

    public function featuredImageUrl(): ?string
    {
        return $this->getFirstMedia('featured_image')?->getUrl();
    }
}
