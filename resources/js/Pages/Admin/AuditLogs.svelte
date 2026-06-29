<script lang="ts">
  import { router, usePage } from '@inertiajs/svelte';

  type LogEntry = {
    id: number;
    user_name: string;
    user_role: string | null;
    action: string;
    description: string | null;
    resource_type: string | null;
    resource_id: number | null;
    ip_address: string | null;
    created_at: string;
  };

  type Paginator = {
    data: LogEntry[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
  };

  let {
    logs,
    filters,
  }: {
    logs: Paginator;
    filters: { category: string; user: string };
  } = $props();

  const page = usePage();
  const isSuperAdmin = $derived((page.props.auth as any)?.user?.role === 'super_admin');

  let filterCategory = $state(filters.category);
  let filterUser     = $state(filters.user);
  let debounceTimer: ReturnType<typeof setTimeout>;

  const categories = [
    { value: '',           label: 'Todas las categorías' },
    { value: 'auth',       label: 'Autenticación' },
    { value: 'account',    label: 'Cuenta' },
    { value: 'user',       label: 'Usuarios' },
    { value: 'match',      label: 'Partidos' },
    { value: 'sync',       label: 'Sincronización' },
    { value: 'import',     label: 'Importación' },
    { value: 'prediction', label: 'Pronósticos' },
  ];

  function applyFilters() {
    router.get('/admin/audit-logs', { category: filterCategory, user: filterUser }, {
      preserveScroll: true,
      replace: true,
    });
  }

  function onCategoryChange() {
    applyFilters();
  }

  function onUserInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
  }

  function goToPage(url: string | null) {
    if (url) router.visit(url, { preserveScroll: true });
  }

  function actionBadge(action: string): { bg: string; text: string; label: string } {
    const labels: Record<string, string> = {
      'auth.login':          'Login',
      'auth.login_failed':   'Login fallido',
      'auth.logout':         'Logout',
      'account.password_changed': 'Cambio contraseña',
      'user.created':        'Usuario creado',
      'user.updated':        'Usuario actualizado',
      'user.deleted':        'Usuario eliminado',
      'user.password_reset': 'Reset contraseña',
      'user.bulk_created':   'Usuarios masivos',
      'match.created':       'Partido creado',
      'match.updated':       'Partido actualizado',
      'sync.triggered':      'Sincronización',
      'import.completed':    'Importación OK',
      'import.failed':       'Importación fallida',
      'prediction.saved':    'Pronósticos',
    };

    const prefix = action.split('.')[0];
    const colors: Record<string, { bg: string; text: string }> = {
      auth:       action.includes('failed') ? { bg: 'bg-red-100',    text: 'text-red-700'    } : { bg: 'bg-blue-100',   text: 'text-blue-700'   },
      account:    { bg: 'bg-amber-100',  text: 'text-amber-700'  },
      user:       action.includes('deleted') ? { bg: 'bg-red-100', text: 'text-red-700' } : { bg: 'bg-purple-100', text: 'text-purple-700' },
      match:      { bg: 'bg-teal-100',   text: 'text-teal-700'   },
      sync:       { bg: 'bg-orange-100', text: 'text-orange-700' },
      import:     action.includes('failed') ? { bg: 'bg-red-100', text: 'text-red-700' } : { bg: 'bg-indigo-100', text: 'text-indigo-700' },
      prediction: { bg: 'bg-emerald-100', text: 'text-emerald-700' },
    };

    const color = colors[prefix] ?? { bg: 'bg-gray-100', text: 'text-gray-600' };

    return { ...color, label: labels[action] ?? action };
  }

  function roleBadge(role: string | null): string {
    if (role === 'super_admin') return 'bg-[#081B6A] text-white';
    if (role === 'admin')       return 'bg-[#3554FF] text-white';
    if (role === 'participant') return 'bg-gray-200 text-gray-700';
    return 'bg-gray-100 text-gray-500';
  }

  function roleLabel(role: string | null): string {
    if (role === 'super_admin') return 'Super Admin';
    if (role === 'admin')       return 'Admin';
    if (role === 'participant') return 'Participante';
    return '—';
  }

  function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('es-SV', {
      dateStyle: 'short',
      timeStyle: 'medium',
    }).format(new Date(iso));
  }
</script>

<svelte:head>
  <title>Auditoría — Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <!-- Nav -->
  <nav class="bg-[#081B6A] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
      <div class="flex items-center gap-6">
        <a href="/" class="flex items-center gap-2">
          <div class="w-7 h-7 bg-[#3554FF] flex items-center justify-center">
            <span class="text-white text-xs font-black">Q26</span>
          </div>
          <span class="font-black text-sm tracking-widest uppercase hidden sm:block">Quiniela 2026</span>
        </a>
        <span class="text-white/20 hidden sm:block">|</span>
        <div class="flex items-center gap-4 text-xs font-semibold tracking-wide">
          <a href="/admin" class="text-white/60 hover:text-white transition-colors">Dashboard</a>
          <a href="/admin/matches" class="text-white/60 hover:text-white transition-colors">Partidos</a>
          {#if isSuperAdmin}
            <a href="/admin/users" class="text-white/60 hover:text-white transition-colors">Usuarios</a>
            <span class="text-[#3554FF]">Auditoría</span>
          {/if}
        </div>
      </div>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" value={page.props.csrf_token as string} />
        <button type="submit" class="text-xs text-white/50 hover:text-white transition-colors font-semibold">Salir</button>
      </form>
    </div>
  </nav>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-6">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
      <div class="flex items-center justify-between">
        <h1 class="text-3xl font-black text-[#081B6A]">Auditoría</h1>
        <span class="text-sm text-[#9CA3AF]">{logs.total} registros en total</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-[#E0E0E0] p-4 mb-6 flex flex-wrap gap-3">
      <select
        bind:value={filterCategory}
        onchange={onCategoryChange}
        class="border border-[#E0E0E0] px-3 py-2 text-sm text-[#081B6A] bg-white focus:outline-none focus:border-[#3554FF] transition-colors"
      >
        {#each categories as cat}
          <option value={cat.value}>{cat.label}</option>
        {/each}
      </select>

      <input
        type="text"
        bind:value={filterUser}
        oninput={onUserInput}
        placeholder="Filtrar por usuario…"
        class="border border-[#E0E0E0] px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] transition-colors min-w-[200px]"
      />

      {#if filterCategory || filterUser}
        <button
          onclick={() => { filterCategory = ''; filterUser = ''; applyFilters(); }}
          class="text-xs text-[#9CA3AF] hover:text-[#081B6A] transition-colors px-2"
        >
          Limpiar filtros ×
        </button>
      {/if}
    </div>

    <!-- Table -->
    <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
      {#if logs.data.length === 0}
        <div class="text-center py-16">
          <p class="text-[#9CA3AF] text-sm">No hay registros de auditoría para los filtros seleccionados.</p>
        </div>
      {:else}
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[#E0E0E0] bg-[#FAFAFA]">
              <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] whitespace-nowrap">Fecha / Hora</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Usuario</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Acción</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Descripción</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] whitespace-nowrap">IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#F0F0F0]">
            {#each logs.data as log}
              {@const badge = actionBadge(log.action)}
              <tr class="hover:bg-[#FAFAFA] transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="font-mono text-xs text-[#6B7280]">{formatDate(log.created_at)}</span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <p class="font-semibold text-[#081B6A] text-sm leading-tight">{log.user_name}</p>
                  {#if log.user_role}
                    <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 mt-0.5 {roleBadge(log.user_role)}">
                      {roleLabel(log.user_role)}
                    </span>
                  {/if}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <span class="inline-block text-[10px] font-bold px-2 py-1 {badge.bg} {badge.text}">
                    {badge.label}
                  </span>
                </td>
                <td class="px-4 py-3 text-[#374151] max-w-sm">
                  <p class="text-xs leading-relaxed">{log.description ?? '—'}</p>
                  {#if log.resource_type && log.resource_id}
                    <p class="text-[10px] text-[#9CA3AF] mt-0.5">{log.resource_type} #{log.resource_id}</p>
                  {/if}
                </td>
                <td class="px-4 py-3">
                  <span class="font-mono text-xs text-[#9CA3AF]">{log.ip_address ?? '—'}</span>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      {/if}
    </div>

    <!-- Pagination -->
    {#if logs.last_page > 1}
      <div class="flex items-center justify-between mt-4">
        <p class="text-xs text-[#9CA3AF]">
          Página {logs.current_page} de {logs.last_page}
        </p>
        <div class="flex gap-1">
          {#each logs.links as link}
            {#if link.url !== null || link.active}
              <button
                onclick={() => goToPage(link.url)}
                disabled={!link.url}
                class="px-3 py-1.5 text-xs font-semibold border transition-colors
                  {link.active
                    ? 'bg-[#081B6A] text-white border-[#081B6A]'
                    : link.url
                      ? 'bg-white text-[#081B6A] border-[#E0E0E0] hover:border-[#081B6A]'
                      : 'bg-white text-[#D1D5DB] border-[#E0E0E0] cursor-not-allowed'}"
              >
                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                {@html link.label}
              </button>
            {/if}
          {/each}
        </div>
      </div>
    {/if}
  </div>
</div>
