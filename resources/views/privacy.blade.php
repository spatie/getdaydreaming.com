@extends('contentPage')

@section('title', 'Privacy')
@section('description', 'What Daydreaming keeps on your Mac and what it sends for wallpapers, feature requests and installation statistics.')
@section('canonical', route('privacy'))

@section('content')
    <div class="page-column">
        <h1 id="page-title">Privacy.</h1>
        <p class="page-lede">Daydreaming runs on your Mac without a Daydreaming account. Your picture, saved wallpapers and AI choices stay under your control.</p>
        <p class="page-updated">Last updated 7 October 2026</p>

        <article>
            <h2>Your pictures and key</h2>
            <p>Daydreaming keeps your original picture and saved wallpapers on your Mac. Your OpenAI API key is stored in macOS Keychain. For automatic wallpapers, the app sends a JPEG copy of your picture, your instructions, the time, weather and any connected text directly to OpenAI using your key.</p>
            <p>Create in Codex saves a PNG copy of your picture and your instructions in a local folder you choose, then opens a prepared chat in the Codex desktop app. You review and send that chat yourself. This website does not receive your pictures, instructions, connected text or keys through wallpaper creation.</p>
        </article>

        <article>
            <h2>Weather</h2>
            <p>Automatic weather uses your location with permission to request conditions from Apple Weather. If Apple Weather is unavailable, Daydreaming sends coordinates rounded to two decimal places to MET Norway. You can choose a fixed weather condition instead.</p>
        </article>

        <article>
            <h2>Feature requests</h2>
            <p>Help &gt; Submit a Prompt sends the feature request you type, your app version and build, and an optional name for public credit or email for a private reply. It does not automatically include your current wallpaper idea, pictures, logs, API key or installation token. If you include private details in the text yourself, we will receive them.</p>
            <p>We store the submitted text and fields with a random submission ID and reference so retries do not create duplicates. We keep requests to review and act on them. The submission database does not store your connection address; a temporary hash of it is kept in the rate-limit cache for up to an hour. To request deletion, email us with your reference.</p>
        </article>

        <article>
            <h2>Installation statistics</h2>
            <p>Sharing installation statistics is on by default and can be turned off in Settings. On first launch, after an app version changes and about once a day while you use the app, it can send a random installation token, your Mac's computer name, app version and build, macOS version, processor architecture and report time to getdaydreaming.com. It does not send your picture, ideas, OpenAI key or hardware identifier.</p>
            <p>Our server stores a one-way hash of the token and the latest computer name in the private admin area. We use reports to count installations, active use and version upgrades. This processing supports our legitimate interest in maintaining the app. A temporary hash of the connection address is kept in the rate-limit cache for up to an hour; the reporting database does not store IP addresses or locations.</p>
        </article>

        <article>
            <h2>How long we keep reports</h2>
            <p>Individual installation reports are deleted after 90 days. An installation’s latest version and activity summary is deleted after a year without a report.</p>
        </article>

        <article>
            <h2>Admin access</h2>
            <p>Google sign-in for the private admin area is limited to approved Spatie staff. We store an admin’s email address and Google account ID to keep access tied to that account. We do not store Google access or refresh tokens.</p>
        </article>

        <article>
            <h2>Updates and contact</h2>
            <p>Checking the signed update feed is separate from sharing installation statistics. You can turn off automatic update checks in Settings.</p>
            <p>For questions or a data request, email <a href="mailto:support@spatie.be?subject=Daydreaming%20privacy">support@spatie.be</a>. Daydreaming is made by Spatie in Antwerp, Belgium.</p>
        </article>
    </div>
@endsection
