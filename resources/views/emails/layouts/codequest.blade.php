<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#0b1020;font-family:Arial,Helvetica,sans-serif;color:#f8fafc;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0b1020;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding:8px 0 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" valign="middle" width="42" height="42"
                                        style="width:42px;height:42px;border-radius:12px;background-color:#8b5cf6;color:#ffffff;font-size:15px;font-weight:bold;">
                                        &lt;/&gt;
                                    </td>
                                    <td style="padding-left:12px;color:#f8fafc;font-size:22px;font-weight:bold;letter-spacing:-0.5px;">
                                        Code<span style="color:#22d3ee;">Quest</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 0 4px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                   style="border:1px solid #26324a;border-radius:18px;background-color:#151e35;">
                                <tr>
                                    <td style="padding:36px 32px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 12px 0;color:#8490a8;font-size:12px;line-height:20px;">
                            CodeQuest · Apprendre à programmer, mission après mission.
                            <br>
                            Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :
                            <br>
                            <a href="{{ $actionUrl }}" style="color:#22d3ee;word-break:break-all;">{{ $actionUrl }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
