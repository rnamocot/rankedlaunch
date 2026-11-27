@php
    $bg = $theme === 'dark' ? '#1d1d1f' : '#ffffff';
    $fg = $theme === 'dark' ? '#ffffff' : '#1d1d1f';
    $sub = $theme === 'dark' ? '#a1a1a6' : '#6e6e73';
    $stroke = $theme === 'dark' ? '#3a3a3c' : '#d2d2d7';
    $inv = $theme === 'dark' ? '#1d1d1f' : '#ffffff';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="220" height="54" viewBox="0 0 220 54" role="img" aria-label="{{ $eyebrow }} {{ $title }}">
    <rect x="0.5" y="0.5" width="219" height="53" rx="11.5" fill="{{ $bg }}" stroke="{{ $stroke }}"/>
    <g transform="translate(14 13)">
        <rect width="28" height="28" rx="7.5" fill="{{ $fg }}"/>
        <rect x="5.9" y="13.6" width="4.8" height="8.3" rx="1.1" fill="{{ $inv }}" opacity=".55"/>
        <rect x="11.6" y="7" width="4.8" height="14.9" rx="1.1" fill="{{ $inv }}"/>
        <rect x="17.3" y="10.5" width="4.8" height="11.4" rx="1.1" fill="{{ $inv }}" opacity=".78"/>
    </g>
    <text x="54" y="23" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, Helvetica, Arial, sans-serif" font-size="9" font-weight="600" letter-spacing=".6" fill="{{ $sub }}">{{ $eyebrow }}</text>
    <text x="54" y="39" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, Helvetica, Arial, sans-serif" font-size="15" font-weight="700" letter-spacing="-.3" fill="{{ $fg }}">{{ $title }}</text>
</svg>
