<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notification appel de présence</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

                <!-- Header bande tricolore -->
                <tr>
                    <td style="height:6px;background:linear-gradient(90deg,#1a7a3c 33%,#f5c518 33% 66%,#c0392b 66%)"></td>
                </tr>

                <!-- Logo / titre -->
                <tr>
                    <td style="background:#0a3d20;padding:24px 32px;text-align:center;">
                        <span style="font-size:28px;font-weight:900;letter-spacing:1px;color:#f5c518;">Kalan</span><span style="font-size:28px;font-weight:900;letter-spacing:1px;color:#ffffff;">Net</span>
                        <p style="margin:4px 0 0;color:rgba(255,255,255,.65);font-size:12px;letter-spacing:.5px;">PLATEFORME DE GESTION SCOLAIRE</p>
                    </td>
                </tr>

                <!-- Badge alerte -->
                <tr>
                    <td style="padding:28px 32px 0;text-align:center;">
                        <span style="display:inline-block;background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:20px;padding:6px 18px;font-size:13px;font-weight:600;">
                            📢 Notification d'appel de présence
                        </span>
                    </td>
                </tr>

                <!-- Corps -->
                <tr>
                    <td style="padding:20px 32px 28px;">
                        <p style="margin:0 0 16px;color:#333;font-size:15px;line-height:1.6;">
                            Cher(e) parent/tuteur,
                        </p>
                        <p style="margin:0 0 20px;color:#333;font-size:15px;line-height:1.6;">
                            Nous vous informons que votre enfant <strong>{{ $prenomEleve }} {{ $nomEleve }}</strong>
                            a été marqué(e) comme :
                        </p>

                        <!-- Statut mis en valeur -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                            <tr>
                                <td style="background:#fef9e7;border-left:4px solid #f5c518;border-radius:0 8px 8px 0;padding:14px 20px;">
                                    <span style="font-size:17px;font-weight:700;color:#0a3d20;">{{ $statutControle }}</span>
                                </td>
                            </tr>
                        </table>

                        <!-- Détails -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa;border-radius:8px;padding:16px;margin-bottom:20px;">
                            <tr>
                                <td style="padding:4px 0;font-size:14px;color:#666;width:120px;">Appel :</td>
                                <td style="padding:4px 0;font-size:14px;color:#222;font-weight:600;">{{ $libelleAppel }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:14px;color:#666;">Date :</td>
                                <td style="padding:4px 0;font-size:14px;color:#222;font-weight:600;">{{ $dateAppel }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;font-size:14px;color:#666;">École :</td>
                                <td style="padding:4px 0;font-size:14px;color:#222;font-weight:600;">{{ $nomEcole }}</td>
                            </tr>
                        </table>

                        <p style="margin:0;color:#555;font-size:13px;line-height:1.6;">
                            Pour plus d'informations, connectez-vous à votre espace parent sur <strong>KalanNet</strong>
                            ou contactez directement l'administration de l'école.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f8f9fa;padding:16px 32px;border-top:1px solid #e9ecef;text-align:center;">
                        <p style="margin:0;font-size:12px;color:#888;">
                            Ce message est envoyé automatiquement par <strong style="color:#0a3d20;">KalanNet</strong>.
                            Merci de ne pas répondre directement à cet e-mail.
                        </p>
                    </td>
                </tr>

                <!-- Bande tricolore bas -->
                <tr>
                    <td style="height:5px;background:linear-gradient(90deg,#c0392b 33%,#f5c518 33% 66%,#1a7a3c 66%)"></td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
