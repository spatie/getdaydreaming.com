@extends('contentPage')

@section('title', 'Support')
@section('description', 'Get help with Daydreaming for Mac.')
@section('canonical', route('support'))

@section('content')
    <div class="page-column">
        <h1 id="page-title">Support.</h1>
        <p class="page-lede">Questions, bugs and ideas are welcome. The people building Daydreaming read your messages.</p>

        <article>
            <h2>Email us</h2>
            <p>Write to <a href="mailto:support@spatie.be?subject=Daydreaming%20support">support@spatie.be</a>. If something broke, tell us what you expected, what happened, your Daydreaming version and your macOS version. Please leave your OpenAI API key out of the message.</p>
        </article>

        <article>
            <h2>Suggest a feature</h2>
            <p>In the app, choose Help &gt; Submit a Prompt. Tell us what you would like Daydreaming to do. You can add a name for public credit and an email address if you want a private reply. The app sends only what you enter in that form, plus its version and build. It does not attach your current wallpaper idea, pictures, logs or API key.</p>
        </article>

        <article>
            <h2>AI questions</h2>
            <p>Automatic wallpapers use your own OpenAI API key, and OpenAI bills your account for new images. Create in Codex opens a prepared chat for you to review and send in the Codex desktop app. For account access or billing, contact the provider. For Daydreaming’s AI handoff or saved wallpapers, contact us.</p>
        </article>

        <article>
            <h2>Updates</h2>
            <p>When public releases are available, you can find their changes in the <a href="{{ route('changelog') }}">changelog</a>. The app can also check for signed updates automatically.</p>
        </article>

        <article id="postcardware">
            <h2>Postcardware</h2>
            <p>Daydreaming is free. If you enjoy using it, you are welcome to send us a postcard from where you live. It is entirely optional. We put postcards on <a href="https://spatie.be/open-source/postcards">our wall</a>.</p>
            <address class="postcard-address">Spatie<br>Kruikstraat 22, Box 12<br>2018 Antwerp<br>Belgium</address>
        </article>
    </div>
@endsection
