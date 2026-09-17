import { describe, expect, it } from 'vitest';
import { MONSTER_CLASSES } from './monster-types';

describe('monster class mappings', () => {
  it('uses the same catalog classes as adventure tools', () => {
    expect(MONSTER_CLASSES).toEqual(['all', 'normal', 'mini', 'mvp']);
  });
});
