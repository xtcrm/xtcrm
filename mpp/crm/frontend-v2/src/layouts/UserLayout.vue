<template>
  <div class="login-layout">
    <canvas ref="bgCanvas" class="bg-canvas"></canvas>
    <div class="bg-grid"></div>
    <div class="bg-glow glow-1"></div>
    <div class="bg-glow glow-2"></div>
    <router-view />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const bgCanvas = ref(null)
let animId = null

onMounted(() => {
  const cvs = bgCanvas.value
  if (!cvs) return
  const ctx = cvs.getContext('2d')
  const particles = []
  const max = 40

  function resize() {
    cvs.width = window.innerWidth
    cvs.height = window.innerHeight
  }
  resize()
  window.addEventListener('resize', resize)

  for (let i = 0; i < max; i++) {
    particles.push({
      x: Math.random() * cvs.width,
      y: Math.random() * cvs.height,
      r: Math.random() * 1.2 + 0.4,
      vx: (Math.random() - 0.5) * 0.3,
      vy: (Math.random() - 0.5) * 0.3,
      o: Math.random() * 0.5 + 0.1,
    })
  }

  function draw() {
    ctx.clearRect(0, 0, cvs.width, cvs.height)
    for (const p of particles) {
      p.x += p.vx; p.y += p.vy
      if (p.x < 0) p.x = cvs.width
      if (p.x > cvs.width) p.x = 0
      if (p.y < 0) p.y = cvs.height
      if (p.y > cvs.height) p.y = 0
      ctx.beginPath()
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2)
      ctx.fillStyle = `rgba(214,137,59,${p.o})`
      ctx.fill()
    }
    // draw connections
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x
        const dy = particles[i].y - particles[j].y
        const dist = Math.sqrt(dx * dx + dy * dy)
        if (dist < 120) {
          ctx.beginPath()
          ctx.moveTo(particles[i].x, particles[i].y)
          ctx.lineTo(particles[j].x, particles[j].y)
          ctx.strokeStyle = `rgba(214,137,59,${0.06 * (1 - dist / 120)})`
          ctx.lineWidth = 0.5
          ctx.stroke()
        }
      }
    }
    animId = requestAnimationFrame(draw)
  }
  draw()

  onBeforeUnmount(() => {
    cancelAnimationFrame(animId)
    window.removeEventListener('resize', resize)
  })
})
</script>

<style scoped>
.login-layout {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: #0a0e14;
  position: relative; overflow: hidden;
}
.bg-canvas { position: absolute; inset: 0; z-index: 0; }
.bg-grid {
  position: absolute; inset: 0; z-index: 0;
  background-image:
    linear-gradient(rgba(214,137,59,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(214,137,59,.03) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: radial-gradient(ellipse 60% 50% at 50% 50%, black 20%, transparent 70%);
}
.bg-glow {
  position: absolute; border-radius: 50%; filter: blur(80px); z-index: 0; opacity: .15;
}
.glow-1 { width: 500px; height: 500px; background: radial-gradient(circle, #d6893b, transparent); top: -15%; left: -10%; }
.glow-2 { width: 400px; height: 400px; background: radial-gradient(circle, #b8860b, transparent); bottom: -10%; right: -8%; }
</style>
