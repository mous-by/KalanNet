import { useState } from 'react';
import { Platform, StyleSheet } from 'react-native';
import { TextInput } from 'react-native-paper';
import DateTimePicker from '@react-native-community/datetimepicker';

interface Props {
  label: string;
  value: string | null; // ISO date, e.g. "2026-09-07"
  onChange: (value: string) => void;
  disabled?: boolean;
}

function toIso(date: Date): string {
  return date.toISOString().slice(0, 10);
}

export default function DateField({ label, value, onChange, disabled }: Props) {
  const [visible, setVisible] = useState(false);

  // @react-native-community/datetimepicker has no web implementation;
  // fall back to a plain ISO-date text input there (dev/preview only).
  if (Platform.OS === 'web') {
    return (
      <TextInput
        mode="outlined"
        label={`${label} (AAAA-MM-JJ)`}
        value={value ?? ''}
        onChangeText={onChange}
        disabled={disabled}
        placeholder="2026-09-07"
        style={styles.input}
      />
    );
  }

  return (
    <>
      <TextInput
        mode="outlined"
        label={label}
        value={value ?? ''}
        editable={false}
        disabled={disabled}
        style={styles.input}
        right={<TextInput.Icon icon="calendar" onPress={() => !disabled && setVisible(true)} />}
        onPressIn={() => !disabled && setVisible(true)}
      />
      {visible ? (
        <DateTimePicker
          value={value ? new Date(value) : new Date()}
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

const styles = StyleSheet.create({
  input: {
    marginBottom: 12,
  },
});
