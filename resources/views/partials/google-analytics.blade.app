{{-- Google Analytics tracking code --}}
@if(config('analytics.google_measurement_id'))
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('analytics.google_measurement_id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ config('analytics.google_measurement_id') }}');
</script>
<!-- End Google Analytics -->
@endif