{{--
  Responsive article image with automatic srcset support.
  Uses the auto-generated thumbnail (media/thumbs/) when available.

  Required variables:
    $imgArticle  – Article model (must have featured_image set)
    $imgAlt      – Alt text string

  Optional variables:
    $imgClass       – CSS classes for the <img> (default: h-full w-full object-cover)
    $imgLoading     – 'lazy' (default) or 'eager'
    $imgPriority    – fetchpriority value, only applied when $imgLoading='eager' (default: 'high')
    $imgSizes       – sizes attribute value (default: mobile-first card sizes)
    $imgWidth       – width attribute (integer)
    $imgHeight      – height attribute (integer)
    $imgOnload      – onload JS expression (e.g. "this.previousElementSibling?.remove()")
--}}
@php
    $__src   = \Illuminate\Support\Facades\Storage::url($imgArticle->featured_image);
    $__thumb = $imgArticle->featured_image_thumb
        ? \Illuminate\Support\Facades\Storage::url($imgArticle->featured_image_thumb)
        : null;
    $__class    = $imgClass    ?? 'h-full w-full min-h-0 min-w-0 object-cover';
    $__loading  = $imgLoading  ?? 'lazy';
    $__sizes    = $imgSizes    ?? '(max-width: 640px) 100vw, (max-width: 1280px) 50vw, 420px';
    $__priority = $imgPriority ?? 'high';
@endphp
<img
    src="{{ $__src }}"
    @if($__thumb)
    srcset="{{ $__thumb }} {{ \App\Services\ImageOptimizerService::THUMB_WIDTH }}w, {{ $__src }} 1920w"
    sizes="{{ $__sizes }}"
    @endif
    alt="{{ $imgAlt }}"
    class="{{ $__class }}"
    loading="{{ $__loading }}"
    @if($__loading === 'eager') fetchpriority="{{ $__priority }}"@endif
    decoding="async"
    @isset($imgWidth) width="{{ $imgWidth }}"@endisset
    @isset($imgHeight) height="{{ $imgHeight }}"@endisset
    @isset($imgOnload) onload="{{ $imgOnload }}"@endisset
>
