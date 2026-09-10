import { useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Button, SegmentedButtons, Text, TextInput } from 'react-native-paper';
import * as ImagePicker from 'expo-image-picker';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';

function InfoForm() {
  const { user, refreshUser } = useAuth();
  const [nomPrenom, setNomPrenom] = useState(user?.nom_prenom ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [telephone, setTelephone] = useState(user?.telephone ?? '');
  const [photoUri, setPhotoUri] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function pickPhoto() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) return;
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8, aspect: [1, 1] });
    if (!result.canceled && result.assets[0]) {
      setPhotoUri(result.assets[0].uri);
    }
  }

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    try {
      const form = new FormData();
      form.append('_method', 'PUT');
      form.append('nomPrenom', nomPrenom);
      form.append('email', email);
      if (telephone) form.append('telephone', telephone);
      if (photoUri) {
        form.append('image', { uri: photoUri, name: 'photo.jpg', type: 'image/jpeg' } as unknown as Blob);
      }
      await api.post('/auth/profile', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      await refreshUser();
      setPhotoUri(null);
      setSuccessVisible(true);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de mettre à jour vos informations.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View style={styles.form}>
      <View style={styles.photoRow}>
        <Pressable onPress={pickPhoto}>
          <Image source={{ uri: photoUri ?? user?.photo_url }} style={styles.photo} />
        </Pressable>
        <Text style={styles.photoHint}>Touchez pour changer la photo</Text>
      </View>
      <TextInput mode="outlined" label={requiredLabel('Nom et prénom')} value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Email')} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" style={styles.input} />
      <TextInput mode="outlined" label="Téléphone (optionnel)" value={telephone ?? ''} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <Button mode="contained" onPress={handleSubmit} loading={isSubmitting} style={styles.submitButton}>
        Enregistrer
      </Button>
      <SuccessSnackbar visible={successVisible} message="Vos informations ont été mises à jour." onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

function PasswordForm() {
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit() {
    setError(null);
    if (password !== passwordConfirmation) {
      setError('Les deux mots de passe ne correspondent pas.');
      return;
    }
    setIsSubmitting(true);
    try {
      await api.put('/auth/password', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      });
      setCurrentPassword('');
      setPassword('');
      setPasswordConfirmation('');
      setSuccessVisible(true);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de mettre à jour le mot de passe.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View style={styles.form}>
      <TextInput mode="outlined" label={requiredLabel('Mot de passe actuel')} secureTextEntry value={currentPassword} onChangeText={setCurrentPassword} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Nouveau mot de passe')} secureTextEntry value={password} onChangeText={setPassword} style={styles.input} />
      <TextInput
        mode="outlined"
        label={requiredLabel('Confirmer le nouveau mot de passe')}
        secureTextEntry
        value={passwordConfirmation}
        onChangeText={setPasswordConfirmation}
        style={styles.input}
      />
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <Button mode="contained" onPress={handleSubmit} loading={isSubmitting} style={styles.submitButton}>
        Mettre à jour le mot de passe
      </Button>
      <SuccessSnackbar visible={successVisible} message="Votre mot de passe a été mis à jour." onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

export default function ProfileScreen() {
  const { user } = useAuth();
  const [tab, setTab] = useState('info');

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {user?.photo_url ? <Image source={{ uri: user.photo_url }} style={styles.headerPhoto} /> : null}
      <Text style={styles.name}>{user?.nom_prenom}</Text>
      <Text style={styles.role}>{user?.droit}</Text>

      <SegmentedButtons
        value={tab}
        onValueChange={setTab}
        style={styles.segmented}
        buttons={[
          { value: 'info', label: 'Informations', icon: 'account-outline' },
          { value: 'password', label: 'Mot de passe', icon: 'lock-outline' },
        ]}
      />

      {tab === 'info' ? <InfoForm /> : <PasswordForm />}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: SURFACE.background,
  },
  content: {
    padding: 24,
    paddingBottom: 48,
  },
  headerPhoto: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignSelf: 'center',
    marginBottom: 12,
  },
  name: {
    fontSize: 22,
    fontWeight: 'bold',
    color: SURFACE.text,
    textAlign: 'center',
  },
  role: {
    fontSize: 14,
    color: SURFACE.muted,
    marginTop: 4,
    marginBottom: 20,
    textAlign: 'center',
  },
  segmented: {
    marginBottom: 20,
  },
  form: {
    marginTop: 4,
  },
  input: {
    marginBottom: 14,
  },
  photoRow: {
    alignItems: 'center',
    marginBottom: 20,
  },
  photo: {
    width: 88,
    height: 88,
    borderRadius: 44,
  },
  photoHint: {
    fontSize: 12,
    opacity: 0.6,
    marginTop: 6,
  },
  submitButton: {
    marginTop: 4,
    borderRadius: 10,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
  success: {
    color: '#1f8a4c',
    marginBottom: 12,
  },
});
