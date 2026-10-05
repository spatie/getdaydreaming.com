<svg viewBox="0 0 1440 1000" role="img" aria-labelledby="{{ $prefix }}-title" preserveAspectRatio="{{ $aspectRatio ?? 'xMidYMax slice' }}">
    <title id="{{ $prefix }}-title">{{ $title }}</title>
    <defs>
        <clipPath id="{{ $prefix }}-star-area">
            <rect class="star-area" y="180" width="1440" height="820" />
        </clipPath>
        <radialGradient id="{{ $prefix }}-glow">
            <stop stop-color="#ffe0a0" stop-opacity=".4" />
            <stop offset="1" stop-color="#ffe0a0" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="{{ $prefix }}-sky" x2="0" y2="1">
            <stop class="sky-top" />
            <stop class="sky-bottom" offset="1" />
        </linearGradient>
        <linearGradient id="{{ $prefix }}-water" x2="0" y2="1">
            <stop class="water-top" />
            <stop class="water-bottom" offset="1" />
        </linearGradient>
        <linearGradient id="{{ $prefix }}-reflection" x2="0" y2="1">
            <stop stop-color="#ffe1ad" stop-opacity=".25" />
            <stop offset="1" stop-color="#ffe1ad" stop-opacity="0" />
        </linearGradient>
    </defs>
    <rect class="sky-paint" width="1440" height="1000" fill="url(#{{ $prefix }}-sky)" />
    <g class="stars" clip-path="url(#{{ $prefix }}-star-area)" fill="#f6f4df">
        <circle cx="130" cy="122" r="1.5" /><circle cx="380" cy="92" r="2" /><circle cx="565" cy="195" r="1.5" /><circle cx="785" cy="55" r="2" /><circle cx="1090" cy="127" r="1.5" /><circle cx="1250" cy="255" r="2" /><circle cx="1350" cy="92" r="1.5" /><circle cx="890" cy="288" r="1.5" /><circle cx="290" cy="335" r="1.5" /><circle cx="75" cy="280" r="2" />
    </g>
    <circle class="sun-glow" cx="1290" cy="365" r="90" fill="url(#{{ $prefix }}-glow)" />
    <circle class="sun" cx="1290" cy="365" r="50" />
    <circle class="moon-glow" cx="1290" cy="450" r="90" fill="url(#{{ $prefix }}-glow)" />
    <circle class="moon" cx="1290" cy="450" r="24" fill="#eef0df" />
    <path class="mountain-back" d="M0 745 157 684 257 714 424 624 579 699 712 654 824 698 1027 612 1238 686 1343 654 1440 700V830H0Z" />
    <path class="snow-peaks" d="M394 640 424 624 463 643 445 642 432 633 420 639 412 633ZM990 628 1027 612 1067 633 1046 627 1033 625 1027 621 1017 625 1005 630Z" fill="#e3ebe7" />
    <path class="mountain-middle" d="M0 785 176 734 350 760 542 692 701 759 863 721 1049 764 1257 696 1440 758V830H0Z" />
    <path class="mountain-front" d="M0 766 156 750 304 768 465 721 663 770 970 753 1168 778 1440 760V830H0Z" />
    <path d="M0 751Q325 713 718 742T1440 751V1000H0Z" fill="url(#{{ $prefix }}-water)" />
    <g class="reflection" transform="translate(30 0)" fill="none" stroke="url(#{{ $prefix }}-reflection)" stroke-width="2" stroke-linecap="round">
        <path d="M1079 759h23M1110 772h14M1068 789h32M1097 807h19M1123 833h11M1057 849h29M1104 876h20M1073 916h37" />
    </g>
    <g class="water-lines" fill="none" stroke="#dce5df" stroke-opacity=".08">
        <path d="M740 782h18m-422 62h24m582 24h20M52 851h27m365 66h22" />
    </g>
    <path class="shore" d="M0 858q140-47 245-23l193 54 142 111H0Zm1440-82-126 62-60 162h186Z" />
    <g class="trees" fill="#172c33">
        <path d="m93 688-36 96h20l-28 53h32v42h24v-42h32l-27-53h20Zm-54 56-30 84h16L3 876h26v38h20v-38h28l-23-48h16Zm1233-60-40 116h24l-34 67h39v65h27v-65h40l-34-67h23Zm63 42-30 87h19l-25 51h30v48h23v-48h31l-26-51h19Z" />
    </g>
    <path class="mobile-bank shore" d="M420 865q90-52 180-7l90 142H390Z" />
    <g class="cabin">
        <path d="m200 797 43-34 55 34Z" fill="#162d35" />
        <path d="M210 797h80v48h-80Z" fill="#324a50" />
        <path d="M210 797h36v48h-36Z" fill="#415b5e" />
        <path d="M258 812h17v19h-17Z" fill="#1d343b" />
        <path class="cabin-light" d="M258 812h17v19h-17Z" fill="#ffe0a0" />
        <path d="M224 815h12v30h-12Z" fill="#1d343b" />
        <path d="M275 775h7v17h-7Z" fill="#243b40" />
    </g>
    <g class="rain" fill="none" stroke="#d5e0e5" stroke-width="1.5" stroke-opacity=".32">
        <path d="m150 330-20 48m170-10-20 48m190-165-20 48m190 8-20 48m170-120-20 48m230 4-20 48m145 0-20 48m135-165-20 48m160 23-20 48m140 8-20 48m-955 170-20 48m190-70-20 48m190 48-20 48m170-110-20 48m180 6-20 48m195 15-20 48" />
        <ellipse cx="498" cy="838" rx="15" ry="3" /><ellipse cx="718" cy="884" rx="21" ry="3" /><ellipse cx="1145" cy="832" rx="18" ry="3" />
    </g>
    <g class="snowfall" fill="#f2f5f4" opacity="0">
        <circle cx="180" cy="350" r="2" /><circle cx="360" cy="480" r="3" /><circle cx="510" cy="410" r="2" /><circle cx="635" cy="570" r="2" /><circle cx="780" cy="385" r="3" /><circle cx="956" cy="480" r="2" /><circle cx="1130" cy="420" r="3" /><circle cx="1260" cy="590" r="2" /><circle cx="400" cy="700" r="2" /><circle cx="885" cy="740" r="3" />
    </g>
    <rect class="fog" y="480" width="1440" height="350" fill="#c5d1d2" opacity="0" />
</svg>
