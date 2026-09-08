import { ReactElement, useState } from 'react';
import { Platform, StyleSheet, View } from 'react-native';
import { Button, Dialog, Portal, TextInput } from 'react-native-paper';
import DateTimePicker from '@react-native-community/datetimepicker';

interface Props {
  label: string | ReactElement;
  value: string | null; // ISO date, e.g. "2026-09-07"
  onChange: (value: string) => void;
  disabled?: boolean;
}

// Local calendar date, not toISOString() (UTC) — the latter can shift the
// date by a day depending on the device's timezone relative to UTC.
function toIso(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function parseIso(value: string | null): Date {
  if (!value) return new Date();
  const parsed = new Date(`${value}T00:00:00`);
  return Number.isNaN(parsed.getTime()) ? new Date() : parsed;
}

export default function DateField({ label, value, onChange, disabled }: Props) {
  const [visible, setVisible] = useState(false);
  const [draft, setDraft] = useState<Date>(() => parseIso(value));

  // @react-native-community/datetimepicker has no web implementation;
  // fall back to a plain ISO-date text input there (dev/preview only).
  if (Platform.OS === 'web') {
    const webLabel = typeof label === 'string' ? `${label} (AAAA-MM-JJ)` : <>{label} (AAAA-MM-JJ)</>;
    return (
      <TextInput
        mode="outlined"
        label={webLabel}
        value={value ?? ''}
        onChangeText={onChange}
        disabled={disabled}
        placeholder="2026-09-07"
        style={styles.input}
      />
    );
  }

  function open() {
    if (disabled) return;
    setDraft(parseIso(value));
    setVisible(true);
  }

  const anchor = (
    <TextInput
      mode="outlined"
      label={label}
      value={value ?? ''}
      editable={false}
      disabled={disabled}
      style={styles.input}
      right={<TextInput.Icon icon="calendar" onPress={open} />}
      onPressIn={open}
    />
  );

  // Android's picker is always its own native dialog (mode/display only
  // change its style, never whether it's a popup) — mounting it is what
  // opens it, and its onChange already fires once, on the user's final
  // choice, exactly like the old behavior this preserves.
  if (Platform.OS === 'android') {
    return (
      <>
        {anchor}
        {visible ? (
          <DateTimePicker
            value={draft}
            mode="date"
            onChange={(event, date) => {
              setVisible(false);
              if (event.type === 'set' && date) onChange(toIso(date));
            }}
          />
        ) : null}
      </>
    );
  }

  // iOS's default/compact display expands in place and reports onChange as
  // soon as it renders/is touched, before the user has actually chosen a
  // date — which read as "the date doesn't take". A spinner in our own
  // dialog with an explicit Valider button removes that ambiguity.
  function confirm() {
    onChange(toIso(draft));
    setVisible(false);
  }

  return (
    <>
      {anchor}
      <Portal>
        <Dialog visible={visible} onDismiss={() => setVisible(false)}>
          <Dialog.Title>{label}</Dialog.Title>
          <View style={styles.pickerWrap}>
            <DateTimePicker
              value={draft}
              mode="date"
              display="spinner"
              onChange={(event, date) => {
                if (date) setDraft(date);
              }}
            />
          </View>
          <Dialog.Actions>
            <Button onPress={() => setVisible(false)}>Annuler</Button>
            <Button onPress={confirm}>Valider</Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </>
  );
}

const styles = StyleSheet.create({
  input: {
    marginBottom: 12,
  },
  pickerWrap: {
    alignItems: 'center',
  },
});
