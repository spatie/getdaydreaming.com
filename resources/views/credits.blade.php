@extends('contentPage')

@section('title', 'Credits')
@section('description', 'Weather and original photo credits for Daydreaming.')
@section('canonical', route('credits'))

@section('content')
    <div class="page-column">
        <h1 id="page-title">Credits.</h1>
        <p class="page-lede">The places and people behind the scenery.</p>

        <article>
            <h2>Weather data</h2>
            <p>Weather data from <a href="https://www.met.no/en">MET Norway</a>, licensed under <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>.</p>
        </article>

        @foreach($photoCredits as $credit)
            <article>
                <h2>{{ $credit['picture'] }}</h2>
                <p>Original photograph by <a href="{{ $credit['source'] }}">{{ $credit['author'] }}</a>, licensed as <a href="{{ $credit['licenseUrl'] }}">{{ $credit['license'] }}</a>.</p>
                <p class="credit-date">Source downloaded <time datetime="{{ $credit['downloaded'] }}">{{ $credit['downloaded'] }}</time>.</p>
            </article>
        @endforeach

        <p class="example-disclosure">Weather data and original photographs used for the Daydreaming website examples. Example edits made for this website with an AI image model.</p>
    </div>
@endsection
