<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Documentation KalanNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 30px;
        }
        h1 {
            text-align: center;
            color: #2563eb;
            font-size: 28px;
            margin-bottom: 10px;
        }
        h2.subtitle {
            text-align: center;
            color: #666;
            font-size: 16px;
            font-weight: normal;
            margin-bottom: 40px;
        }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .border-bottom { border-bottom: 2px solid #ddd; }
        .pb-2 { padding-bottom: 8px; }
        .mt-5 { margin-top: 40px; }
        .mt-4 { margin-top: 20px; }
        .mb-3 { margin-bottom: 15px; }
        h4 {
            color: #1e40af;
            font-size: 18px;
        }
        h5 {
            color: #374151;
            font-size: 16px;
        }
        ul { padding-left: 20px; }
        li { margin-bottom: 5px; }
        p { margin-bottom: 10px; }
        .doc-intro { font-size: 13px; color: #555; margin-bottom: 20px; }
        .doc-toc { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 6px; padding: 15px 20px; margin-bottom: 25px; }
        .doc-toc-title { font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; margin-bottom: 8px; color: #2563eb; }
        .doc-toc ol { margin: 0; padding-left: 18px; }
        .doc-tip, .doc-note { border-radius: 4px; padding: 12px 16px; margin: 14px 0; border-left: 4px solid; }
        .doc-tip { background: #f0fdf4; border-left-color: #22c55e; }
        .doc-note { background: #fefce8; border-left-color: #eab308; }
        table.doc-catalogue { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.doc-catalogue th, table.doc-catalogue td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; font-size: 12px; }
        table.doc-catalogue th { background: #dbeafe; font-weight: bold; }
        .doc-shot { margin: 16px 0; text-align: center; }
        .doc-shot img { max-width: 100%; border: 1px solid #ccc; border-radius: 6px; }
        .doc-shot figcaption { margin-top: 6px; font-size: 11px; color: #666; }
        .doc-shot-row .doc-shot { display: inline-block; width: 47%; vertical-align: top; margin: 16px 1%; }
    </style>
</head>
<body>
    <h1>DOCUMENTATION UTILISATEUR KALANNET</h1>
    <h2 class="subtitle">Le guide complet pour maîtriser KalanNet de A à Z</h2>

    @include('documentation.content')
    
</body>
</html>
