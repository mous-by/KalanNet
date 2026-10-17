import { useEffect, useMemo, useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Checkbox, Switch, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import { useAppTheme } from '@/context/ThemeContext';
import { useLocale } from '@/context/LocaleContext';
import { apiErrorMessage } from '@/lib/api';
import { downloadAndPrint, downloadAndShare } from '@/lib/downloadFile';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Eleve } from '@/types/api';

interface ModeleCarte {
  code: string;
  nom: string;
  description: string;
  couleurs: [string, string];
  vignette: string;
}

interface CartesData {
  classes: Classe[];
  annees: AnneeScolaire[];
  eleves: Eleve[];
  // Serveur ≥ octobre 2026 (cartes au format carte bancaire).
  modeles?: ModeleCarte[];
  modele_defaut?: string;
  entete_gauche?: string | null;
  entete_droite?: string | null;
}

/**
 * Cartes scolaires : classe, modèle (6, avec vignette et aperçu), réglages,
 * élèves à imprimer ; le PDF est imprimé ou partagé (mêmes cartes que le site).
 */
export default function CartesScolairesScreen() {
  const { t } = useLocale();
  const { theme } = useAppTheme();
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const endpoint = idClasse && idAnnee ? `/eleves/cartes-scolaires?id_classe=${idClasse}&id_annee=${idAnnee}` : '/eleves/cartes-scolaires';
  const { data, isLoading, error } = useApiGet<CartesData>(endpoint, [endpoint]);

  const [modele, setModele] = useState<string | null>(null);
  const [titre, setTitre] = useState('CARTE D’IDENTITÉ SCOLAIRE');
  const [signataire, setSignataire] = useState('Le Directeur');
  const [enteteGauche, setEnteteGauche] = useState<string | null>(null);
  const [enteteDroite, setEnteteDroite] = useState<string | null>(null);
  const [qr, setQr] = useState(true);
  const [drapeau, setDrapeau] = useState(true);
  const [logo, setLogo] = useState(true);
  const [coches, setCoches] = useState<Set<number>>(new Set());
  const [action, setAction] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  // Valeurs de l'école (modèle conseillé, en-tête du pays) dès le premier chargement.
  useEffect(() => {
    if (!data) return;
    setModele((m) => m ?? data.modele_defaut ?? data.modeles?.[0]?.code ?? null);
    setEnteteGauche((v) => v ?? data.entete_gauche ?? '');
    setEnteteDroite((v) => v ?? data.entete_droite ?? '');
  }, [data]);
  useEffect(() => setCoches(new Set()), [idClasse, idAnnee]);

  const eleves = idClasse && idAnnee ? data?.eleves ?? [] : [];
  const modeles = data?.modeles ?? [];
  const modeleChoisi = modeles.find((m) => m.code === modele);
  const classeOptions = (data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (data?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));

  const parametres = useMemo(() => {
    const p = new URLSearchParams({
      id_classe: String(idClasse ?? ''),
      id_annee: String(idAnnee ?? ''),
      card_title: titre,
      signature_label: signataire,
      entete_gauche: enteteGauche ?? '',
      entete_droite: enteteDroite ?? '',
      show_qr: qr ? '1' : '0',
      show_mali_flag: drapeau ? '1' : '0',
      show_school_logo: logo ? '1' : '0',
    });
    if (coches.size) p.set('selected_eleves', Array.from(coches).join(','));
    return p;
  }, [idClasse, idAnnee, titre, signataire, enteteGauche, enteteDroite, qr, drapeau, logo, coches]);

  /** PDF d'un modèle (ses couleurs) : aperçu (premiers élèves) ou toutes les cartes. */
  async function lancer(cle: string, code: string, quoi: 'apercu' | 'imprimer' | 'partager') {
    const m = modeles.find((x) => x.code === code);
    const p = new URLSearchParams(parametres);
    p.set('template', code);
    if (m) {
      p.set('primary_color', m.couleurs[0]);
      p.set('secondary_color', m.couleurs[1]);
    }
    if (quoi !== 'apercu') p.set('imprimer', '1');
    const chemin = `/eleves/cartes-scolaires/${quoi === 'apercu' ? 'apercu' : 'pdf'}?${p.toString()}`;
    const fichier = quoi === 'apercu' ? `Apercu_carte_${code}.pdf` : 'Cartes_scolaires.pdf';
    setMessage(null);
    setAction(cle);
    try {
      await (quoi === 'partager' ? downloadAndShare(chemin, fichier) : downloadAndPrint(chemin, fichier));
    } catch (err) {
      setMessage(apiErrorMessage(err, t('cartes.erreur')));
    } finally {
      setAction(null);
    }
  }

  function basculer(id: number) {
    setCoches((avant) => {
      const apres = new Set(avant);
      if (apres.has(id)) apres.delete(id);
      else apres.add(id);
      return apres;
    });
  }

  if (isLoading && !data) return <ActivityIndicator style={styles.spinner} size="large" />;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <SelectField label={requiredLabel(t('cartes.classe'))} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label={requiredLabel(t('cartes.annee'))} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />

      {modeles.length ? (
        <>
          <Text style={styles.section}>{t('cartes.modele')}</Text>
          <View style={styles.grille}>
            {modeles.map((m) => {
              const choisi = m.code === modele;
              return (
                <Pressable
                  key={m.code}
                  onPress={() => setModele(m.code)}
                  style={[styles.tuile, { borderColor: choisi ? theme.accent : '#d1d5db', borderWidth: choisi ? 2 : 1 }]}
                  accessibilityRole="radio"
                  accessibilityState={{ selected: choisi }}
                >
                  <Image source={{ uri: m.vignette }} style={styles.vignette} resizeMode="contain" />
                  <Text style={styles.nomModele}>{m.nom}</Text>
                  {choisi ? <Text style={[styles.choisi, { color: theme.accent }]}>✓ {t('cartes.choisi')}</Text> : null}
                  <Button compact icon="eye" onPress={() => lancer(`apercu-${m.code}`, m.code, 'apercu')} loading={action === `apercu-${m.code}`} disabled={action !== null}>
                    {t('cartes.apercu')}
                  </Button>
                </Pressable>
              );
            })}
          </View>
          {modeleChoisi ? <Text style={styles.aide}>{modeleChoisi.description}</Text> : null}
        </>
      ) : null}

      <TextInput mode="outlined" label={t('cartes.titre_carte')} value={titre} onChangeText={setTitre} style={styles.input} />
      <TextInput mode="outlined" label={t('cartes.entete_gauche')} value={enteteGauche ?? ''} onChangeText={setEnteteGauche} multiline style={styles.input} />
      <TextInput mode="outlined" label={t('cartes.entete_droite')} value={enteteDroite ?? ''} onChangeText={setEnteteDroite} multiline style={styles.input} />
      <TextInput mode="outlined" label={t('cartes.signature')} value={signataire} onChangeText={setSignataire} style={styles.input} />
      <View style={styles.options}>
        {([['qr', qr, setQr], ['drapeau', drapeau, setDrapeau], ['logo', logo, setLogo]] as const).map(([cle, valeur, changer]) => (
          <View key={cle} style={styles.option}>
            <Switch value={valeur} onValueChange={changer} />
            <Text>{t(`cartes.${cle}` as 'cartes.qr')}</Text>
          </View>
        ))}
      </View>

      {!idClasse || !idAnnee ? (
        <Text style={styles.vide}>{t('cartes.choisir')}</Text>
      ) : isLoading ? (
        <ActivityIndicator style={styles.spinner} />
      ) : eleves.length === 0 ? (
        <Text style={styles.vide}>{t('cartes.aucun_eleve')}</Text>
      ) : (
        <>
          <View style={styles.entete}>
            <Text style={styles.section}>{t('cartes.eleves').replace(':count', String(eleves.length))}</Text>
            <Checkbox.Item
              label={t('cartes.tout_cocher')}
              status={coches.size === eleves.length ? 'checked' : coches.size ? 'indeterminate' : 'unchecked'}
              onPress={() => setCoches(coches.size === eleves.length ? new Set() : new Set(eleves.map((e) => e.id_eleve)))}
              style={styles.toutCocher}
            />
          </View>
          {eleves.map((e) => (
            <Checkbox.Item
              key={e.id_eleve}
              label={`${e.prenom_eleve} ${e.nom_eleve}${e.matricule ? ` · ${e.matricule}` : ''}`}
              status={coches.has(e.id_eleve) ? 'checked' : 'unchecked'}
              onPress={() => basculer(e.id_eleve)}
            />
          ))}
          {modele === 'recto_verso' ? <Text style={styles.aide}>{t('cartes.recto_verso_aide')}</Text> : null}
          {message ? <Text style={styles.error}>{message}</Text> : null}
          <View style={styles.actions}>
            <Button mode="contained" icon="printer" onPress={() => modele && lancer('imprimer', modele, 'imprimer')} loading={action === 'imprimer'} disabled={action !== null || !modele}>
              {coches.size ? t('cartes.imprimer_n').replace(':count', String(coches.size)) : t('cartes.imprimer_tout')}
            </Button>
            <Button mode="outlined" icon="share-variant" onPress={() => modele && lancer('partager', modele, 'partager')} loading={action === 'partager'} disabled={action !== null || !modele}>
              {t('cartes.partager')}
            </Button>
          </View>
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 16, paddingBottom: 40 },
  spinner: { marginTop: 30 },
  error: { color: '#d33', marginVertical: 8, textAlign: 'center' },
  section: { fontSize: 16, fontWeight: '700', marginTop: 12, marginBottom: 8 },
  grille: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  tuile: { width: '47%', borderRadius: 10, padding: 6, backgroundColor: '#fff', alignItems: 'center' },
  vignette: { width: '100%', aspectRatio: 1.58 },
  nomModele: { fontWeight: '700', marginTop: 4, textAlign: 'center' },
  choisi: { fontSize: 12, fontWeight: '600' },
  aide: { color: '#6b7280', marginVertical: 8 },
  input: { marginTop: 10 },
  options: { flexDirection: 'row', flexWrap: 'wrap', gap: 16, marginVertical: 12 },
  option: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  vide: { textAlign: 'center', color: '#6b7280', marginTop: 20 },
  entete: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap' },
  toutCocher: { paddingVertical: 0 },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 16, justifyContent: 'center' },
});
