import js from '@eslint/js'
import tseslint from 'typescript-eslint'

export default tseslint.config(
    js.configs.recommended,
    ...tseslint.configs.recommendedTypeChecked,
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/**',
            'storage/**',
        ],
    },
    {
        files: ['resources/ts/**/*.ts', 'resources/ts/**/*.tsx'],
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
        rules: {
            // Allow unused variables with underscore prefix
            '@typescript-eslint/no-unused-vars': ['warn', {
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_',
            }],

            // Allow any for API responses / legacy code migration
            '@typescript-eslint/no-explicit-any': 'warn',

            // Allow implicit return in arrow functions
            '@typescript-eslint/no-confusing-void-expression': 'off',

            // Prefer const
            'prefer-const': 'warn',
            'no-var': 'error',

            // No console.log in production code
            'no-console': 'error',

            // 2-space indentation
            'indent': ['warn', 2],
        },
    },
    {
        // JSX-specific rules
        files: ['resources/ts/**/*.tsx'],
        rules: {
            // Allow JSX fragments
            'react/jsx-fragments': 'off',
        },
    },
)
