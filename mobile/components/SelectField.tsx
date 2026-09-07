import { useState } from 'react';
import { StyleSheet } from 'react-native';
import { Menu, TextInput } from 'react-native-paper';

export interface SelectOption {
  value: number | string;
  label: string;
}

interface Props {
  label: string;
  value: number | string | null;
  options: SelectOption[];
  onChange: (value: number | string) => void;
  disabled?: boolean;
}

export default function SelectField({ label, value, options, onChange, disabled }: Props) {
  const [visible, setVisible] = useState(false);
  const selected = options.find((o) => o.value === value);

  return (
    <Menu
      visible={visible}
      onDismiss={() => setVisible(false)}
      anchor={
        <TextInput
          mode="outlined"
          label={label}
          value={selected?.label ?? ''}
          editable={false}
          disabled={disabled}
          style={styles.input}
          right={<TextInput.Icon icon="menu-down" onPress={() => setVisible(true)} />}
          onPressIn={() => !disabled && setVisible(true)}
        />
      }>
      {options.length === 0 ? (
        <Menu.Item title="Aucune option disponible" disabled />
      ) : (
        options.map((option) => (
          <Menu.Item
            key={option.value}
            title={option.label}
            onPress={() => {
              onChange(option.value);
              setVisible(false);
            }}
          />
        ))
      )}
    </Menu>
  );
}

const styles = StyleSheet.create({
  input: {
    marginBottom: 12,
  },
});
