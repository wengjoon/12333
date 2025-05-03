{{-- SEO Meta Tags, centralized partial for inclusion in layout --}}

{{-- Standard Meta Tags --}}
<title>
    @yield('seo_title', config('seo.default_title'))
</title>
<meta name="description" content="@yield('seo_description', config('seo.default_description'))">
<meta name="keywords" content="@yield('seo_keywords', config('seo.default_keywords'))">
<link rel="canonical" href="@yield('canonical_url', url()->current())">

{{-- Open Graph Tags (for social media) --}}
<meta property="og:title" content="@yield('seo_title', config('seo.default_title'))">
<meta property="og:description" content="@yield('seo_description', config('seo.default_description'))">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="@yield('og_image', asset(config('seo.default_og_image')))">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">

{{-- Twitter Card Tags --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="@yield('seo_title', config('seo.default_title'))">
<meta name="twitter:description" content="@yield('seo_description', config('seo.default_description'))">
<meta name="twitter:image" content="@yield('twitter_image', asset(config('seo.default_twitter_image')))">