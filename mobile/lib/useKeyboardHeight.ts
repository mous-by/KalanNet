import { useEffect, useState } from 'react';
import { Keyboard, Platform } from 'react-native';

/**
 * Current on-screen keyboard height in px (0 when hidden).
 *
 * The Dialogs in this app (react-native-paper's <Dialog>) render as a plain
 * Animated.View centered with justifyContent: 'center' — not a real native
 * Modal — so neither the OS's window resize behavior nor a nested
 * KeyboardAvoidingView repositions them when the keyboard opens; a dialog's
 * bottom (its Actions row, e.g. "Enregistrer") can end up hidden behind the
 * keyboard. Applying `marginBottom: keyboardHeight` to the Dialog's own
 * `style` shifts its centered position up by roughly half that amount,
 * which is enough to clear the keyboard for typical short form dialogs.
 */
export function useKeyboardHeight(): number {
  const [height, setHeight] = useState(0);

  useEffect(() => {
    const showEvent = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvent = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';

    const showSub = Keyboard.addListener(showEvent, (e) => setHeight(e.endCoordinates?.height ?? 0));
    const hideSub = Keyboard.addListener(hideEvent, () => setHeight(0));

    return () => {
      showSub.remove();
      hideSub.remove();
    };
  }, []);

  return height;
}
