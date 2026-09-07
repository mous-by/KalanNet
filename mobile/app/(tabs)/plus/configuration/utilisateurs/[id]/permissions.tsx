import { useEffect, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Checkbox, Text } from 'react-native-paper';

import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface PermissionItem {
  id: number;
  name: string;
  module_display: string;
  action: string;
}

interface PermissionsData {
  utilisateur: { nomPrenom: string };
  grouped_permissions: Record<string, PermissionItem[]>;
  permission_ids: number[];
  read_only: boolean;
}

export default function UserPermissionsScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, error } = useApiGet<PermissionsData>(`/configuration/utilisateurs/${id}/permissions`, [id]);
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [saveError, setSaveError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (data) setSelected(new Set(data.permission_ids));
  }, [data]);

  function toggle(permId: number) {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(permId)) next.delete(permId);
      else next.add(permId);
      return next;
    });
  }

  async function handleSave() {
    setSaveError(null);
    setIsSaving(true);
    try {
      await api.put(`/configuration/utilisateurs/${id}/permissions`, { permissions: Array.from(selected) });
      router.back();
    } catch (err) {
      setSaveError(apiErrorMessage(err, 'Impossible d’enregistrer les permissions.'));
    } finally {
      setIsSaving(false);
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.title}>{data.utilisateur.nomPrenom}</Text>
      {data.read_only ? <Text style={styles.note}>Lecture seule : vous ne pouvez pas modifier ces permissions.</Text> : null}

      {Object.entries(data.grouped_permissions).map(([moduleKey, items]) => (
        <View key={moduleKey} style={styles.section}>
          <Text style={styles.sectionTitle}>{items[0]?.module_display ?? moduleKey}</Text>
          {items.map((perm) => (
            <View key={perm.id} style={styles.permRow}>
              <Checkbox
                status={selected.has(perm.id) ? 'checked' : 'unchecked'}
                disabled={data.read_only}
                onPress={() => toggle(perm.id)}
              />
              <Text style={styles.permLabel}>{perm.action}</Text>
            </View>
          ))}
        </View>
      ))}

      {saveError ? <Text style={styles.error}>{saveError}</Text> : null}

      {!data.read_only ? <SubmitButton label="Enregistrer" onPress={handleSave} loading={isSaving} /> : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 40 },
  title: { fontSize: 18, fontWeight: 'bold', marginBottom: 8 },
  note: { opacity: 0.6, marginBottom: 16 },
  section: { marginBottom: 16 },
  sectionTitle: { fontWeight: '600', marginBottom: 4 },
  permRow: { flexDirection: 'row', alignItems: 'center' },
  permLabel: { flex: 1 },
});
