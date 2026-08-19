import { MessageService } from '../../core/services/message.service';

export function getErrorMessage(err: any, fallbackKey: string, msg: MessageService): string {
  if (err.status === 401) return msg.get('cr_err_credentials');
  if (err.error?.error) return err.error.error;
  return msg.get(fallbackKey);
}
