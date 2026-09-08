import { Snackbar } from 'react-native-paper';

interface Props {
  visible: boolean;
  message: string;
  onDismiss: () => void;
}

// The mobile equivalent of the web's SweetAlert success popups — a brief,
// friendly confirmation instead of navigating away with no feedback at all.
export default function SuccessSnackbar({ visible, message, onDismiss }: Props) {
  return (
    <Snackbar visible={visible} onDismiss={onDismiss} duration={1200} style={{ backgroundColor: '#1f8a4c' }}>
      {message}
    </Snackbar>
  );
}
