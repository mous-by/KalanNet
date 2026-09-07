import { StyleSheet, Text, View } from 'react-native';

// Matches the web login's tricolor wordmark exactly (.brand-title-kal/-an/-red
// in resources/views/auth/login.blade.php) — the Mali flag colors split
// across "Kal" / "an" / "Net".
export default function BrandTitle({ fontSize = 30 }: { fontSize?: number }) {
  return (
    <View style={styles.row}>
      <Text style={[styles.text, { fontSize, color: '#16a34a' }]}>Kal</Text>
      <Text style={[styles.text, { fontSize, color: '#eab308' }]}>an</Text>
      <Text style={[styles.text, { fontSize, color: '#dc2626' }]}>Net</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
  },
  text: {
    fontWeight: '800',
    letterSpacing: -0.5,
  },
});
