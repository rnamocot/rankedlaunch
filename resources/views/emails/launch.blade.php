<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#fafafa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Helvetica,Arial,sans-serif;color:#171717;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fafafa;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
                    {{-- Brand --}}
                    <tr>
                        <td style="padding:0 4px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td style="width:26px;height:26px;background:#171717;border-radius:7px;text-align:center;vertical-align:bottom;font-size:0;line-height:0;padding:0 0 5px;">
                                    <span style="display:inline-block;width:4px;height:8px;background:#8a8a8a;border-radius:1px;margin:0 1px;"></span><span style="display:inline-block;width:4px;height:14px;background:#ffffff;border-radius:1px;margin:0 1px;"></span><span style="display:inline-block;width:4px;height:11px;background:#c8c8c8;border-radius:1px;margin:0 1px;"></span>
                                </td>
                                <td style="padding-left:9px;font-size:15px;font-weight:600;letter-spacing:-0.3px;">RankedLaunch</td>
                            </tr></table>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td style="background:#ffffff;border:1px solid #ebebec;border-radius:18px;padding:32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr>
                                <td><img src="{{ $product->logoUrl() }}" width="44" height="44" alt="" style="display:block;border-radius:10px;border:1px solid #ebebec;"></td>
                                <td style="padding-left:12px;">
                                    <div style="font-size:15px;font-weight:600;">{{ $product->name }}</div>
                                    <div style="font-size:13px;color:#6b6b70;">{{ $product->tagline }}</div>
                                </td>
                            </tr></table>

                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.2;font-weight:700;letter-spacing:-0.6px;">{{ $heading }}</h1>

                            @foreach ($lines as $line)
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#3a3a3d;">{{ $line }}</p>
                            @endforeach

                            @isset($list)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:4px 0 16px;">
                                    @foreach ($list as $item)
                                        <tr>
                                            <td style="vertical-align:top;padding:4px 10px 4px 0;font-size:15px;color:#a3a3a8;">&#9744;</td>
                                            <td style="padding:4px 0;font-size:15px;line-height:1.5;color:#3a3a3d;">{{ $item }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endisset

                            @isset($stats)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 20px;background:#f3f3f4;border-radius:14px;">
                                    <tr>
                                        @foreach ($stats as $label => $value)
                                            <td align="center" style="padding:16px 4px;">
                                                <div style="font-size:22px;font-weight:700;letter-spacing:-0.5px;">{{ $value }}</div>
                                                <div style="font-size:12px;color:#6b6b70;margin-top:2px;">{{ $label }}</div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            @endisset

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:8px;"><tr>
                                <td style="background:#171717;border-radius:10px;">
                                    <a href="{{ $cta[1] }}" style="display:inline-block;padding:12px 20px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">{{ $cta[0] }} &rarr;</a>
                                </td>
                            </tr></table>

                            @isset($secondary)
                                <p style="margin:16px 0 0;font-size:13px;"><a href="{{ $secondary[1] }}" style="color:#171717;">{{ $secondary[0] }}</a></p>
                            @endisset
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 4px 0;font-size:12px;line-height:1.6;color:#a3a3a8;">
                            You’re getting this because you launched {{ $product->name }} on RankedLaunch.<br>
                            <a href="{{ route('home') }}" style="color:#a3a3a8;">rankedlaunch.com</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
