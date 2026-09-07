import { StyleSheet } from 'react-native';
import { Button } from 'react-native-paper';

interface Props {
  label: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  mode?: 'contained' | 'outlined' | 'text';
}

export default function SubmitButton({ label, onPress, loading, disabled, mode = 'contained' }: Props) {
  return (
    <Button mode={mode} onPress={onPress} loading={loading} disabled={disabled || loading} style={styles.button}>
      {label}
    </Button>
  );
}

const styles = StyleSheet.create({
  button: {
    marginTop: 8,
  },
});
