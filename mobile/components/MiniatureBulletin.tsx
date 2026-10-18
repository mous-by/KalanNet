import { ReactNode } from 'react';
import { DimensionValue, StyleSheet, View, ViewStyle } from 'react-native';

/**
 * Croquis d'un modèle de bulletin : même dessin que les tuiles de la page web
 * (resources/views/bulletins/partials/miniature.blade.php), sans charger de PDF.
 */

type MiseEnPage = 'paysage' | 'a4' | 'a5';

const GRIS = '#d9dee6';
const NOIR = '#222';

function Trait({ width = '100%', height = 3, color = GRIS, center, style }: { width?: DimensionValue; height?: number; color?: string; center?: boolean; style?: ViewStyle }) {
  return <View style={[{ width, height, borderRadius: 2, backgroundColor: color }, center && { alignSelf: 'center' }, style]} />;
}

function Bande({ color = GRIS, style }: { color?: string; style?: ViewStyle }) {
  return <View style={[{ height: 7, borderRadius: 1, backgroundColor: color }, style]} />;
}

function Ligne({ children }: { children: ReactNode }) {
  return <View style={styles.ligne}>{children}</View>;
}

function Lignes({ children }: { children: ReactNode }) {
  return <View style={styles.lignes}>{children}</View>;
}

function Cases({ pleines = [], nombre, hauteur = 13, style }: { pleines?: number[]; nombre: number; hauteur?: number; style?: ViewStyle }) {
  return (
    <View style={[styles.cases, style]}>
      {Array.from({ length: nombre }, (_, i) => (
        <View
          key={i}
          style={[styles.case, { height: hauteur }, pleines.includes(i) && { backgroundColor: NOIR, borderColor: NOIR }]}
        />
      ))}
    </View>
  );
}

const repete = (n: number, rendu: (i: number) => ReactNode) => Array.from({ length: n }, (_, i) => rendu(i));

function Contenu({ code }: { code: string }) {
  switch (code) {
    case 'standard':
      return (
        <>
          {repete(2, (e) => (
            <View key={e} style={[styles.exemplaire, e === 1 && styles.exemplaireSuivant]}>
              <Ligne>
                <Trait width="35%" />
                <Trait width="35%" />
              </Ligne>
              <Trait width="60%" center />
              <View style={styles.bandeDouble} />
              <Lignes>{repete(7, (i) => <Trait key={i} width={i % 2 ? '80%' : '100%'} color="#cbd5e1" />)}</Lignes>
            </View>
          ))}
        </>
      );
    case 'classique':
      return (
        <>
          <Trait width="60%" center />
          <Trait width="40%" center />
          <Bande color={NOIR} />
          <Lignes>{repete(9, (i) => <Trait key={i} width={i % 3 ? '80%' : '100%'} />)}</Lignes>
          <Cases nombre={2} pleines={[0]} />
        </>
      );
    case 'domaines':
      return (
        <>
          <Ligne>
            <Trait width="35%" />
            <Trait width="30%" />
          </Ligne>
          <Bande color={NOIR} />
          <Lignes>
            {repete(4, (d) => (
              <View key={d} style={styles.lignes}>
                <Trait height={4} color="#9ca3af" />
                <Trait width="80%" />
                <Trait width="80%" />
              </View>
            ))}
          </Lignes>
          <Cases nombre={2} />
          <Cases nombre={1} pleines={[0]} hauteur={7} style={{ marginTop: 2 }} />
        </>
      );
    case 'essentiel':
      return (
        <>
          <Trait width="60%" />
          <View style={styles.bandeFilet} />
          <Lignes>{repete(8, (i) => <Trait key={i} width={i % 2 ? '80%' : '100%'} />)}</Lignes>
          <Cases nombre={3} pleines={[0]} />
        </>
      );
    case 'domaines_compact':
      return (
        <>
          {repete(2, (e) => (
            <View key={e} style={[styles.exemplaire, e === 1 && styles.exemplaireSuivant]}>
              <Ligne>
                <Trait width="35%" />
                <Trait width="30%" />
              </Ligne>
              <Bande color={NOIR} />
              <Lignes>
                {repete(4, (d) => (
                  <View key={d} style={styles.lignes}>
                    <Trait height={4} color="#9ca3af" />
                    <Trait width="80%" />
                  </View>
                ))}
              </Lignes>
              <Cases nombre={2} />
            </View>
          ))}
        </>
      );
    case 'principales':
      return (
        <>
          {repete(2, (e) => (
            <View key={e} style={[styles.exemplaire, e === 1 && styles.exemplaireSuivant]}>
              <Ligne>
                <Trait width="45%" />
                <Trait width="15%" />
              </Ligne>
              <Trait width="60%" />
              <Lignes>
                {repete(5, (i) => <Trait key={i} width={i % 2 ? '80%' : '100%'} />)}
                <Trait height={4} color="#bbb" />
                {repete(3, (i) => <Trait key={`s${i}`} width="80%" />)}
                <Trait height={4} color="#bbb" />
                <Trait height={4} color="#777" />
              </Lignes>
            </View>
          ))}
        </>
      );
    default:
      // Modèle sans croquis dédié (ex. École de santé) : allure générique A4.
      return (
        <>
          <Trait width="60%" center />
          <Trait width="40%" center />
          <Bande />
          <Lignes>{repete(9, (i) => <Trait key={i} width={i % 3 ? '80%' : '100%'} />)}</Lignes>
          <Cases nombre={3} />
        </>
      );
  }
}

export default function MiniatureBulletin({ code, miseEnPage }: { code: string; miseEnPage: MiseEnPage }) {
  return (
    <View style={[styles.feuille, styles[miseEnPage]]} accessibilityElementsHidden importantForAccessibility="no-hide-descendants">
      <Contenu code={code} />
    </View>
  );
}

const styles = StyleSheet.create({
  feuille: {
    alignSelf: 'center',
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#d5dae2',
    borderRadius: 3,
    padding: 7,
    overflow: 'hidden',
    gap: 3,
    shadowColor: '#0f172a',
    shadowOpacity: 0.08,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
    elevation: 2,
  },
  a4: { width: 106, height: 150 },
  a5: { width: 92, height: 130 },
  paysage: { width: 150, height: 106, flexDirection: 'row', gap: 0, padding: 0 },
  exemplaire: { flex: 1, padding: 5, gap: 3 },
  exemplaireSuivant: { borderLeftWidth: 1, borderStyle: 'dashed', borderColor: NOIR },
  ligne: { flexDirection: 'row', justifyContent: 'space-between' },
  lignes: { gap: 2, marginTop: 2 },
  cases: { flexDirection: 'row', gap: 3, marginTop: 'auto' },
  case: { flex: 1, borderRadius: 2, borderWidth: 1, borderColor: GRIS },
  bandeDouble: { height: 8, borderWidth: 1.5, borderColor: '#475569' },
  bandeFilet: { height: 4, borderTopWidth: 2, borderColor: NOIR },
});
