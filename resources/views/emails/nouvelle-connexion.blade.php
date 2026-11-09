<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouvelle connexion détectée</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr><td style="height:6px;background:#1a7a3c;"></td></tr>
                <tr>
                    <td style="background:#0a3d20;padding:22px 32px;text-align:center;">
                        <span style="font-size:26px;font-weight:900;letter-spacing:1px;color:#f5c518;">Kalan</span><span style="font-size:26px;font-weight:900;letter-spacing:1px;color:#ffffff;">Net</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 32px 8px;">
                        <h1 style="margin:0 0 14px;font-size:20px;color:#0a3d20;">Nouvelle connexion détectée</h1>
                        <p style="margin:0 0 14px;color:#333;font-size:15px;line-height:1.6;">Bonjour {{ $nom }},</p>
                        <p style="margin:0 0 18px;color:#333;font-size:15px;line-height:1.6;">Une connexion depuis un nouvel appareil a été détectée sur votre compte KalanNet.</p>
                        <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e8ec;border-radius:8px;font-size:14px;color:#333;">
                            <tr><td style="padding:10px 14px;color:#667;width:110px;border-bottom:1px solid #e3e8ec;">Date</td><td style="padding:10px 14px;font-weight:600;border-bottom:1px solid #e3e8ec;">{{ $date }}</td></tr>
                            <tr><td style="padding:10px 14px;color:#667;border-bottom:1px solid #e3e8ec;">Adresse IP</td><td style="padding:10px 14px;font-weight:600;border-bottom:1px solid #e3e8ec;">{{ $ip }}</td></tr>
                            <tr><td style="padding:10px 14px;color:#667;">Appareil</td><td style="padding:10px 14px;font-weight:600;">{{ $appareil }}<div style="font-weight:400;color:#889;font-size:12px;margin-top:4px;word-break:break-all;">{{ $userAgent }}</div></td></tr>
                        </table>
                        <p style="margin:20px 0 6px;color:#333;font-size:15px;line-height:1.6;"><strong>C'était vous ?</strong> Aucune action n'est requise.</p>
                        <p style="margin:0 0 20px;color:#333;font-size:15px;line-height:1.6;"><strong>Ce n'était pas vous ?</strong> Changez votre mot de passe immédiatement, puis prévenez l'administrateur de votre établissement.</p>
                        <p style="margin:0 0 24px;text-align:center;">
                            <a href="{{ $lienMotDePasse }}" style="display:inline-block;background:#1a7a3c;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 24px;border-radius:8px;">Changer mon mot de passe</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px 24px;color:#889;font-size:12px;line-height:1.5;border-top:1px solid #eef1f4;">
                        Cet e-mail est envoyé automatiquement pour protéger votre compte. KalanNet ne vous demandera jamais votre mot de passe par e-mail ou par téléphone.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
