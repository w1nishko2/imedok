{{-- SEO Meta Tags Component --}}

{{-- Title --}}
<title>{{ $seo['title'] ?? config('app.name') }}</title>

{{-- Meta Description --}}
@if(isset($seo['description']))
<meta name="description" content="{{ $seo['description'] }}">
@endif

{{-- Meta Keywords --}}
@if(isset($seo['keywords']))
<meta name="keywords" content="{{ $seo['keywords'] }}">
@endif

{{-- Canonical URL --}}
@if(isset($seo['canonical']))
<link rel="canonical" href="{{ $seo['canonical'] }}">
@endif

{{-- Robots --}}
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow">

{{-- Author & Copyright --}}
<meta name="author" content="{{ config('app.name') }}">
<meta name="copyright" content="© {{ date('Y') }} {{ config('app.name') }}">
<meta name="publisher" content="{{ config('app.name') }}">

{{-- Language --}}
<meta http-equiv="content-language" content="ru">
<meta name="language" content="Russian">

{{-- Open Graph Meta Tags --}}
@if(isset($seo['og']))
<meta property="og:title" content="{{ $seo['og']['title'] }}">
<meta property="og:description" content="{{ $seo['og']['description'] }}">
<meta property="og:url" content="{{ $seo['og']['url'] }}">
<meta property="og:type" content="{{ $seo['og']['type'] }}">
<meta property="og:site_name" content="{{ $seo['og']['site_name'] }}">
<meta property="og:locale" content="{{ $seo['og']['locale'] }}">

@if(isset($seo['og']['image']))
<meta property="og:image" content="{{ $seo['og']['image'] }}">
<meta property="og:image:width" content="{{ $seo['og']['image:width'] ?? 1200 }}">
<meta property="og:image:height" content="{{ $seo['og']['image:height'] ?? 630 }}">
<meta property="og:image:alt" content="{{ $seo['og']['image:alt'] ?? $seo['og']['title'] }}">
@endif

@if(isset($seo['og']['article:published_time']))
<meta property="article:published_time" content="{{ $seo['og']['article:published_time'] }}">
<meta property="article:modified_time" content="{{ $seo['og']['article:modified_time'] }}">
<meta property="article:section" content="{{ $seo['og']['article:section'] }}">
<meta property="article:tag" content="{{ $seo['og']['article:tag'] }}">
@endif
@endif

{{-- Twitter Card Meta Tags --}}
@if(isset($seo['twitter']))
<meta name="twitter:card" content="{{ $seo['twitter']['card'] }}">
<meta name="twitter:title" content="{{ $seo['twitter']['title'] }}">
<meta name="twitter:description" content="{{ $seo['twitter']['description'] }}">

@if(isset($seo['twitter']['image']))
<meta name="twitter:image" content="{{ $seo['twitter']['image'] }}">
<meta name="twitter:image:alt" content="{{ $seo['twitter']['image:alt'] ?? $seo['twitter']['title'] }}">
@endif
@endif

{{-- Favicon --}}
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/manifest.json">

{{-- Schema.org JSON-LD --}}
@if(isset($seo['schema']))
<script type="application/ld+json">
{!! json_encode($seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- Breadcrumbs Schema --}}
@if(isset($seo['breadcrumbs']))
<script type="application/ld+json">
{!! json_encode($seo['breadcrumbs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- Additional Schema (ItemList for homepage) --}}
@if(isset($itemListSchema))
<script type="application/ld+json">
{!! json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

{{-- Mobile Optimization --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, minimum-scale=1.0">
<meta name="theme-color" content="#ffffff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

{{-- DNS Prefetch & Preconnect --}}
<link rel="dns-prefetch" href="//fonts.bunny.net">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
