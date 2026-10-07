<footer class="site-footer">
    <div class="footer-art" aria-hidden="true"><span class="footer-horizon"></span></div>
    <div class="footer-inner container">
        <div class="footer-main">
            <div class="footer-intro">
                <a class="brand footer-brand" href="{{ route('home') }}"><img src="{{ asset('daydreaming-icon.webp') }}" alt="" width="40" height="40"><span>Daydreaming</span></a>
            </div>
            <nav class="footer-nav" aria-label="Elsewhere">
                <a href="https://spatie.be">Spatie</a>
                <a href="https://github.com/spatie/daydreaming-app">Daydreaming on GitHub</a>
                <a href="https://freek.dev">Freek’s blog</a>
            </nav>
        </div>
        <nav class="footer-page-links" aria-label="Site pages">
            <a href="{{ route('changelog') }}">Changelog</a>
            <a href="{{ route('support') }}">Support</a>
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('credits') }}">Credits</a>
        </nav>
    </div>
</footer>
