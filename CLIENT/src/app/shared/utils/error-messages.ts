export function getErrorMessage(err: any, fallback: string): string {
  if (err.status === 401) return 'Credenciales incorrectas';
  if (err.error?.error) return err.error.error;
  return fallback;
}
