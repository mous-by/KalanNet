import { StyleSheet, View } from 'react-native';
import { ProgressBar, Text } from 'react-native-paper';

// Indicateur de force du mot de passe (même calcul que le web) : barre colorée
// en % et règles obligatoires cochées (8 caractères, lettres, chiffres).
const NIVEAUX = ['Très faible', 'Faible', 'Moyen', 'Fort'];
const COULEURS = ['#dc3545', '#fd7e14', '#e0a800', '#198754'];
const REGLES = ['8 caractères au moins', 'Des lettres', 'Des chiffres'];

export function evaluerMotDePasse(mdp: string) {
  const regles = [mdp.length >= 8, /\p{L}/u.test(mdp), /\d/.test(mdp)];
  let score = 0;
  if (mdp.length >= 8) score += 25;
  if (mdp.length >= 12) score += 15;
  if (/\p{L}/u.test(mdp)) score += 15;
  if (/\d/.test(mdp)) score += 20;
  if (/\p{Ll}/u.test(mdp) && /\p{Lu}/u.test(mdp)) score += 15;
  if (/[^\p{L}\d]/u.test(mdp)) score += 10;
  // Une règle obligatoire manque : jamais au-delà de « Faible ».
  if (regles.includes(false)) score = Math.min(score, 45);
  if (!mdp) score = 0;
  const niveau = score < 30 ? 0 : score < 60 ? 1 : score < 80 ? 2 : 3;
  return { score: Math.min(score, 100), niveau, regles };
}

export default function ForceMotDePasse({ motDePasse }: { motDePasse: string }) {
  const { score, niveau, regles } = evaluerMotDePasse(motDePasse);
  return (
    <View style={styles.bloc}>
      <View style={styles.entete}>
        <Text style={styles.petit}>Force du mot de passe</Text>
        {motDePasse ? (
          <Text style={[styles.petit, styles.gras, { color: COULEURS[niveau] }]} accessibilityLiveRegion="polite">
            {NIVEAUX[niveau]} ({score} %)
          </Text>
        ) : null}
      </View>
      <ProgressBar progress={score / 100} color={COULEURS[niveau]} style={styles.barre} accessibilityLabel={`Force du mot de passe : ${score} %`} />
      <View style={styles.regles}>
        {REGLES.map((regle, i) => (
          <Text key={regle} style={[styles.petit, { color: regles[i] ? '#198754' : '#6c757d' }]}>
            {regles[i] ? '✓' : '○'} {regle}
          </Text>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  bloc: { marginTop: -4, marginBottom: 12 },
  entete: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 },
  petit: { fontSize: 12 },
  gras: { fontWeight: '700' },
  barre: { height: 8, borderRadius: 4 },
  regles: { flexDirection: 'row', flexWrap: 'wrap', columnGap: 12, rowGap: 2, marginTop: 6 },
});
