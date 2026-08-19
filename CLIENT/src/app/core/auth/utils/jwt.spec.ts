import { describe, expect, it } from 'vitest';
import { decodeJwtPayload } from './jwt';

function base64UrlEncode(input: string): string {
  return btoa(input).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function createToken(payload: object): string {
  const header = base64UrlEncode(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
  const body = base64UrlEncode(JSON.stringify(payload));
  return `${header}.${body}.signature`;
}

describe('decodeJwtPayload', () => {
  it('should decode the payload and return exp as seconds', () => {
    const token = createToken({ sub: '1', exp: 1700000000, iat: 1699996400 });
    const payload = decodeJwtPayload(token);
    expect(payload?.sub).toBe('1');
    expect(payload?.exp).toBe(1700000000);
  });

  it('should return null for an invalid token', () => {
    expect(decodeJwtPayload('not-a-jwt')).toBeNull();
    expect(decodeJwtPayload('a.b.c')).toBeNull();
  });

  it('should handle malformed base64 payload gracefully', () => {
    const token = 'header.@@invalid@@.signature';
    expect(decodeJwtPayload(token)).toBeNull();
  });

  it('should handle an empty token', () => {
    expect(decodeJwtPayload('')).toBeNull();
  });
});
