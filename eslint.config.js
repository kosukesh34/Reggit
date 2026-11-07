import eslint from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  {
    ignores: ['public/build/**', 'resources/js/liff-ui/generated/**'],
  },
  eslint.configs.recommended,
  {
    rules: {
      'no-undef': 'off',
    },
  },
  ...tseslint.configs.recommended,
  {
    files: ['resources/js/**/*.{ts,tsx}'],
    plugins: {
      'react-hooks': reactHooks,
    },
    rules: {
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      'no-undef': 'off',
      'no-unused-vars': 'off',
      'no-useless-assignment': 'off',
      'no-irregular-whitespace': 'off',
      'prefer-const': 'off',
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/no-this-alias': 'off',
      // 使われないコードの蓄積を防ぐ。意図的に使わない引数・変数は _ で始める
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrors: 'none' }],
    },
  },
);
