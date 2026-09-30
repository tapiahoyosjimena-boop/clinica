<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sus resultados están disponibles — Clínica Norte</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111; font-size: 14px; padding: 0; margin: 0; background: #f9fafb;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width: 580px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.07);">
    <tr>
        <td style="background: #0d9488; padding: 24px 32px;">
            <h1 style="margin: 0; color: #fff; font-size: 20px; font-weight: bold;">Clínica Norte</h1>
            <p style="margin: 4px 0 0; color: #ccfbf1; font-size: 13px;">Sistema de Laboratorio e Imagen</p>
        </td>
    </tr>
    <tr>
        <td style="padding: 32px;">
            <p style="margin: 0 0 12px;">Estimado/a <strong>{{ $patientName }}</strong>,</p>
            <p style="margin: 0 0 12px;">
                Le informamos que el resultado de su examen
                <strong>{{ $examName }}</strong> ya está disponible en nuestro portal.
            </p>

            @if($isCritical)
                <div style="background: #fee2e2; color: #b91c1c; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
                    ⚠ <strong>Atención:</strong> Su resultado ha sido clasificado como <strong>crítico</strong>.
                    Por favor comuníquese con su médico a la brevedad posible.
                </div>
            @endif

            <p style="margin: 0 0 20px;">
                Puede descargar su PDF completo ingresando al portal del paciente con el siguiente enlace:
            </p>

            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="background: #0d9488; border-radius: 6px; padding: 12px 24px;">
                        <a href="{{ $portalUrl }}"
                           style="color: #fff; text-decoration: none; font-weight: bold; font-size: 14px;">
                            Ver mis resultados →
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin: 24px 0 0; font-size: 12px; color: #6b7280;">
                Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
                <a href="{{ $portalUrl }}" style="color: #0d9488;">{{ $portalUrl }}</a>
            </p>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
            <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                Clínica Norte — Este es un mensaje automático, por favor no responda directamente a este correo.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
