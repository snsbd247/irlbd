<?php

namespace App\Services;

use App\Http\Resources\CertificationResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\FaqResource;
use App\Http\Resources\HeroSlideResource;
use App\Http\Resources\MilestoneResource;
use App\Http\Resources\NewsPostResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\TeamMemberResource;
use App\Models\Certification;
use App\Models\Client;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Milestone;
use App\Models\NewsPost;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Support\Collection;

/**
 * Attaches public data from other modules to sections whose type declares a "source".
 * Only active/published/verified records are ever included.
 */
class SectionHydrator
{
    /** @param Collection<int, PageSection> $sections */
    public function hydrate(Collection $sections): Collection
    {
        $types = config('sections');

        return $sections->each(function (PageSection $section) use ($types) {
            $source = $types[$section->type]['source'] ?? null;
            if ($source) {
                $section->hydrated = $this->load($source, $section->settings ?? []);
            }
        });
    }

    protected function load(string $source, array $settings): mixed
    {
        $limit = (int) ($settings['limit'] ?? 6);

        return match ($source) {
            'hero_slides' => HeroSlideResource::collection(HeroSlide::active()->ordered()->get()),
            'services' => ServiceResource::collection(Service::active()->ordered()->limit($limit)->get()),
            'product_categories' => ProductCategoryResource::collection(
                ProductCategory::active()->ordered()
                    ->withCount(['products' => fn ($q) => $q->published()])
                    ->get()
            ),
            'featured_products' => ProductResource::collection(
                Product::visible()->where('is_featured', true)->with('category')->ordered()->limit($limit)->get()
            ),
            'clients' => ClientResource::collection(Client::active()->ordered()->get()),
            'latest_news' => NewsPostResource::collection(
                NewsPost::published()->with('category')->latest('published_at')->limit($limit ?: 3)->get()
            ),
            'founder' => ($founder = TeamMember::active()->where('is_founder', true)->ordered()->first())
                ? new TeamMemberResource($founder) : null,
            'milestones' => MilestoneResource::collection(Milestone::active()->ordered()->get()),
            'certifications' => CertificationResource::collection(
                Certification::active()->where('is_verified', true)->ordered()->get()
            ),
            'faqs' => FaqResource::collection(Faq::active()->ordered()->get()),
            default => null,
        };
    }
}
