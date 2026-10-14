@include('pdf.bulletins.styles._entete')
.feuille { padding: 9mm 12mm 0; font-size: 9px; color: #000; }
.bandeau { margin-top: 3mm; width: 100%; border: 1.5px solid #000; border-left: none; border-right: none; }
.bandeau td { padding: 5px 4px; }
.bandeau .titre { font-size: 12px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
.bandeau .annee { text-align: right; font-size: 10px; }
.identite { width: 100%; margin-top: 2.5mm; border: 1px solid #000; }
.identite td { padding: 4px 8px; border-right: 1px solid #000; white-space: nowrap; }
.identite .lib { display: block; font-size: 7px; text-transform: uppercase; letter-spacing: .8px; color: #333; }
.identite .val { display: block; font-size: 10.5px; font-weight: bold; margin-top: 1px; }
.notes { width: 100%; margin-top: 3mm; font-size: 9px; }
.notes th { background: #f1f1f1; font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; padding: 5px 5px; border: 1px solid #000; }
.notes td { padding: 2.5px 5px; border: 1px solid #000; line-height: 1.15; }
.notes .matiere { text-align: left; font-weight: bold; }
.notes .matiere .prof { display: block; font-weight: normal; font-size: 6.5px; color: #333; line-height: 1.05; }
.notes .moy { font-weight: bold; }
.notes .bareme td { font-size: 7px; color: #333; padding: 2px 5px; }
.notes tr.total td { font-weight: bold; padding-top: 4px; padding-bottom: 4px; }
.appr { font-size: 8.5px; }
.bas { width: 100%; margin-top: 4mm; }
.bas > tbody > tr > td { vertical-align: top; }
.resultat { border: 1.5px solid #000; }
.resultat .tete { border-bottom: 1px solid #000; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; padding: 4px 8px; }
.resultat .grand { text-align: center; padding: 6px 8px 2px; }
.resultat .grand .val { font-size: 22px; font-weight: bold; }
.resultat .grand .sur { font-size: 10px; }
.resultat .mention { text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; padding-bottom: 5px; }
.resultat table { width: 100%; }
.resultat .ligne td { padding: 3px 8px; border-top: 1px solid #999; font-size: 9px; }
.resultat .ligne td.v { text-align: right; font-weight: bold; }
.cadre { border: 1px solid #000; }
.cadre .tete { border-bottom: 1px solid #000; font-size: 8px; text-transform: uppercase; letter-spacing: .8px; padding: 4px 8px; font-weight: bold; }
.cadre .corps { padding: 6px 8px; font-size: 9px; }
.pointilles { border-bottom: 1px dotted #000; height: 15px; }
.case { display: inline-block; width: 8px; height: 8px; border: 1px solid #000; margin-right: 4px; }
.decisions td { padding: 2px 0; font-size: 9px; }
.signatures { width: 100%; margin-top: 4mm; }
.signatures td { width: 33%; text-align: center; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; text-decoration: underline; vertical-align: top; height: 18mm; }
.pied { position: absolute; bottom: 5mm; left: 12mm; right: 12mm; font-size: 7px; color: #555; text-align: center; border-top: 1px solid #999; padding-top: 2mm; }
