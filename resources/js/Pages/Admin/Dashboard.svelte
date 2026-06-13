<script lang="ts">
  import { useForm, usePage } from '@inertiajs/svelte';

  let {
    stats,
  }: {
    stats: {
      participants: number;
      predictions: number;
      last_sync: string | null;
      last_import: string | null;
      import_result: { participants: number; predictions: number; skipped: number } | null;
    };
  } = $props();

  const page = usePage();

  const form = useForm({ file: null as File | null });

  let fileInput: HTMLInputElement;
  let fileName = $state('');

  function onFileChange(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) {
      form.file = f;
      fileName = f.name;
    }
  }

  function submit(e: SubmitEvent) {
    e.preventDefault();
    form.post('/admin/import', {
      forceFormData: true,
      onSuccess: () => {
        fileName = '';
        form.reset();
        if (fileInput) fileInput.value = '';
      },
    });
  }

  function formatDate(iso: string | null): string {
    if (!iso) return 'Nunca';
    return new Intl.DateTimeFormat('es-MX', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(iso));
  }
</script>

<svelte:head>
  <title>Admin — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <!-- Admin nav -->
  <nav class="bg-[#081B6A] text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
      <div class="flex items-center gap-6">
        <a href="/" class="flex items-center gap-2">
          <div class="w-7 h-7 bg-[#3554FF] flex items-center justify-center">
            <span class="text-white text-xs font-black">Q26</span>
          </div>
          <span class="font-black text-sm tracking-widest uppercase hidden sm:block">Quiniela 2026</span>
        </a>
        <span class="text-white/20 hidden sm:block">|</span>
        <div class="flex items-center gap-4 text-xs font-semibold tracking-wide">
          <span class="text-[#3554FF]">Dashboard</span>
          <a href="/admin/users" class="text-white/60 hover:text-white transition-colors">Usuarios</a>
        </div>
      </div>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" value={page.props.csrf_token as string} />
        <button type="submit" class="text-xs text-white/50 hover:text-white transition-colors font-semibold">
          Salir
        </button>
      </form>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
      <h1 class="text-3xl font-black text-[#081B6A]">Dashboard</h1>
    </div>

    <!-- Flash messages -->
    {#if page.props.flash?.success}
      <div class="mb-6 bg-emerald-50 border border-emerald-200 px-5 py-4 text-emerald-800 text-sm font-semibold">
        {page.props.flash.success}
      </div>
    {/if}
    {#if page.props.flash?.error}
      <div class="mb-6 bg-red-50 border border-red-200 px-5 py-4 text-red-800 text-sm font-semibold">
        {page.props.flash.error}
      </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Stats -->
      <div class="lg:col-span-1 space-y-4">
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Estado actual</p>

        <div class="bg-white border border-[#E0E0E0] p-5">
          <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Participantes</p>
          <p class="text-4xl font-black text-[#081B6A]">{stats.participants}</p>
        </div>
        <div class="bg-white border border-[#E0E0E0] p-5">
          <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Pronósticos</p>
          <p class="text-4xl font-black text-[#3554FF]">{stats.predictions}</p>
        </div>
        <div class="bg-white border border-[#E0E0E0] p-5 space-y-2">
          <div>
            <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Última sincronización</p>
            <p class="text-sm font-semibold text-[#081B6A]">{formatDate(stats.last_sync)}</p>
          </div>
          <div>
            <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Última importación</p>
            <p class="text-sm font-semibold text-[#081B6A]">{formatDate(stats.last_import)}</p>
          </div>
        </div>

        {#if stats.import_result}
          <div class="bg-emerald-50 border border-emerald-200 p-5">
            <p class="text-[10px] text-emerald-600 tracking-widest uppercase font-bold mb-2">Última importación</p>
            <p class="text-sm text-emerald-800"><strong>{stats.import_result.participants}</strong> participantes</p>
            <p class="text-sm text-emerald-800"><strong>{stats.import_result.predictions}</strong> pronósticos</p>
            {#if stats.import_result.skipped > 0}
              <p class="text-sm text-amber-700"><strong>{stats.import_result.skipped}</strong> omitidos</p>
            {/if}
          </div>
        {/if}

        <a href="/admin/users" class="block bg-[#081B6A] text-white text-center text-xs font-black tracking-widest uppercase py-3 hover:bg-[#0a2490] transition-colors">
          Gestionar usuarios →
        </a>
      </div>

      <!-- Import form -->
      <div class="lg:col-span-2">
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Importar participantes</p>

        <div class="bg-white border border-[#E0E0E0] p-8">
          <p class="text-sm text-[#6B7280] mb-6">
            Sube el archivo Excel con los pronósticos de los participantes.
            El sistema importará los participantes y sus predicciones automáticamente.
            Las filas ya existentes se actualizarán.
          </p>

          <form onsubmit={submit} class="space-y-6">
            <!-- File drop zone -->
            <div>
              <label
                for="file-input"
                class="flex flex-col items-center justify-center border-2 border-dashed border-[#E0E0E0]
                  p-10 cursor-pointer hover:border-[#3554FF] hover:bg-[#FAFAFE] transition-colors"
              >
                <svg class="w-10 h-10 text-[#D1D5DB] mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                {#if fileName}
                  <p class="text-sm font-bold text-[#3554FF]">{fileName}</p>
                  <p class="text-xs text-[#9CA3AF] mt-1">Haz clic para cambiar</p>
                {:else}
                  <p class="text-sm font-semibold text-[#081B6A]">Seleccionar archivo Excel</p>
                  <p class="text-xs text-[#9CA3AF] mt-1">.xlsx o .xls · máx. 10 MB</p>
                {/if}
              </label>
              <input
                id="file-input"
                type="file"
                accept=".xlsx,.xls"
                bind:this={fileInput}
                onchange={onFileChange}
                class="sr-only"
              />
              {#if $form.errors.file}
                <p class="text-xs text-red-600 mt-1">{$form.errors.file}</p>
              {/if}
            </div>

            <button
              type="submit"
              disabled={$form.processing || !$form.file}
              class="w-full bg-[#3554FF] text-white font-black text-sm py-3 tracking-widest uppercase
                hover:bg-[#2340cc] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {$form.processing ? 'Importando...' : 'Importar pronósticos'}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
