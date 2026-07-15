import { ref, watch } from 'vue'

const isDark = ref(
  localStorage.getItem('color-mode') === 'dark' ||
    (!localStorage.getItem('color-mode') &&
      window.matchMedia('(prefers-color-scheme: dark)').matches),
)

watch(
  isDark,
  (val) => {
    localStorage.setItem('color-mode', val ? 'dark' : 'light')
    document.documentElement.classList.toggle('dark', val)
  },
  { immediate: true },
)

export function useColorMode() {
  return {
    isDark,
    toggle: () => {
      isDark.value = !isDark.value
    },
  }
}
