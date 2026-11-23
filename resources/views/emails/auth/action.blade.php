@extends('emails.layouts.codequest')

@section('content')
    <p style="margin:0 0 12px;color:#22d3ee;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">
        {{ $eyebrow }}
    </p>
    <h1 style="margin:0 0 20px;color:#f8fafc;font-size:26px;line-height:34px;">
        {{ $title }}
    </h1>
    <p style="margin:0 0 14px;color:#e2e8f0;font-size:16px;line-height:26px;">
        {{ $greeting }}
    </p>
    <p style="margin:0 0 26px;color:#cbd5e1;font-size:15px;line-height:25px;">
        {{ $intro }}
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td align="center" style="border-radius:10px;background-color:#8b5cf6;">
                <a href="{{ $actionUrl }}"
                   style="display:inline-block;padding:14px 22px;border:1px solid #8b5cf6;border-radius:10px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">
                    {{ $actionLabel }}
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 12px;color:#94a3b8;font-size:13px;line-height:22px;">
        {{ $expiration }}
    </p>
    <p style="margin:0;color:#94a3b8;font-size:13px;line-height:22px;">
        {{ $outro }}
    </p>
@endsection
