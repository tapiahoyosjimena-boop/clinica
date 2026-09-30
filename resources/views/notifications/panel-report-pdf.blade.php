<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }} — Clínica Norte</title>
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
            <p style="margin: 0 0 12px;">Estimado equipo,</p>
            <p style="margin: 0 0 16px;">
                Se adjunta el reporte <strong>{{ $reportTitle }}</strong> solicitado desde el panel administrativo.
                {{ $reportDescription }}
            </p>

            <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #0f766e;">
                    <strong>Período consultado:</strong> {{ $periodLabel }}
                </p>
                @if($rowCount !== null)
                    <p style="margin: 0 0 8px; font-size: 13px; color: #0f766e;">
                        <strong>Registros incluidos:</strong> {{ number_format($rowCount, 0, ',', '.') }}
                    </p>
                @endif
                @if(filled($generatedByName))
                    <p style="margin: 0; font-size: 13px; color: #0f766e;">
                        <strong>Generado por:</strong> {{ $generatedByName }}
                    </p>
                @endif
            </div>

            <p style="margin: 0 0 8px;">
                El documento PDF <strong>{{ $attachmentFilename }}</strong> contiene el detalle completo según los filtros aplicados al generar el reporte.
            </p>
            <p style="margin: 0; font-size: 13px; color: #4b5563;">
                Si no visualiza el adjunto, revise la carpeta de spam o solicite un nuevo envío desde el módulo <em>Reportes</em> del panel.
            </p>

            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
            <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                Clínica Norte — Mensaje automático del sistema. Por favor no responda directamente a este correo.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
