@include('pdf.bulletins.styles._entete')
.feuille { padding: 5mm 7mm 0; font-size: 9px; color: #000; }
.bande { width: 100%; margin-top: 2mm; border-top: 1.5px solid #000; border-bottom: 1px solid #000; }
.bande td { padding: 2.5px 2px; font-size: 9px; vertical-align: middle; }
.bande .t { font-weight: bold; text-transform: uppercase; letter-spacing: .7px; }
.bande .ar { text-align: center; font-size: 12px; font-weight: bold; }
.bande .a { text-align: right; }
.eleve { width: 100%; margin-top: 1.5mm; }
.eleve td { white-space: nowrap; padding-right: 6px; vertical-align: middle; }
.eleve td.photo { width: 40px; }
.eleve .photo img { width: 34px; height: 34px; border-radius: 17px; border: 1px solid #000; }
.eleve .initiales { width: 34px; height: 24px; border-radius: 17px; border: 1.2px solid #000; font-size: 12px; font-weight: bold; text-align: center; padding-top: 10px; }
.eleve .lib { display: block; font-size: 7.5px; text-transform: uppercase; letter-spacing: .6px; color: #222; }
.eleve .val { display: block; font-size: 10px; font-weight: bold; }
.notes { width: 100%; margin-top: 1.5mm; }
.notes th { font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; padding: 2px 3px; border-bottom: 1.2px solid #000; }
.notes td { padding: 1.5px 3px; border-bottom: 1px solid #ddd; font-size: 8.5px; line-height: 1.05; }
.notes .mat { padding-left: 8px; }
.notes .mat-ar { text-align: right; font-size: 9px; }
.notes tr.domaine td { font-weight: bold; font-size: 9px; padding: 2.2px 3px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
.notes tr.domaine td.ar { text-align: right; font-size: 10px; }
.notes tr.domaine td.moy { border: 1.2px solid #000; }
.notes tr.partie td { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; padding: 3px 3px 1.5px; border-bottom: 1.5px solid #000; }
.notes tr.partie td.ar { text-align: right; text-transform: none; letter-spacing: 0; font-size: 10px; }
.maitres { width: 100%; margin-top: 2mm; }
.maitres td.box { width: 50%; border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
.maitres .lib { font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; color: #222; }
.maitres .nom { font-size: 8.5px; font-weight: bold; }
.maitres .appr { font-size: 9px; font-weight: bold; text-transform: uppercase; }
.maitres .sign { height: 6mm; }
.synthese { width: 100%; margin-top: 2mm; border: 1.5px solid #000; }
.synthese td { text-align: center; padding: 3px; border-right: 1px solid #000; }
.synthese .lib { display: block; font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; color: #222; }
.synthese .val { display: block; font-size: 11.5px; font-weight: bold; }
.synthese td.general .val { font-size: 13.5px; }
.signatures { width: 100%; margin-top: 2mm; }
.signatures td { width: 50%; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; vertical-align: top; height: 9mm; }
.signatures td.d { text-align: right; }
