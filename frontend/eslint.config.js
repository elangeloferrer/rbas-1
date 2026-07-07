import antfu from '@antfu/eslint-config'

export default antfu({
  vue: true,
  typescript: true,
  formatters: {
    css: true,
    html: true,
  },
  rules: {
    'no-console': ['warn', { allow: ['warn', 'error'] }], // Allow console.warn and console.error; disallow console.log in production code
    'vue/component-name-in-template-casing': ['error', 'PascalCase'], // Enforce consistent component name casing in templates
  },
})