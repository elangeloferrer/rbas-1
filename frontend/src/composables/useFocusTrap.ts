import type { Ref } from 'vue'

const FOCUSABLE =
  'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

export function useFocusTrap(containerRef: Ref<HTMLElement | null>) {
  function onTab(e: KeyboardEvent) {
    if (!containerRef.value) return
    const focusable = Array.from(containerRef.value.querySelectorAll<HTMLElement>(FOCUSABLE))
    if (focusable.length === 0) return
    const first = focusable[0]!
    const last = focusable[focusable.length - 1]!
    if (e.shiftKey) {
      if (document.activeElement === first) {
        e.preventDefault()
        last.focus()
      }
    } else {
      if (document.activeElement === last) {
        e.preventDefault()
        first.focus()
      }
    }
  }

  return { onTab }
}
