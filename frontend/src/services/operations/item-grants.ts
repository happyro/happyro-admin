import { request } from '@umijs/max';

export async function mailItem(data: Record<string, unknown>) {
  return request('/api/operations/item-grants/mail', { method: 'POST', data });
}
