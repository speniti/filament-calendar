/** @type {import('stylelint').Config} */
export default {
  extends: ['stylelint-config-standard', 'stylelint-config-tailwindcss'],
  rules: {
    'at-rule-no-unknown': [
      true,
      { ignoreAtRules: ['apply', 'reference', 'utility'] },
    ],
    'at-rule-no-deprecated': [
      true,
      { ignoreAtRules: ['tailwind', 'apply', 'layer', 'config'] },
    ],
    'at-rule-prelude-no-invalid': [true, { ignoreAtRules: ['apply'] }],
    'selector-class-pattern': ['', {}],
  },
};
