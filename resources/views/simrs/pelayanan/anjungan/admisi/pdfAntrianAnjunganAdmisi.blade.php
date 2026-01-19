<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cetak Antrian</title>
        <style>
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                text-align: center;
                font-family: Arial, sans-serif;
                font-size: 14px;
                margin: 0;
                padding: 0;
            }

            .container {
                width: 80mm;
                padding: 5px;
                margin: 0 auto;
                box-sizing: border-box;
            }

            h3 {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 3px;
            }

            h4 {
                margin: 3px 0;
                font-size: 16px;
            }

            p {
                margin: 2px 0;
                font-size: 14px;
            }

            .nomor-antrian {
                font-size: 30px;
                font-weight: bold;
                margin: 10px 0;
                border: 2px solid black;
                display: inline-block;
                padding: 8px 12px;
                border-radius: 5px;
            }

            .divider {
                border-top: 1px dashed black;
                margin: 5px 0;
            }

            .footer {
                font-size: 12px;
                margin-top: 5px;
            }

            .page-break {
                page-break-after: always;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h3>FASKES TINGKAT LANJUT</h3>
            <h4>RS ARSY PACIRAN</h4>
            <div class="divider"></div>
            <div class="nomor-antrian">{{ $nomor }}</div>
            <h4>ANTRIAN ADMISI</h4>
            <div class="divider"></div>
            <p class="footer">Silakan menunggu panggilan.</p>
        </div>

        <div class="page-break"></div>

        <div class="container">
            <h3>FASKES TINGKAT LANJUT</h3>
            <h4>RS ARSY PACIRAN</h4>
            <div class="divider"></div>
            <div class="nomor-antrian">{{ $nomor }}</div>
            <h4>ANTRIAN ADMISI</h4>
            <div class="divider"></div>
            <p class="footer">Silakan menunggu panggilan.</p>
        </div>
    </body>

</html>
