import { Text } from 'react-native-paper';

// Mirrors the web forms' `<span class="text-danger">*</span>` convention for
// required fields — used as the `label` prop of TextInput/SelectField/DateField,
// which all accept a plain string or a React element there.
export default function requiredLabel(label: string) {
  return (
    <Text>
      {label} <Text style={{ color: '#d33' }}>*</Text>
    </Text>
  );
}
