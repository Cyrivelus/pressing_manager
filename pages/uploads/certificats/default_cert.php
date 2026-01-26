<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; background: #f8f9fa; display: flex; justify-content: center; padding: 50px; }
        
        .certificate {
            width: 800px;
            padding: 60px;
            background: #fff;
            border: 20px solid #2c3e50; /* Cadre sombre puissant */
            border-image: linear-gradient(45deg, #2c3e50, #198754) 1;
            position: relative;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .header { color: #198754; text-transform: uppercase; letter-spacing: 5px; font-weight: bold; margin-bottom: 20px; }
        
        .title { font-size: 48px; font-family: 'Times New Roman', serif; margin-bottom: 10px; color: #333; }
        
        .sub-title { font-size: 18px; color: #666; margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; }

        .client-name { font-size: 32px; font-weight: bold; color: #2c3e50; margin: 20px 0; }

        .description { font-size: 16px; line-height: 1.6; color: #555; margin-bottom: 50px; }

        .footer { display: flex; justify-content: space-between; margin-top: 60px; }
        
        .signature { border-top: 1px solid #333; width: 200px; padding-top: 10px; font-size: 14px; font-weight: bold; }

        .stamp {
            position: absolute;
            bottom: 40px;
            right: 40px;
            width: 100px;
            height: 100px;
            background: #f1c40f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
            transform: rotate(-15deg);
            border: 4px double #fff;
            box-shadow: 0 0 0 4px #f1c40f;
        }
    </style>
</head>
<body>

<div class="certificate">
    <div class="header">Attestation de Conformité</div>
    <div class="title">Certificat Éco-Gérant</div>
    <div class="sub-title">Pressing Manager - Excellence Environnementale</div>

    <p class="description">Le présent document atteste que l'établissement a validé avec succès l'audit de conformité relatif à la gestion des solvants et au recyclage des déchets textiles pour l'exercice en cours.</p>

    <div class="client-name">VALIDE PAR DEFAUT</div>

    <p class="description" style="font-style: italic;">
        "Engagé pour une propreté durable et une empreinte carbone maîtrisée."
    </p>

    <div class="footer">
        <div class="signature">Direction Technique</div>
        <div class="signature">Responsable QHSE</div>
    </div>

    <div class="stamp">OFFICIEL<br>PRESSING</div>
</div>

</body>
</html>