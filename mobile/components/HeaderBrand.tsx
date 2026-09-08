import { StyleSheet, View } from 'react-native';

import BrandLogo from './BrandLogo';
import BrandTitle from './BrandTitle';

export default function HeaderBrand() {
  return (
    <View style={styles.row}>
      <BrandLogo size={26} />
      <BrandTitle fontSize={17} />
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
});
