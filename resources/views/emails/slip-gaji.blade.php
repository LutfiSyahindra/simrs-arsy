<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
</head>

<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Assalamualaikum {{ $data["nama"] ?? "Bapak/Ibu" }},</p>

    <p>
        Terlampir slip gaji periode {{ $periodeText ?? ($data["periode"] ?? "-") }}.
        Mohon periksa lampiran PDF pada email ini.
    </p>

    <p>Terima kasih.</p>
</body>

</html>
