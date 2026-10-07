@extends('contentPage')

@section('title', 'Changelog')
@section('description', 'Daydreaming for Mac release notes, newest first.')
@section('canonical', route('changelog'))

@section('content')
    <div class="page-column">
        <h1 id="page-title">Changelog.</h1>
        <p class="page-lede">New features, improvements and fixes, newest first. Daydreaming checks for signed updates when you enable automatic updates.</p>

        @forelse($releases as $release)
            <article class="changelog-entry" id="version-{{ str_replace('.', '-', $release['version']) }}-{{ $release['build'] }}">
                <div class="changelog-meta">
                    <strong>Version {{ $release['version'] }}</strong>
                    @if($release['publishedAt'])
                        <time datetime="{{ $release['publishedAt']->toDateString() }}">{{ $release['publishedAt']->format('j F Y') }}</time>
                    @endif
                </div>
                <h2>{{ $release['title'] }}</h2>
                @if($release['notes'])
                    <p class="changelog-notes">{{ $release['notes'] }}</p>
                @endif
            </article>
        @empty
            <div class="content-empty">
                <h2>No public releases yet.</h2>
                <p>Release notes will appear here when the first download is available.</p>
            </div>
        @endforelse
    </div>
@endsection
