import { useEffect, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Checkbox, Text } from 'react-native-paper';

import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface PermissionItem {
  id: number;
  name: string;
  module_display: string;
  action: string;
}

interface PermissionsData {
  utilisateur: { nomPrenom: string; droit: string; managed_orders: string[] | null; ecole?: { typeEcole: string } | null };
  grouped_permissions: Record<string, PermissionItem[]>;
  permission_ids: number[];
  read_only: boolean;
}

const MANAGED_ORDERS_OPTIONS: { value: string; label: string }[] = [
  { value: 'fondamentale1', label: 'Fondamentale I' },
  { value: 'fondamentale2', label: 'Fondamentale II' },
  { value: 'secondairegenerale', label: 'Secondaire Général' },
  { value: 'secondairetechniqueetprofessionnel', label: 'Secondaire Technique et Professionnel' },
];

export default function UserPermissionsScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, error } = useApiGet<PermissionsData>(`/configuration/utilisateurs/${id}/permissions`, [id]);
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [managedOrders, setManagedOrders] = useState<Set<string>>(new Set());
  const [saveError, setSaveError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  useEffect(() => {
    if (data) {
      setSelected(new Set(data.permission_ids));
      setManagedOrders(new Set(data.utilisateur.managed_orders ?? []));
    }
  }, [data]);

  function toggle(permId: number) {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(permId)) next.delete(permId);
      else next.add(permId);
      return next;
    });
  }

  function toggleOrder(order: string) {
    setManagedOrders((prev) => {
      const next = new Set(prev);
      if (next.has(order)) next.delete(order);
      else next.add(order);
      return next;
    });
  }

  const isComplexGestionnaire = data?.utilisateur.droit === 'Gestionnaire' && data?.utilisateur.ecole?.typeEcole === 'Complexe Scolaire';

  async function handleSave() {
    if (isComplexGestionnaire && managedOrders.size === 0) {
      setSaveError('Veuillez sélectionner au moins un ordre d’enseignement géré.');
      return;
    }
    setSaveError(null);
    setIsSaving(true);
    try {
      await api.put(`/configuration/utilisateurs/${id}/permissions`, {
        permissions: Array.from(selected),
        managed_orders: Array.from(managedOrders),
      });
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
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

      {isComplexGestionnaire ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>
            Ordres d'enseignement gérés <Text style={styles.required}>*</Text>
          </Text>
          <Text style={styles.note}>Ce gestionnaire d'un complexe scolaire doit être limité à un ou plusieurs ordres.</Text>
          {MANAGED_ORDERS_OPTIONS.map((option) => (
            <Checkbox.Item
              key={option.value}
              label={option.label}
              status={managedOrders.has(option.value) ? 'checked' : 'unchecked'}
              disabled={data.read_only}
              onPress={() => toggleOrder(option.value)}
              style={styles.permRow}
            />
          ))}
        </View>
      ) : null}

      {Object.entries(data.grouped_permissions).map(([moduleKey, items]) => (
        <View key={moduleKey} style={styles.section}>
          <Text style={styles.sectionTitle}>{items[0]?.module_display ?? moduleKey}</Text>
          {items.map((perm) => (
            <Checkbox.Item
              key={perm.id}
              label={perm.action}
              status={selected.has(perm.id) ? 'checked' : 'unchecked'}
              disabled={data.read_only}
              onPress={() => toggle(perm.id)}
              style={styles.permRow}
            />
          ))}
        </View>
      ))}

      {saveError ? <Text style={styles.error}>{saveError}</Text> : null}

      {!data.read_only ? <SubmitButton label="Enregistrer" onPress={handleSave} loading={isSaving} /> : null}

      <SuccessSnackbar visible={successVisible} message="Permissions enregistrées avec succès." onDismiss={() => setSuccessVisible(false)} />
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
  required: { color: '#d33' },
  permRow: { paddingHorizontal: 0 },
});
