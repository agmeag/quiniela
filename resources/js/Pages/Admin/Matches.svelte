<script lang="ts">
  import { useForm, usePage } from '@inertiajs/svelte';

  type Match = {
    id: number;
    correlativo: number;
    home_team: string;
    home_team_flag: string | null;
    away_team: string;
    away_team_flag: string | null;
    match_date: string;
    venue: string | null;
    group_name: string;
    stage: string;
    status: string;
  };

  let { matches }: { matches: Match[] } = $props();

  const page = usePage();

  let filterGroup = $state('all');
  let editingId   = $state<number | null>(null);
  let editDate    = $state('');
  let editTime    = $state('');

  const form = useForm({
    home_team:      '',
    home_team_flag: '',
    away_team:      '',
    away_team_flag: '',
    match_date:     '',
    venue:          '',
  });

  const groups = $derived([...new Set(matches.map(m => m.group_name))].sort());
  const filtered = $derived(
    filterGroup === 'all' ? matches : matches.filter(m => m.group_name === filterGroup)
  );

  function startEdit(match: Match) {
    if (editingId === match.id) {
      editingId = null;
      return;
    }
    const [d, t] = match.match_date.split(' ');
    editDate            = d;
    editTime            = t.slice(0, 5);
    form.home_team      = match.home_team;
    form.home_team_flag = match.home_team_flag ?? '';
    form.away_team      = match.away_team;
    form.away_team_flag = match.away_team_flag ?? '';
    form.match_date     = match.match_date;
    form.venue          = match.venue ?? '';
    form.clearErrors();
    editingId = match.id;
  }

  function saveMatch(match: Match) {
    form.match_date = editDate + ' ' + editTime + ':00';
    form.put(`/admin/matches/${match.id}`, {
      onSuccess: () => { editingId = null; },
    });
  }

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
          {#if (page.props.auth as any)?.user?.role === 'super_admin'}
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
    <div class="mb-8">
      <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
      <h1 class="text-3xl font-black text-[#081B6A]">Partidos</h1>
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

    <!-- Group filter -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button
        onclick={() => filterGroup = 'all'}
        class="px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-colors
          {filterGroup === 'all' ? 'bg-[#081B6A] text-white' : 'bg-white border border-[#E0E0E0] text-[#9CA3AF] hover:border-[#081B6A] hover:text-[#081B6A]'}"
      >
        Todos
      </button>
      {#each groups as g}
        <button
          onclick={() => filterGroup = g}
          class="px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-colors
            {filterGroup === g ? 'bg-[#081B6A] text-white' : 'bg-white border border-[#E0E0E0] text-[#9CA3AF] hover:border-[#081B6A] hover:text-[#081B6A]'}"
        >
          {g}
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
            <th class="px-4 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden lg:table-cell">Grupo</th>
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
                <span class="text-xs text-[#6B7280]">{match.group_name}</span>
              </td>
              <td class="px-4 py-3 hidden lg:table-cell">
                <span class="inline-block px-2 py-0.5 text-[9px] font-black tracking-widest uppercase {statusBadge(match.status)}">
                  {statusLabel(match.status)}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  onclick={() => startEdit(match)}
                  class="text-xs font-bold transition-colors
                    {editingId === match.id ? 'text-[#9CA3AF] hover:text-red-500' : 'text-[#3554FF] hover:text-[#2340cc]'}"
                >
                  {editingId === match.id ? 'Cancelar' : 'Editar'}
                </button>
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
                      <!-- Date -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Fecha (hora SV)
                        </label>
                        <input
                          type="date"
                          bind:value={editDate}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors"
                        />
                      </div>

                      <!-- Time -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Hora (hora SV)
                        </label>
                        <input
                          type="time"
                          bind:value={editTime}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors"
                        />
                      </div>

                      <!-- Venue -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Sede
                        </label>
                        <input
                          type="text"
                          bind:value={form.venue}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors"
                        />
                      </div>

                      <!-- Home team -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Equipo local
                        </label>
                        <input
                          type="text"
                          bind:value={form.home_team}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors
                            {form.errors.home_team ? 'border-red-400' : ''}"
                        />
                        {#if form.errors.home_team}
                          <p class="text-xs text-red-600 mt-1">{form.errors.home_team}</p>
                        {/if}
                      </div>

                      <!-- Home flag -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Bandera local (emoji)
                        </label>
                        <input
                          type="text"
                          bind:value={form.home_team_flag}
                          maxlength="10"
                          placeholder="🇲🇽"
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors"
                        />
                      </div>

                      <!-- Away team -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Equipo visitante
                        </label>
                        <input
                          type="text"
                          bind:value={form.away_team}
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors
                            {form.errors.away_team ? 'border-red-400' : ''}"
                        />
                        {#if form.errors.away_team}
                          <p class="text-xs text-red-600 mt-1">{form.errors.away_team}</p>
                        {/if}
                      </div>

                      <!-- Away flag -->
                      <div>
                        <label class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
                          Bandera visitante (emoji)
                        </label>
                        <input
                          type="text"
                          bind:value={form.away_team_flag}
                          maxlength="10"
                          placeholder="🇧🇷"
                          class="w-full border border-[#E0E0E0] bg-white px-3 py-2 text-sm text-[#081B6A]
                            focus:outline-none focus:border-[#3554FF] transition-colors"
                        />
                      </div>
                    </div>

                    <!-- Errors summary -->
                    {#if form.errors.match_date}
                      <p class="text-xs text-red-600 mt-3">{form.errors.match_date}</p>
                    {/if}

                    <div class="flex gap-3 mt-5">
                      <button
                        onclick={() => saveMatch(match)}
                        disabled={form.processing}
                        class="bg-[#3554FF] text-white font-black text-xs tracking-widest uppercase px-6 py-2.5
                          hover:bg-[#2340cc] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {form.processing ? 'Guardando...' : 'Guardar cambios'}
                      </button>
                      <button
                        onclick={() => { editingId = null; }}
                        class="border border-[#E0E0E0] bg-white text-[#6B7280] font-bold text-xs px-5 py-2.5
                          hover:border-[#081B6A] hover:text-[#081B6A] transition-colors"
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
