import antfu from '@antfu/eslint-config'
import prettier from 'eslint-config-prettier'

export default antfu({
  vue: true,
  typescript: true,
  stylistic: false, // Prettier owns all formatting — disable ESLint stylistic rules
  rules: {
    'no-console': ['warn', { allow: ['warn', 'error'] }], // Allow console.warn and console.error; disallow console.log in production code
    'vue/component-name-in-template-casing': ['error', 'PascalCase'], // Enforce consistent component name casing in templates
    'antfu/if-newline': 'off',
  },
}).append(prettier) // Belt-and-suspenders: disable any remaining rules that conflict with Prettier
