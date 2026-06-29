<script lang="ts">
  import { useForm, usePage } from '@inertiajs/svelte';

  let {
    stats,
  }: {
    stats: {
      participants: number;
      predictions: number;
      participants_without_users: number;
      last_sync: string | null;
      last_import: string | null;
      import_result: { participants: number; predictions: number; skipped: number } | null;
    };
  } = $props();

  const page = usePage();

  const isSuperAdmin = $derived((page.props.auth as any)?.user?.role === 'super_admin');

  // ── Import form ───────────────────────────────────────────────
  const importForm = useForm({ file: null as File | null });
  let fileInput: HTMLInputElement;
  let fileName = $state('');

  function onFileChange(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) { importForm.file = f; fileName = f.name; }
  }

  function submitImport(e: SubmitEvent) {
    e.preventDefault();
    importForm.post('/admin/import', {
      forceFormData: true,
      onSuccess: () => { fileName = ''; importForm.reset(); if (fileInput) fileInput.value = ''; },
    });
  }

  // ── Export ────────────────────────────────────────────────────
  let exportStage = $state('group');

  const stageOptions = [
    { value: 'group',       label: 'Fase de grupos' },
    { value: 'round_of_32', label: 'Dieciseisavos de final' },
    { value: 'round_of_16', label: 'Octavos de final' },
    { value: 'quarter',     label: 'Cuartos de final' },
    { value: 'semi',        label: 'Semifinales' },
    { value: 'third_place', label: 'Tercer puesto' },
    { value: 'final',       label: 'Final' },
  ];

  // ── Bulk user creation ────────────────────────────────────────
  const bulkForm = useForm({ base_password: '' });

  function submitBulkCreate(e: SubmitEvent) {
    e.preventDefault();
    bulkForm.post('/admin/users/bulk-create', {
      onSuccess: () => { bulkForm.reset(); },
    });
  }

  // ── Sync ──────────────────────────────────────────────────────
  type SyncStatus = 'idle' | 'queued' | 'running';
  type SyncLog = { status: string; matches_fetched: number; matches_updated: number; duration_seconds: number; created_at: string } | null;

  let syncStatus = $state<SyncStatus>('idle');
  let syncLog    = $state<SyncLog>(null);
  let pollTimer: ReturnType<typeof setInterval> | null = null;

  async function triggerSync() {
    if (syncStatus !== 'idle') return;
    const res = await fetch('/admin/sync', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': page.props.csrf_token as string },
    });
    if (res.ok) {
      syncStatus = 'queued';
      startPolling();
    }
  }

  function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
      const res  = await fetch('/admin/sync/status');
      const data = await res.json();
      syncStatus = data.status as SyncStatus;
      syncLog    = data.last_sync;
      if (syncStatus === 'idle') {
        clearInterval(pollTimer!);
        pollTimer = null;
      }
    }, 3000);
  }

  function formatDate(iso: string | null): string {
    if (!iso) return 'Nunca';
    return new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso));
  }

  function syncLabel(status: SyncStatus) {
    if (status === 'queued')  return 'En cola…';
    if (status === 'running') return 'Sincronizando…';
    return 'Sincronizar + Recalcular';
  }
</script>

<svelte:head>
  <title>Admin — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <!-- Nav -->
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
          <a href="/admin/matches" class="text-white/60 hover:text-white transition-colors">Partidos</a>
          {#if isSuperAdmin}
            <a href="/admin/users" class="text-white/60 hover:text-white transition-colors">Usuarios</a>
            <a href="/admin/audit-logs" class="text-white/60 hover:text-white transition-colors">Auditoría</a>
          {/if}
        </div>
      </div>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" value={page.props.csrf_token as string} />
        <button type="submit" class="text-xs text-white/50 hover:text-white transition-colors font-semibold">Salir</button>
      </form>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
      <h1 class="text-3xl font-black text-[#081B6A]">Dashboard</h1>
    </div>

    <!-- Flash -->
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
      <!-- Left: Stats + actions -->
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
          {#if isSuperAdmin}
            <div>
              <p class="text-[10px] text-[#9CA3AF] tracking-widest uppercase">Última importación</p>
              <p class="text-sm font-semibold text-[#081B6A]">{formatDate(stats.last_import)}</p>
            </div>
          {/if}
        </div>

        {#if isSuperAdmin && stats.import_result}
          <div class="bg-emerald-50 border border-emerald-200 p-5">
            <p class="text-[10px] text-emerald-600 tracking-widest uppercase font-bold mb-2">Última importación</p>
            <p class="text-sm text-emerald-800"><strong>{stats.import_result.participants}</strong> participantes</p>
            <p class="text-sm text-emerald-800"><strong>{stats.import_result.predictions}</strong> pronósticos</p>
            {#if stats.import_result.skipped > 0}
              <p class="text-sm text-amber-700"><strong>{stats.import_result.skipped}</strong> omitidos</p>
            {/if}
          </div>
        {/if}

        <a href="/admin/matches" class="block bg-[#081B6A] text-white text-center text-xs font-black tracking-widest uppercase py-3 hover:bg-[#0a2490] transition-colors">
          Gestionar partidos →
        </a>
        {#if isSuperAdmin}
          <a href="/admin/users" class="block bg-white border border-[#E0E0E0] text-[#081B6A] text-center text-xs font-black tracking-widest uppercase py-3 hover:border-[#081B6A] transition-colors">
            Gestionar usuarios →
          </a>
        {/if}
      </div>

      <!-- Right: Sync + Import -->
      <div class="lg:col-span-2 space-y-8">

        <!-- ── Export module ── -->
        <div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Exportar predicciones</p>
          <div class="bg-white border border-[#E0E0E0] p-8">
            <p class="text-sm text-[#6B7280] mb-5">
              Descarga un Excel con todas las predicciones de una fase — una fila por participante,
              una columna por partido, con colores según resultado.
            </p>
            <div class="space-y-4">
              <div>
                <label for="export-stage" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                  Fase
                </label>
                <select
                  id="export-stage"
                  bind:value={exportStage}
                  class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] bg-white
                    focus:outline-none focus:border-[#3554FF] transition-colors"
                >
                  {#each stageOptions as opt}
                    <option value={opt.value}>{opt.label}</option>
                  {/each}
                </select>
              </div>
              <a
                href="/admin/export/predictions?stage={exportStage}"
                download
                class="flex items-center justify-center gap-2 w-full bg-[#081B6A] text-white font-black text-sm py-3
                  tracking-widest uppercase hover:bg-[#0a2490] transition-colors"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar Excel
              </a>
            </div>
          </div>
        </div>

        <!-- ── Sync module ── -->
        <div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Sincronización y recálculo</p>
          <div class="bg-white border border-[#E0E0E0] p-8">
            <p class="text-sm text-[#6B7280] mb-1">
              Obtiene los resultados actuales desde la API y recalcula todos los puntos y el marcador.
              Equivale a <code class="bg-[#F0F0F0] px-1 text-xs">quiniela:sync --recalculate</code>.
            </p>
            <p class="text-xs text-[#9CA3AF] mb-6">Usa este botón para correcciones manuales o cuando la sincronización automática falle.</p>

            <!-- Status indicator -->
            {#if syncStatus !== 'idle'}
              <div class="flex items-center gap-3 mb-5 bg-[#F0F4FF] border border-[#3554FF]/20 px-4 py-3">
                <div class="w-3 h-3 rounded-full bg-[#3554FF] animate-pulse shrink-0"></div>
                <p class="text-sm font-semibold text-[#3554FF]">
                  {syncStatus === 'queued' ? 'En cola — esperando worker…' : 'Sincronizando y recalculando puntos…'}
                </p>
              </div>
            {/if}

            {#if syncLog && syncStatus === 'idle'}
              <div class="mb-5 bg-[#F6F6F6] border border-[#E0E0E0] px-4 py-3 text-xs text-[#6B7280] space-y-0.5">
                <p><span class="font-bold text-[#081B6A]">Última ejecución:</span> {formatDate(syncLog.created_at)}</p>
                <p><span class="font-bold">Partidos obtenidos:</span> {syncLog.matches_fetched} · <span class="font-bold">Actualizados:</span> {syncLog.matches_updated} · <span class="font-bold">Duración:</span> {syncLog.duration_seconds?.toFixed(1)}s</p>
              </div>
            {/if}

            <button
              onclick={triggerSync}
              disabled={syncStatus !== 'idle'}
              class="w-full bg-[#3554FF] text-white font-black text-sm py-3 tracking-widest uppercase
                hover:bg-[#2340cc] transition-colors disabled:opacity-50 disabled:cursor-not-allowed
                flex items-center justify-center gap-3"
            >
              {#if syncStatus !== 'idle'}
                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {/if}
              {syncLabel(syncStatus)}
            </button>
          </div>
        </div>

        <!-- ── Bulk user creation (super_admin only) ── -->
        {#if isSuperAdmin}
          <div>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Crear cuentas de usuario</p>
            <div class="bg-white border border-[#E0E0E0] p-8">
              <p class="text-sm text-[#6B7280] mb-1">
                Crea una cuenta para cada participante que tenga email registrado pero aún no tenga usuario.
              </p>
              {#if stats.participants_without_users > 0}
                <p class="text-sm font-bold text-[#081B6A] mb-5">
                  {stats.participants_without_users} participante{stats.participants_without_users !== 1 ? 's' : ''} sin cuenta
                </p>
              {:else}
                <p class="text-sm text-emerald-600 font-semibold mb-5">Todos los participantes con email ya tienen cuenta.</p>
              {/if}
              <form onsubmit={submitBulkCreate} class="space-y-4">
                <div>
                  <label for="bulk-pwd" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                    Contraseña base
                  </label>
                  <input
                    id="bulk-pwd"
                    type="password"
                    bind:value={bulkForm.base_password}
                    placeholder="Mínimo 8 caracteres"
                    class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
                      focus:outline-none focus:border-[#3554FF] transition-colors
                      {bulkForm.errors.base_password ? 'border-red-400' : ''}"
                  />
                  {#if bulkForm.errors.base_password}
                    <p class="text-xs text-red-600 mt-1">{bulkForm.errors.base_password}</p>
                  {/if}
                  <p class="text-[10px] text-[#9CA3AF] mt-1">
                    Los usuarios deberán cambiarla en el primer inicio de sesión.
                  </p>
                </div>
                <button
                  type="submit"
                  disabled={bulkForm.processing || stats.participants_without_users === 0}
                  class="w-full bg-[#081B6A] text-white font-black text-sm py-3 tracking-widest uppercase
                    hover:bg-[#0a2490] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {bulkForm.processing ? 'Creando cuentas...' : 'Crear cuentas de usuario'}
                </button>
              </form>
            </div>
          </div>
        {/if}

        <!-- ── Import (super_admin only) ── -->
        {#if isSuperAdmin}
          <div>
            <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-4">Importar participantes</p>
            <div class="bg-white border border-[#E0E0E0] p-8">
              <p class="text-sm text-[#6B7280] mb-6">
                Sube el archivo Excel con los pronósticos. El sistema importará participantes y predicciones.
                Las filas ya existentes se actualizarán.
              </p>
              <form onsubmit={submitImport} class="space-y-6">
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
                  <input id="file-input" type="file" accept=".xlsx,.xls" bind:this={fileInput} onchange={onFileChange} class="sr-only" />
                  {#if importForm.errors.file}
                    <p class="text-xs text-red-600 mt-1">{importForm.errors.file}</p>
                  {/if}
                </div>
                <button
                  type="submit"
                  disabled={importForm.processing || !importForm.file}
                  class="w-full bg-[#081B6A] text-white font-black text-sm py-3 tracking-widest uppercase
                    hover:bg-[#0a2490] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {importForm.processing ? 'Importando...' : 'Importar pronósticos'}
                </button>
              </form>
            </div>
          </div>
        {/if}

      </div>
    </div>
  </div>
</div>
