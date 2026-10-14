@include('pdf.bulletins.styles._entete')
.feuille { padding: 8mm 11mm 0; font-size: 9.5px; color: #000; }
.titre { width: 100%; margin-top: 3mm; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
.titre td { padding: 4px 4px; vertical-align: middle; }
.titre .fr { font-size: 13px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.titre .fr small { display: block; font-size: 9px; letter-spacing: 0; text-transform: none; font-weight: normal; margin-top: 1px; }
.titre .ar { text-align: right; font-size: 14.5px; font-weight: bold; }
.titre .ar small { display: block; font-size: 9.5px; font-weight: normal; }
.identite { width: 100%; margin-top: 2.5mm; border: 1px solid #000; }
.identite td { padding: 4px 8px; white-space: nowrap; border-right: 1px solid #000; }
.identite .lib { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .7px; color: #222; }
.identite .lib .ar { text-transform: none; letter-spacing: 0; font-size: 8px; }
.identite .val { display: block; font-size: 11px; font-weight: bold; margin-top: 1px; }
.notes { width: 100%; margin-top: 2.5mm; font-size: 9.5px; }
.notes th { background: #f1f1f1; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; padding: 3px 4px; font-weight: bold; border: 1px solid #000; }
.notes th .ar { text-transform: none; font-size: 8px; font-weight: normal; display: block; }
.notes td { padding: 1.8px 4px; border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #bbb; line-height: 1.1; }
.notes .mat { text-align: left; padding-left: 10px; border-right: none; }
.notes .mat-ar { text-align: right; font-size: 9.5px; border-left: none; }
.notes .moy { font-weight: bold; }
.notes tr.partie td { padding: 5px 4px 3px; border: none; border-bottom: 1.5px solid #000; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: .7px; }
.notes tr.partie td.ar { text-transform: none; text-align: right; font-size: 11px; letter-spacing: 0; }
.notes tr.partie .maitre { text-transform: none; letter-spacing: 0; font-weight: normal; }
.notes tr.domaine td { font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 3px 4px; }
.notes tr.domaine td.dom-ar { text-align: right; font-size: 10.5px; border-left: none; }
.notes tr.domaine td:first-child { border-right: none; }
.notes tr.domaine td.dom-moy { font-size: 11px; border: 1.5px solid #000; }
.notes tr.domaine .dom-appr { font-weight: normal; font-style: italic; }
.maitres { width: 100%; margin-top: 3mm; }
.maitres > tbody > tr > td { width: 50%; vertical-align: top; }
.maitre-box { border: 1px solid #000; }
.maitre-box .tete { border-bottom: 1px solid #000; padding: 3px 7px; }
.maitre-box .tete table { width: 100%; }
.maitre-box .tete td { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; }
.maitre-box .tete td.ar { text-align: right; text-transform: none; letter-spacing: 0; font-size: 9.5px; }
.maitre-box .corps { padding: 4px 7px; }
.maitre-box .corps table { width: 100%; }
.maitre-box .corps td { padding: 1.5px 0; font-size: 9px; }
.maitre-box .corps td.v { text-align: right; font-weight: bold; }
.maitre-box .appr { font-size: 11px; font-weight: bold; text-transform: uppercase; }
.maitre-box .sign { height: 11mm; font-size: 8px; vertical-align: bottom; }
.synthese { width: 100%; margin-top: 3mm; border: 1.5px solid #000; }
.synthese td { text-align: center; padding: 4px 4px; border-right: 1px solid #000; vertical-align: middle; }
.synthese .lib { display: block; font-size: 8px; text-transform: uppercase; letter-spacing: .6px; color: #222; }
.synthese .lib .ar { text-transform: none; letter-spacing: 0; font-size: 8px; }
.synthese .val { display: block; font-size: 13px; font-weight: bold; margin-top: 1px; }
.synthese td.general { border-right: 1.5px solid #000; }
.synthese td.general .val { font-size: 18px; }
.signatures { width: 100%; margin-top: 3mm; }
.signatures td { width: 50%; text-align: center; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; vertical-align: top; height: 13mm; }
.signatures .ar { text-transform: none; letter-spacing: 0; font-size: 9.5px; font-weight: normal; }
.pied { margin-top: 2mm; font-size: 8px; color: #333; text-align: center; border-top: 1px solid #999; padding-top: 1.5mm; }
