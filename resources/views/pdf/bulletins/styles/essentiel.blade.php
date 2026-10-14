@include('pdf.bulletins.styles._entete')
.feuille { padding: 6mm 8mm 0; font-size: 10px; color: #000; }
.titre { margin-top: 2.5mm; width: 100%; border-top: 1.5px solid #000; border-bottom: 1px solid #000; }
.titre td { padding: 3px 0; }
.titre .t { font-size: 11.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .9px; }
.titre .a { text-align: right; font-size: 9.5px; }
.eleve { width: 100%; margin-top: 2mm; }
.eleve td { padding: 0 6px 0 0; white-space: nowrap; }
.eleve .lib { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .7px; color: #222; }
.eleve .val { display: block; font-size: 11px; font-weight: bold; }
.notes { width: 100%; margin-top: 2.5mm; }
.notes th { font-size: 8px; text-transform: uppercase; letter-spacing: .6px; padding: 3px 3px; border-bottom: 1.2px solid #000; font-weight: bold; }
.notes td { padding: 2.4px 3px; border-bottom: 1px solid #bbb; }
.notes .mat { text-align: left; }
.notes .moy { font-weight: bold; }
.notes .appr { font-size: 9px; text-align: right; }
.synthese { width: 100%; margin-top: 3mm; }
.synthese td { text-align: center; padding: 4px; vertical-align: middle; border: 1px solid #000; }
.synthese .lib { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .6px; color: #222; }
.synthese .val { display: block; font-size: 13px; font-weight: bold; }
.synthese td.general { border-width: 1.5px; }
.synthese td.general .val { font-size: 16px; }
.synthese td.general .mention { display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; }
.signatures { width: 100%; margin-top: 4mm; }
.signatures td { width: 50%; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; text-decoration: underline; vertical-align: top; height: 12mm; }
.signatures td.d { text-align: right; }
