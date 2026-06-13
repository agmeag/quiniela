<script lang="ts">
  import { useForm } from '@inertiajs/svelte';

  const form = useForm({
    email: '',
    password: '',
    remember: false,
  });

  function submit(e: SubmitEvent) {
    e.preventDefault();
    form.post('/login');
  }
</script>

<svelte:head>
  <title>Iniciar sesión — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#081B6A] flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-14 h-14 bg-[#3554FF] mb-4">
        <span class="text-white text-xl font-black tracking-tighter">Q26</span>
      </div>
      <h1 class="text-white font-black text-2xl tracking-tight">Quiniela 2026</h1>
      <p class="text-white/40 text-sm mt-1">Panel de administración</p>
    </div>

    <!-- Card -->
    <form onsubmit={submit} class="bg-white p-8 space-y-5">
      {#if $form.errors.email}
        <div class="bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
          {$form.errors.email}
        </div>
      {/if}

      <div>
        <label for="email" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Correo electrónico
        </label>
        <input
          id="email"
          type="email"
          autocomplete="email"
          bind:value={$form.email}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] placeholder-[#D1D5DB]
            focus:outline-none focus:border-[#3554FF] transition-colors"
          placeholder="admin@ejemplo.com"
        />
      </div>

      <div>
        <label for="password" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Contraseña
        </label>
        <input
          id="password"
          type="password"
          autocomplete="current-password"
          bind:value={$form.password}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
            focus:outline-none focus:border-[#3554FF] transition-colors"
        />
      </div>

      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input type="checkbox" bind:checked={$form.remember} class="accent-[#3554FF]" />
        <span class="text-sm text-[#6B7280]">Recordarme</span>
      </label>

      <button
        type="submit"
        disabled={$form.processing}
        class="w-full bg-[#3554FF] text-white font-black text-sm py-3 tracking-widest uppercase
          hover:bg-[#2340cc] transition-colors disabled:opacity-60"
      >
        {$form.processing ? 'Ingresando...' : 'Ingresar'}
      </button>
    </form>

    <p class="text-center mt-6">
      <a href="/" class="text-white/40 text-xs hover:text-white/70 transition-colors">
        ← Volver al sitio
      </a>
    </p>
  </div>
</div>
