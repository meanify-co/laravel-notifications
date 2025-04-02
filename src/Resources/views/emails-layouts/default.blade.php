<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Imprima&display=swap" rel="stylesheet">

    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { -ms-interpolation-mode:bicubic; }

        h1, h2, h3, h4, h5, h6, p, a {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        h1, h2, h3, h4, h5, h6, p {
            margin: 0;
            line-height: 1.5;
            color: #2D3142;
        }

        p.small { font-size: 12px; }
        p.normal { font-size: 14px; }
        p.large { font-size: 16px; }
        h3.title { font-size: 20px; font-weight: 600; }

        .heavy{ font-weight: 800; font-size: 20px !important}

        a {
            color: inherit;
            text-decoration: none;
        }

        a.anchor{
            text-decoration: underline;
        }

        .btn {
            display: block;
            padding: 12px 24px;
            background-color: #0eafa2;
            color: #ffffff !important;
            border-radius: 30px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            width: fit-content;
            margin: 0 auto;
        }

        .wrapper {
            width: 100%;
            background-color: #ffffff;
            padding: 0;
            margin: 0;
        }

        .content-wrapper {
            width: 600px;
            background-color: #f6f6f6;
            border-radius: 30px;
            padding: 20px 20px;
        }

        .box {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 30px 20px;
            margin: 0 40px;
        }

        .footer-links a {
            color: #2D3142;
            font-size: 12px;
            text-decoration: none;
            margin: 0 5px;
        }

        .social-icons td {
            padding: 0 5px;
        }

        @media only screen and (max-width: 600px) {
            .content-wrapper { width: 100% !important; border-radius: 0 !important; }
            h3.title { font-size: 24px !important; }
            p.normal, p.small, p.large { font-size: 14px !important; }
        }
    </style>
</head>
<body class="wrapper">

<table role="presentation" align="center"  cellspacing="0" cellpadding="0">
    <!-- START: HEADER -->
    <tr>
        <td align="center" style="padding: 40px 0 20px 0;">
            <img src="{{ $logo_url ?? 'https://mfy-open.s3.us-east-1.amazonaws.com/live/assets/email/meanify.png' }}" alt="Logo" width="{{ $logo_width ?? '136' }}" height="{{ $logo_height ?? '60' }}" style="display:block;">
        </td>
    </tr>
</table>


<table role="presentation" align="center" class="content-wrapper" cellspacing="0" cellpadding="0">
    <!-- START: BODY -->
    <tr>
        <td>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="box">

                        @if(isset($title))
                            <h3 class="title" style="text-align: center;">
                                {!! $title !!}
                            </h3>
                        @endif

                        @if(isset($body))

                            <p class="large" style="text-align: center; margin-top: 30px;">
                                {!! $body !!}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    @if(isset($cta_link))
        <tr>
            <td align="center" style="padding: 30px 40px 10px 40px;">
                <a href="{{ $cta_link ?? '#' }}" class="btn" style="margin-bottom: 40px">
                    {{ $cta_button ?? 'Button' }}
                </a>

                <p class="small" style="text-align: center; margin-top: 15px;">
                    @if (!empty($cta_link) && !empty($short_cta))
                        <a href="{{ $cta_link }}" class="anchor">{{ $short_cta }}</a>
                    @endif
                    {{ $cta_help ?? '' }}
                </p>
            </td>
        </tr>
    @endif

    @if(isset($help_text))
        <!-- START: HELP TEXT -->
        <tr>
            <td align="center" style="padding: 20px 40px 0px 40px;">
                <p class="small" style="text-align: center; margin-bottom: 20px;">
                    {{ $help_text ?? '' }}
                </p>
            </td>
        </tr>
    @endif

</table>




<table role="presentation" align="center"  cellspacing="0" cellpadding="0">
    <!-- START: FOOTER -->
    <tr>
        <td align="center" style="padding: 30px 40px 40px 40px;">
            @if (!empty($social_links))
                @php
                    $icons = [
                        'facebook' => 'facebook.png',
                        'linkedin' => 'linkedin.png',
                        'instagram' => 'instagram.png',
                        'youtube' => 'youtube.png',
                    ];
                @endphp

                <table role="presentation" class="social-icons" cellspacing="0" cellpadding="0">
                    <tr>
                        @foreach (['facebook' => 'facebook.png', 'linkedin' => 'linkedin.png', 'instagram' => 'instagram.png', 'youtube' => 'youtube.png'] as $platform => $icon)
                            @if (!empty($social_links[$platform]))
                                <td style="padding-right: 8px;">
                                    <a href="{{ $social_links[$platform] }}">
                                        <img src="https://mfy-open.s3.us-east-1.amazonaws.com/live/assets/email/{{ $icon }}" alt="{{ ucfirst($platform) }}" width="24" height="24">
                                    </a>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                </table>
            @endif

            <p class="small" style="margin-top: 20px;">
                @if (!empty($privacy_url))
                    <a href="{{ $privacy_url }}">{{$privacy_text ?? 'Privacy Policy'}}</a>
                @endif

                @if (!empty($unsubscribe_url))
                    @if (!empty($privacy_url)) • @endif
                    <a href="{{ $unsubscribe_url }}">{{$unsubscribe_text ?? 'Unsubscribe'}}</a>
                @endif
            </p>

            <p class="normal" style="margin-top: 30px;">&copy; {{now()->format('Y')}} Meanify</p>
        </td>
    </tr>
</table>

</body>
</html>
