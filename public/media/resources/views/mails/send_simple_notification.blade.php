<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Notification</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #f68b0b;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
        }
        .content {
            padding: 20px;
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        .content p {
            margin-bottom: 15px;
        }
        .button {
            display: block;
            width: 200px;
            margin: 20px auto;
            text-align: center;
            background-color: #3498db;
            color: white;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .button:hover {
            background-color: #f68b0b;
        }
        .footer {
            background: #f68b0b;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            Notification Importante
        </div>

        <!-- Contenu -->
        <div class="content">
            <p><strong>{!! $mailContent !!}</strong></p>
            <p>En cas de difficulté, veuillez contacter : <br><strong>direction@alerte-info.net</strong></p>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            © {{ date('Y') }} ALERTE INFO SARL - Tous droits réservés.
        </div>
    </div>
</body>
</html>
