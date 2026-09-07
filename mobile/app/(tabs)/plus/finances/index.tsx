import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import { usePaginatedApi } from '@/lib/useApi';
import { Classe, Eleve } from '@/types/api';

interface Paiement {
  id_paiement: number;
  reference: string;
  montant_paye: number;
  date_paiement: string;
  motif: string | null;
  eleve?: Eleve;
  classe?: Classe;
}

export default function FinancesScreen() {
  const list = usePaginatedApi<Paiement>('/finances/paiements', {}, 'paiements');

  return (
    <View style={styles.container}>
      <View style={styles.toolbar}>
        <Button mode="outlined" onPress={() => router.push('/plus/finances/caisse')}>
          Voir la caisse
        </Button>
      </View>
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_paiement)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucun paiement."
        renderItem={(item) => (
          <View style={styles.row}>
            <Text style={styles.title}>
              {item.eleve ? `${item.eleve.prenom_eleve} ${item.eleve.nom_eleve}` : item.reference}
            </Text>
            <Text style={styles.meta}>
              {item.classe?.nom_classe ?? '—'} · {item.date_paiement}
            </Text>
            <Text style={styles.amount}>{Number(item.montant_paye).toLocaleString('fr-FR')} FCFA</Text>
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  toolbar: { padding: 16, paddingBottom: 0, alignItems: 'flex-end' },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  title: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  amount: { marginTop: 6, fontWeight: '700', color: '#1f8a4c' },
});
