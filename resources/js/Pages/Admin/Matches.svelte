<script lang="ts">
  import { useForm, usePage } from '@inertiajs/svelte';
  import { STAGE_LABELS, STAGE_ORDER } from '../../lib/utils';

  type Match = {
    id: number;
    correlativo: number;
    home_team: string;
    home_team_flag: string | null;
    away_team: string;
    away_team_flag: string | null;
    match_date: string;
    venue: string | null;
    group_name: string | null;
    stage: string;
    status: string;
    external_id: string | null;
    closes_at: string | null;
  };

  let { matches }: { matches: Match[] } = $props();

  const page = usePage();
  const isSuperAdmin = $derived((page.props.auth as any)?.user?.role === 'super_admin');

  // ── Stage filter ─────────────────────────────────────────────
  let filterStage = $state('all');

  const STAGE_IDX = Object.fromEntries(STAGE_ORDER.map((s, i) => [s, i]));

  const presentStages = $derived(
    [...new Set(matches.map(m => m.stage))].sort(
      (a, b) => (STAGE_IDX[a] ?? 99) - (STAGE_IDX[b] ?? 99)
    )
  );

  const filtered = $derived(
    filterStage === 'all' ? matches : matches.filter(m => m.stage === filterStage)
  );

  // ── Edit form ─────────────────────────────────────────────────
  let editingId = $state<number | null>(null);
  let editDate  = $state('');
  let editTime  = $state('');

  const form = useForm({
    home_team:      '',
    home_team_flag: '',
    away_team:      '',
    away_team_flag: '',
    match_date:     '',
    venue:          '',
    external_id:    '',
    stage:          '',
    group_name:     '',
  });

  function startEdit(match: Match) {
    if (editingId === match.id) { editingId = null; return; }
    const [d, t] = match.match_date.split(' ');
    editDate            = d;
    editTime            = t.slice(0, 5);
    form.home_team      = match.home_team;
    form.home_team_flag = match.home_team_flag ?? '';
    form.away_team      = match.away_team;
    form.away_team_flag = match.away_team_flag ?? '';
    form.match_date     = match.match_date;
    form.venue          = match.venue ?? '';
    form.external_id    = match.external_id ?? '';
    form.stage          = match.stage;
    form.group_name     = match.group_name ?? '';
    form.clearErrors();
    editingId = match.id;
  }

  function saveMatch(match: Match) {
    form.match_date = editDate + ' ' + editTime + ':00';
    form.put(`/admin/matches/${match.id}`, {
      onSuccess: () => { editingId = null; },
    });
  }

  // ── Create panel ──────────────────────────────────────────────
  let createOpen = $state(false);
  let createDate = $state('');
  let createTime = $state('');

  const createForm = useForm({
    home_team:      '',
    home_team_flag: '',
    away_team:      '',
    away_team_flag: '',
    match_date:     '',
    venue:          '',
    stage:          'group' as string,
    group_name:     '',
    external_id:    '',
  });

  function openCreate() {
    createForm.reset();
    createForm.clearErrors();
    createDate = '';
    createTime = '';
    createOpen = true;
  }

  function closeCreate() {
    createOpen = false;
    createForm.reset();
    createForm.clearErrors();
  }

  function submitCreate(e: SubmitEvent) {
    e.preventDefault();
    createForm.match_date = createDate + ' ' + createTime + ':00';
    createForm.post('/admin/matches', {
      onSuccess: closeCreate,
    });
  }

  // ── Helpers ───────────────────────────────────────────────────
  function statusBadge(status: string) {
    if (status === 'finished') return 'bg-[#081B6A] text-white';
    if (status === 'live')     return 'bg-emerald-500 text-white';
    return 'bg-[#F0F0F0] text-[#9CA3AF]';
  }

  function statusLabel(status: string) {
    if (status === 'finished') return 'Finalizado';
    if (status === 'live')     return 'En vivo';
    return 'Por jugar';
  }

  function formatMatchDate(svStr: string) {
    const [date, time] = svStr.split(' ');
    const [y, m, d] = date.split('-').map(Number);
    const [h, min] = time.split(':');
    const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${d} ${months[m - 1]} · ${h}:${min}`;
  }

  function phaseLabel(match: Match): string {
    return match.group_name ?? (STAGE_LABELS[match.stage] ?? match.stage);
  }

  function canEdit(match: Match): boolean {
    if (isSuperAdmin) return true;
    return match.closes_at != null && new Date(match.closes_at) > new Date();
  }

  const stages = STAGE_ORDER.map(s => ({ value: s, label: STAGE_LABELS[s] ?? s }));
</script>

<svelte:head>
  <title>Partidos — Admin Quiniela 2026</title>
</svelte:head>

<div class="min-h-screen bg-[#F6F6F6]">
  <!-- Nav -->
  <nav class="bg-[#081B6A] text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
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
          <span class="text-[#3554FF]">Partidos</span>
          {#if isSuperAdmin}
            <a href="/admin/users" class="text-white/60 hover:text-white transition-colors">Usuarios</a>
          {/if}
        </div>
      </div>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" value={page.props.csrf_token as string} />
        <button type="submit" class="text-xs text-white/50 hover:text-white transition-colors font-semibold">Salir</button>
      </form>
    </div>
  </nav>

  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-end justify-between mb-8">
      <div>
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
        <h1 class="text-3xl font-black text-[#081B6A]">Partidos</h1>
      </div>
      <button
        onclick={openCreate}
        class="bg-[#3554FF] text-white text-xs font-black tracking-widest uppercase px-5 py-3 hover:bg-[#2340cc] transition-colors"
      >
        + Nuevo partido
      </button>
    </div>

    <!-- Flash -->
    {#if (page.props.flash as any)?.success}
      <div class="mb-6 bg-emerald-50 border border-emerald-200 px-5 py-4 text-emerald-800 text-sm font-semibold">
        {(page.props.flash as any).success}
      </div>
    {/if}
    {#if (page.props.flash as any)?.error}
      <div class="mb-6 bg-red-50 border border-red-200 px-5 py-4 text-red-800 text-sm font-semibold">
        {(page.props.flash as any).error}
      </div>
    {/if}

    <!-- Stage filter -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button
        onclick={() => filterStage = 'all'}
        class="px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-colors
          {filterStage === 'all' ? 'bg-[#081B6A] text-white' : 'bg-white border border-[#E0E0E0] text-[#9CA3AF] hover:border-[#081B6A] hover:text-[#081B6A]'}"
      >
        Todos
      </button>
      {#each presentStages as s}
        <button
          onclick={() => filterStage = s}
          class="px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-colors
            {filterStage === s ? 'bg-[#081B6A] text-white' : 'bg-white border border-[#E0E0E0] text-[#9CA3AF] hover:border-[#081B6A] hover:text-[#081B6A]'}"
        >
          {STAGE_LABELS[s] ?? s}
        </button>
      {/each}
    </div>

    <!-- Table -->
    <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#E0E0E0]">
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] w-10">#</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Local</th>
            <th class="px-4 py-3 text-center text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden sm:table-cell">vs</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Visitante</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden md:table-cell">Fecha (SV)</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden lg:table-cell">Fase/Grupo</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden lg:table-cell">Estado</th>
            <th class="px-4 py-3 text-right text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]"></th>
          </tr>
        </thead>
        <tbody>
          {#each filtered as match (match.id)}
            <tr class="border-b border-[#F0F0F0] hover:bg-[#FAFAFA] transition-colors {editingId === match.id ? 'bg-[#F0F4FF]' : ''}">
              <td class="px-4 py-3 text-[#9CA3AF] font-mono text-xs">{match.correlativo}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="text-base leading-none">{match.home_team_flag ?? ''}</span>
                  <span class="font-semibold text-[#081B6A]">{match.home_team}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-center text-[#D1D5DB] font-bold hidden sm:table-cell">vs</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="text-base leading-none">{match.away_team_flag ?? ''}</span>
                  <span class="font-semibold text-[#081B6A]">{match.away_team}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-[#6B7280] text-xs hidden md:table-cell whitespace-nowrap">
                {formatMatchDate(match.match_date)}
              </td>
              <td class="px-4 py-3 hidden lg:table-cell">
                <span class="text-xs text-[#6B7280]">{phaseLabel(match)}</span>
              </td>
              <td class="px-4 py-3 hidden lg:table-cell">
                <span class="inline-block px-2 py-0.5 text-[9px] font-black tracking-widest uppercase {statusBadge(match.status)}">
                  {statusLabel(match.status)}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                {#if canEdit(match)}
                  <button
                    onclick={() => startEdit(match)}
                    class="text-xs font-bold transition-colors
                      {editingId === match.id ? 'text-[#9CA3AF] hover:text-red-500' : 'text-[#3554FF] hover:text-[#2340cc]'}"
                  >
                    {editingId === match.id ? 'Cancelar' : 'Editar'}
                  </button>
                {:else}
                  <span class="text-[10px] text-[#D1D5DB] italic">Solo superadmin</span>
                {/if}
              </td>
            </tr>

            <!-- Inline edit row -->
            {#if editingId === match.id}
              <tr class="border-b border-[#3554FF]/20">
                <td colspan="8" class="px-0 py-0">
                  <div class="bg-[#F0F4FF] border-l-4 border-[#3554FF] px-6 py-6">
                    <p class="text-[10px] font-bold tracking-widest uppercase text-[#3554FF] mb-4">
                      Editando partido #{match.correlativo}
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Fecha (hora SV)</label>
                        <input type="date" bind:value={editDate}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Hora (hora SV)</label>
                        <input type="time" bind:value={editTime}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Sede</label>
                        <input type="text" bind:value={form.venue}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Equipo local</label>
                        <input type="text" bind:value={form.home_team}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] {form.errors.home_team ? 'border-red-400' : ''}" />
                        {#if form.errors.home_team}<p class="text-xs text-red-600 mt-1">{form.errors.home_team}</p>{/if}
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Bandera local (emoji)</label>
                        <input type="text" bind:value={form.home_team_flag} maxlength="10" placeholder="🇲🇽"
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Equipo visitante</label>
                        <input type="text" bind:value={form.away_team}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] {form.errors.away_team ? 'border-red-400' : ''}" />
                        {#if form.errors.away_team}<p class="text-xs text-red-600 mt-1">{form.errors.away_team}</p>{/if}
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Bandera visitante (emoji)</label>
                        <input type="text" bind:value={form.away_team_flag} maxlength="10" placeholder="🇧🇷"
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">ID externo (API)</label>
                        <input type="text" bind:value={form.external_id} placeholder="ej. 42"
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]
                            {form.errors.external_id ? 'border-red-400' : ''}" />
                        {#if form.errors.external_id}<p class="text-xs text-red-600 mt-1">{form.errors.external_id}</p>{/if}
                      </div>
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Fase</label>
                        <select bind:value={form.stage}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]">
                          {#each stages as s}
                            <option value={s.value}>{s.label}</option>
                          {/each}
                        </select>
                        {#if form.errors.stage}<p class="text-xs text-red-600 mt-1">{form.errors.stage}</p>{/if}
                      </div>
                      {#if form.stage === 'group'}
                        <div>
                          <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Grupo</label>
                          <input type="text" bind:value={form.group_name} placeholder="Grupo A"
                            class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]
                              {form.errors.group_name ? 'border-red-400' : ''}" />
                          {#if form.errors.group_name}<p class="text-xs text-red-600 mt-1">{form.errors.group_name}</p>{/if}
                        </div>
                      {/if}
                    </div>

                    {#if form.errors.match_date}
                      <p class="text-xs text-red-600 mt-3">{form.errors.match_date}</p>
                    {/if}

                    <div class="flex gap-3 mt-5">
                      <button
                        onclick={() => saveMatch(match)}
                        disabled={form.processing}
                        class="bg-[#3554FF] text-white font-black text-xs tracking-widest uppercase px-6 py-2.5 hover:bg-[#2340cc] transition-colors disabled:opacity-50"
                      >
                        {form.processing ? 'Guardando...' : 'Guardar cambios'}
                      </button>
                      <button
                        onclick={() => { editingId = null; }}
                        class="border border-[#E0E0E0] bg-white text-[#6B7280] font-bold text-xs px-5 py-2.5 hover:border-[#081B6A] hover:text-[#081B6A] transition-colors"
                      >
                        Cancelar
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            {/if}
          {:else}
            <tr>
              <td colspan="8" class="px-5 py-12 text-center text-[#9CA3AF]">No hay partidos</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Create slide-over panel -->
{#if createOpen}
  <div class="fixed inset-0 bg-black/40 z-40" onclick={closeCreate} role="presentation"></div>

  <div class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col">
    <div class="bg-[#081B6A] text-white px-6 py-5 flex items-center justify-between shrink-0">
      <h2 class="font-black text-base tracking-wide">Nuevo partido</h2>
      <button onclick={closeCreate} class="text-white/60 hover:text-white text-xl leading-none">✕</button>
    </div>

    <form onsubmit={submitCreate} class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
      <!-- Stage -->
      <div>
        <label for="c-stage" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Fase</label>
        <select id="c-stage" bind:value={createForm.stage}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] bg-white focus:outline-none focus:border-[#3554FF]">
          {#each stages as s}
            <option value={s.value}>{s.label}</option>
          {/each}
        </select>
        {#if createForm.errors.stage}<p class="text-xs text-red-600 mt-1">{createForm.errors.stage}</p>{/if}
      </div>

      <!-- Group name (only for group stage) -->
      {#if createForm.stage === 'group'}
        <div>
          <label for="c-group" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Nombre del grupo</label>
          <input id="c-group" type="text" bind:value={createForm.group_name} placeholder="Grupo A"
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] {createForm.errors.group_name ? 'border-red-400' : ''}" />
          {#if createForm.errors.group_name}<p class="text-xs text-red-600 mt-1">{createForm.errors.group_name}</p>{/if}
        </div>
      {/if}

      <!-- Date -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="c-date" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Fecha (SV)</label>
          <input id="c-date" type="date" bind:value={createDate}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
        </div>
        <div>
          <label for="c-time" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Hora (SV)</label>
          <input id="c-time" type="time" bind:value={createTime}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
        </div>
      </div>
      {#if createForm.errors.match_date}<p class="text-xs text-red-600 -mt-3">{createForm.errors.match_date}</p>{/if}

      <!-- Home team -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="c-home" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Equipo local</label>
          <input id="c-home" type="text" bind:value={createForm.home_team}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] {createForm.errors.home_team ? 'border-red-400' : ''}" />
          {#if createForm.errors.home_team}<p class="text-xs text-red-600 mt-1">{createForm.errors.home_team}</p>{/if}
        </div>
        <div>
          <label for="c-home-flag" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Bandera (emoji)</label>
          <input id="c-home-flag" type="text" bind:value={createForm.home_team_flag} maxlength="10" placeholder="🇲🇽"
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
        </div>
      </div>

      <!-- Away team -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="c-away" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Equipo visitante</label>
          <input id="c-away" type="text" bind:value={createForm.away_team}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF] {createForm.errors.away_team ? 'border-red-400' : ''}" />
          {#if createForm.errors.away_team}<p class="text-xs text-red-600 mt-1">{createForm.errors.away_team}</p>{/if}
        </div>
        <div>
          <label for="c-away-flag" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Bandera (emoji)</label>
          <input id="c-away-flag" type="text" bind:value={createForm.away_team_flag} maxlength="10" placeholder="🇧🇷"
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
        </div>
      </div>

      <!-- Venue -->
      <div>
        <label for="c-venue" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">Sede (opcional)</label>
        <input id="c-venue" type="text" bind:value={createForm.venue}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]" />
      </div>

      <!-- External ID -->
      <div>
        <label for="c-ext-id" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">ID externo (API)</label>
        <input id="c-ext-id" type="text" bind:value={createForm.external_id} placeholder="ej. 42"
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] focus:outline-none focus:border-[#3554FF]
            {createForm.errors.external_id ? 'border-red-400' : ''}" />
        {#if createForm.errors.external_id}
          <p class="text-xs text-red-600 mt-1">{createForm.errors.external_id}</p>
        {/if}
        <p class="text-[10px] text-[#9CA3AF] mt-1">Requerido para sincronización automática de resultados.</p>
      </div>
    </form>

    <div class="px-6 py-4 border-t border-[#E0E0E0] flex gap-3 shrink-0">
      <button
        onclick={closeCreate}
        class="flex-1 border border-[#E0E0E0] text-[#6B7280] font-bold text-sm py-2.5 hover:border-[#081B6A] hover:text-[#081B6A] transition-colors"
      >
        Cancelar
      </button>
      <button
        onclick={(e) => submitCreate(e as unknown as SubmitEvent)}
        disabled={createForm.processing}
        class="flex-1 bg-[#3554FF] text-white font-black text-sm py-2.5 hover:bg-[#2340cc] transition-colors disabled:opacity-50"
      >
        {createForm.processing ? 'Creando...' : 'Crear partido'}
      </button>
    </div>
  </div>
{/if}
