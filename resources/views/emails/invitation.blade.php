<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>You're invited to Fiesta Field Guide</title>
</head>
<body style="margin:0; padding:0; background-color:#FDF8ED;">
    {{-- Shown as the preview line in most inboxes. --}}
    <div style="display:none; max-height:0; overflow:hidden;">
        {{ $adminName }} set a place for you. Come catalog your Fiesta.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FDF8ED;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <img src="{{ $logoUrl }}" width="56" height="56" alt="" style="display:block; border:0;">
                            <p style="margin:8px 0 0; font-family:Georgia, 'Times New Roman', serif; font-size:22px; font-weight:bold; color:#1A1816;">
                                Fiesta <span style="color:#F24B21;">Field Guide</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#FFFFFF; border:2px solid #F1E7D3; border-radius:24px; padding:32px 28px;">
                            <p style="margin:0 0 6px; font-family:Helvetica, Arial, sans-serif; font-size:13px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; color:#00A3A8;">
                                You're invited
                            </p>
                            <h1 style="margin:0 0 16px; font-family:Georgia, 'Times New Roman', serif; font-size:28px; line-height:1.2; color:#1A1816;">
                                {{ $adminName }} set a place for you at the table.
                            </h1>
                            <p style="margin:0 0 16px; font-family:Helvetica, Arial, sans-serif; font-size:16px; line-height:1.55; color:#3A3530;">
                                Fiesta Field Guide is a little app for Fiesta dinnerware lovers. Look up any color and
                                see what came in it, keep track of the pieces in your own cupboard, and make a wishlist
                                of the grails you are still hunting.
                            </p>
                            <p style="margin:0 0 24px; font-family:Helvetica, Arial, sans-serif; font-size:16px; line-height:1.55; color:#3A3530;">
                                As a friend of {{ $adminName }}'s, you can also peek at their collection. No judging
                                the number of Sunflower mugs.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="border-radius:999px; background-color:#1A1816;">
                                        <a href="{{ $link }}" style="display:inline-block; padding:14px 32px; font-family:Helvetica, Arial, sans-serif; font-size:16px; font-weight:bold; color:#FDF8ED; text-decoration:none; border-radius:999px;">
                                            Pull up a chair
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; font-family:Helvetica, Arial, sans-serif; font-size:13px; line-height:1.5; color:#7A716A; text-align:center;">
                                The link works once and is good for {{ $daysValid }} days.<br>
                                If the button does not work, paste this into your browser:<br>
                                <a href="{{ $link }}" style="color:#00A3A8; word-break:break-all;">{{ $link }}</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:28px 0 8px;">
                            <img src="{{ $platesUrl }}" width="600" alt="A row of Fiesta plates in Poppy, Sunflower, Meadow, Turquoise, Lapis and Peony" style="display:block; width:100%; max-width:600px; height:auto; border:0;">
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:8px 16px 0;">
                            <p style="margin:0; font-family:Helvetica, Arial, sans-serif; font-size:12px; line-height:1.5; color:#9A918A;">
                                You got this because {{ $adminName }} typed your address into Fiesta Field Guide.
                                If it is not for you, just ignore it and the link will expire on its own.<br>
                                Not affiliated with Fiesta Tableware Company or Homer Laughlin.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
