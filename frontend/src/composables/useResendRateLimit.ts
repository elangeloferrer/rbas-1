import { onUnmounted, ref } from 'vue'

export function formatCountdown(seconds: number): string {
  if (seconds >= 3600) {
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    return m > 0 ? `${h}h ${m}m` : `${h}h`
  }
  if (seconds >= 60) {
    const m = Math.floor(seconds / 60)
    const s = seconds % 60
    return s > 0 ? `${m}m ${s}s` : `${m}m`
  }
  return `${seconds}s`
}

export function useResendRateLimit() {
  const rateLimitSeconds = ref(0)
  let timer: ReturnType<typeof setInterval> | null = null

  function startCountdown(seconds: number) {
    if (timer) clearInterval(timer)
    rateLimitSeconds.value = seconds
    timer = setInterval(() => {
      rateLimitSeconds.value--
      if (rateLimitSeconds.value <= 0) {
        clearInterval(timer!)
        timer = null
      }
    }, 1000)
  }

  onUnmounted(() => {
    if (timer) clearInterval(timer)
  })

  return { rateLimitSeconds, startCountdown }
}
