<script lang="ts">
  import { useForm } from '@inertiajs/svelte';

  const form = useForm({
    password: '',
    password_confirmation: '',
  });

  function submit(e: SubmitEvent) {
    e.preventDefault();
    form.post('/account/change-password');
  }
</script>

<svelte:head>
  <title>Cambiar contraseña — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#081B6A] flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-14 h-14 bg-[#3554FF] mb-4">
        <span class="text-white text-xl font-black tracking-tighter">Q26</span>
      </div>
      <h1 class="text-white font-black text-2xl tracking-tight">Quiniela 2026</h1>
      <p class="text-white/40 text-sm mt-1">Cambio de contraseña requerido</p>
    </div>

    <!-- Card -->
    <div class="bg-white p-8">
      <p class="text-sm text-[#6B7280] mb-6 text-center">
        Por seguridad, debes cambiar tu contraseña antes de continuar.
      </p>

      <!-- Security disclaimer -->
      <div class="bg-[#F0F4FF] border border-[#3554FF]/20 px-4 py-4 mb-6">
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#3554FF] mb-2">Aviso de seguridad</p>
        <p class="text-xs text-[#374151] leading-relaxed">
          Tu nueva contraseña será almacenada de forma cifrada y segura. Ningún usuario podrá visualizarla ni acceder a ella. Por tu seguridad, no compartas tu contraseña con nadie.
        </p>
      </div>

      <form onsubmit={submit} class="space-y-5">
        {#if form.errors.password}
          <div class="bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {form.errors.password}
          </div>
        {/if}

        <div>
          <label for="password" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
            Nueva contraseña
          </label>
          <input
            id="password"
            type="password"
            autocomplete="new-password"
            bind:value={form.password}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
              focus:outline-none focus:border-[#3554FF] transition-colors
              {form.errors.password ? 'border-red-400' : ''}"
          />
        </div>

        <div>
          <label for="password_confirmation" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
            Confirmar contraseña
          </label>
          <input
            id="password_confirmation"
            type="password"
            autocomplete="new-password"
            bind:value={form.password_confirmation}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
              focus:outline-none focus:border-[#3554FF] transition-colors"
          />
          {#if form.errors.password_confirmation}
            <p class="text-xs text-red-600 mt-1">{form.errors.password_confirmation}</p>
          {/if}
        </div>

        <button
          type="submit"
          disabled={form.processing}
          class="w-full bg-[#3554FF] text-white font-black text-sm py-3 tracking-widest uppercase
            hover:bg-[#2340cc] transition-colors disabled:opacity-60"
        >
          {form.processing ? 'Guardando...' : 'Cambiar contraseña'}
        </button>
      </form>
    </div>

    <div class="text-center mt-6">
      <form method="post" action="/logout" class="inline">
        <button type="submit" class="text-white/40 text-xs hover:text-white/70 transition-colors">
          Cerrar sesión
        </button>
      </form>
    </div>
  </div>
</div>
