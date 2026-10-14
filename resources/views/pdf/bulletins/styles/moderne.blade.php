@include('pdf.bulletins.styles._entete')
.feuille { padding: 6mm 7mm 0; font-size: 10px; color: #000; }
.titre { margin-top: 2.5mm; width: 100%; border-top: 1.5px solid #000; border-bottom: 1px solid #000; }
.titre td { padding: 3px 0; }
.titre .t { font-size: 11.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .9px; }
.titre .a { text-align: right; font-size: 9.5px; }
.carte { width: 100%; margin-top: 2.5mm; }
.carte td { vertical-align: middle; }
.carte .photo { width: 50px; }
.carte .photo img { width: 44px; height: 44px; border-radius: 22px; border: 1px solid #000; }
.carte .initiales { width: 44px; height: 30px; border-radius: 22px; border: 1.2px solid #000; font-size: 16px; font-weight: bold; text-align: center; padding-top: 12px; }
.carte .eleve .n { font-size: 14.5px; font-weight: bold; }
.carte .eleve .d { font-size: 9.5px; margin-top: 2px; }
.carte .score { width: 112px; text-align: center; border: 1.5px solid #000; border-radius: 6px; padding: 4px; }
.carte .score .v { font-size: 19px; font-weight: bold; }
.carte .score .s { font-size: 9.5px; }
.carte .score .r { font-size: 9px; font-weight: bold; margin-top: 1px; }
.evolution { margin-top: 2mm; font-size: 9.5px; padding: 3px 7px; border: 1px solid #000; border-radius: 4px; }
.matieres { width: 100%; margin-top: 2.5mm; }
.matieres th { font-size: 8px; text-transform: uppercase; letter-spacing: .7px; text-align: left; padding: 0 3px 2px; font-weight: bold; border-bottom: 1px solid #000; }
.matieres td { padding: 2.2px 3px; vertical-align: middle; border-bottom: 1px solid #ddd; }
.matieres .nom { width: 34%; font-size: 10px; }
.matieres .barre { width: 40%; }
.matieres .piste { height: 6px; border: 0.8px solid #000; }
.matieres .niveau { height: 6px; background: #000; }
.matieres .v { width: 11%; text-align: right; font-weight: bold; font-size: 10px; }
.matieres .a { width: 15%; text-align: right; font-size: 8px; }
.bas { width: 100%; margin-top: 3mm; }
.avis { border: 1.5px solid #000; padding: 4px 7px; }
.avis .lib { font-size: 8px; text-transform: uppercase; letter-spacing: .7px; color: #222; }
.avis .t { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-top: 1px; }
.avis .p { font-size: 9px; margin-top: 1px; }
.signatures { width: 100%; margin-top: 3.5mm; }
.signatures td { width: 50%; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; text-decoration: underline; vertical-align: top; height: 11mm; }
.signatures td.d { text-align: right; }
