<x-layouts.app
    :title="$page->meta_title ?: $page->title"
    :description="$page->meta_description"
    :image="$page->featuredImageUrl()"
>
    @foreach ($page->sections as $section)
        <x-dynamic-component :component="'blocks.'.$section->type" :data="$section->data" />
    @endforeach
</x-layouts.app>
