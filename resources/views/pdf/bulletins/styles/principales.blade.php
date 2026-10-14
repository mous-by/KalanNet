@include('pdf.bulletins.styles._entete')
.feuille { padding: 5mm 6mm 0; font-size: 9px; color: #000; }
.infos { width: 100%; margin-top: 2mm; font-size: 9.5px; }
.infos td { padding: 1px 0; vertical-align: top; }
.infos .lib { font-weight: bold; text-transform: uppercase; white-space: nowrap; padding-right: 6px; width: 1%; }
.infos .fort { font-weight: bold; }
.infos .adroite { text-align: right; white-space: nowrap; }
.notes { width: 100%; margin-top: 2mm; border: 1px solid #000; }
.notes th { font-size: 8.5px; font-weight: bold; border: 1px solid #000; padding: 2.5px 3px; text-align: left; }
.notes td { border: 1px solid #000; padding: 1.8px 3px; font-size: 9px; }
.notes .formule td { font-size: 7.5px; padding: 1px 3px; }
.notes .mat { font-weight: bold; white-space: nowrap; }
.notes td.n { text-align: right; white-space: nowrap; }
.notes td.c { text-align: center; font-weight: bold; }
.notes td.appr { font-size: 8px; }
.notes tr.groupe td { font-weight: bold; text-transform: uppercase; letter-spacing: .6px; font-size: 8px; padding: 2.5px 3px; }
.notes tr.partiel td { background: #e6e6e6; font-weight: bold; }
.notes tr.generale td { background: #d9d9d9; font-weight: bold; font-size: 10px; }
.bas { width: 100%; margin-top: 2mm; border: 1px solid #000; }
.bas td { border: 1px solid #000; padding: 2.5px 4px; font-size: 9px; }
.bas .lib { font-weight: bold; }
.signatures { width: 100%; margin-top: 3mm; }
.signatures td { width: 50%; font-size: 8.5px; font-weight: bold; text-transform: uppercase; text-decoration: underline; vertical-align: top; height: 12mm; }
.signatures td.d { text-align: right; }
.dense .notes td { padding-top: 1px; padding-bottom: 1px; font-size: 8.5px; }
.dense .entete .logo img { width: 30px; height: 30px; }
.dense .signatures td { height: 8mm; }
