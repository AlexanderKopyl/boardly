import { defineConfig, globalIgnores } from 'eslint/config'
import nextVitals from 'eslint-config-next/core-web-vitals'
import nextTs from 'eslint-config-next/typescript'

const eslintConfig = defineConfig([
  ...nextVitals,
  ...nextTs,
  globalIgnores([
    '.next/**',
    'out/**',
    'build/**',
    'next-env.d.ts',
  ]),
  {
    settings: {
      // eslint-plugin-react v7 bundled in eslint-config-next calls the legacy
      // context.getFilename() API removed in ESLint 10 during React version
      // auto-detection. Pinning the version here skips that code path.
      react: { version: '19' },
    },
  },
])

export default eslintConfig
