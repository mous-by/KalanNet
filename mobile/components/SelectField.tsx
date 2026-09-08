import { ReactElement, useMemo, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Dialog, Divider, List, Portal, Text, TextInput } from 'react-native-paper';

export interface SelectOption {
  value: number | string;
  label: string;
}

interface Props {
  label: string | ReactElement;
  value: number | string | null;
  options: SelectOption[];
  onChange: (value: number | string) => void;
  disabled?: boolean;
}

// Above this many options a plain anchored menu becomes unusable — it can
// overflow the screen with no reliable way to scroll or find an entry, which
// is exactly what happened with the 26 académies / 125 CAP lists. A dialog
// with a bounded, always-scrollable list (plus a search box once the list is
// long) works at any size instead.
const SEARCH_THRESHOLD = 8;

export default function SelectField({ label, value, options, onChange, disabled }: Props) {
  const [visible, setVisible] = useState(false);
  const [search, setSearch] = useState('');
  const selected = options.find((o) => o.value === value);

  const filtered = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) return options;
    return options.filter((option) => option.label.toLowerCase().includes(query));
  }, [options, search]);

  function open() {
    if (disabled) return;
    setSearch('');
    setVisible(true);
  }

  function close() {
    setVisible(false);
  }

  function choose(option: SelectOption) {
    onChange(option.value);
    close();
  }

  return (
    <>
      <TextInput
        mode="outlined"
        label={label}
        value={selected?.label ?? ''}
        editable={false}
        disabled={disabled}
        style={styles.input}
        right={<TextInput.Icon icon="menu-down" onPress={open} />}
        onPressIn={open}
      />
      <Portal>
        <Dialog visible={visible} onDismiss={close} style={styles.dialog}>
          <Dialog.Title>{label}</Dialog.Title>
          {options.length > SEARCH_THRESHOLD ? (
            <Dialog.Content>
              <TextInput
                mode="outlined"
                dense
                placeholder="Rechercher…"
                value={search}
                onChangeText={setSearch}
                left={<TextInput.Icon icon="magnify" />}
              />
            </Dialog.Content>
          ) : null}
          <Dialog.ScrollArea style={styles.scrollArea}>
            <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
              {filtered.length === 0 ? (
                <Text style={styles.empty}>Aucune option disponible.</Text>
              ) : (
                filtered.map((option, index) => (
                  <View key={option.value}>
                    {index > 0 ? <Divider /> : null}
                    <List.Item
                      title={option.label}
                      onPress={() => choose(option)}
                      right={option.value === value ? (props) => <List.Icon {...props} icon="check" /> : undefined}
                    />
                  </View>
                ))
              )}
            </ScrollView>
          </Dialog.ScrollArea>
          <Dialog.Actions>
            <Button onPress={close}>Fermer</Button>
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
  dialog: {
    maxHeight: '80%',
  },
  scrollArea: {
    paddingHorizontal: 0,
  },
  scrollView: {
    maxHeight: 360,
  },
  scrollContent: {
    paddingHorizontal: 8,
    paddingVertical: 4,
  },
  empty: {
    textAlign: 'center',
    opacity: 0.6,
    paddingVertical: 20,
  },
});
