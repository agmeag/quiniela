<script lang="ts">
  import { useForm, usePage, router } from '@inertiajs/svelte';

  type UserRow = {
    id: number;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'participant';
    participant_id: number | null;
    participant_name: string | null;
    participant_slug: string | null;
  };

  type ParticipantOption = { id: number; name: string; slug: string };

  let {
    users,
    participants,
  }: {
    users: UserRow[];
    participants: ParticipantOption[];
  } = $props();

  const page = usePage();

  // Panel state
  let panelOpen = $state(false);
  let editing = $state<UserRow | null>(null);

  const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'participant' as 'super_admin' | 'admin' | 'participant',
    participant_id: null as number | null,
  });

  function openCreate() {
    editing = null;
    form.reset();
    form.clearErrors();
    panelOpen = true;
  }

  function openEdit(user: UserRow) {
    editing = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.participant_id = user.participant_id;
    form.clearErrors();
    panelOpen = true;
  }

  function closePanel() {
    panelOpen = false;
    editing = null;
    form.reset();
    form.clearErrors();
  }

  function submit(e: SubmitEvent) {
    e.preventDefault();
    if (editing) {
      form.put(`/admin/users/${editing.id}`, {
        onSuccess: closePanel,
      });
    } else {
      form.post('/admin/users', {
        onSuccess: closePanel,
      });
    }
  }

  function deleteUser(user: UserRow) {
    if (!confirm(`¿Eliminar el usuario "${user.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(`/admin/users/${user.id}`);
  }

  const roleLabel = (role: string) => {
    if (role === 'super_admin') return 'Super Admin';
    if (role === 'admin')       return 'Admin';
    return 'Participante';
  };

  const roleBadge = (role: string) => {
    if (role === 'super_admin') return 'bg-[#081B6A] text-white';
    if (role === 'admin')       return 'bg-[#3554FF] text-white';
    return 'bg-[#F0F0F0] text-[#6B7280]';
  };
</script>

<svelte:head>
  <title>Usuarios — Admin Quiniela 2026</title>
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
          <a href="/admin" class="text-white/60 hover:text-white transition-colors">Dashboard</a>
          <a href="/admin/matches" class="text-white/60 hover:text-white transition-colors">Partidos</a>
          <span class="text-[#3554FF]">Usuarios</span>
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
    <div class="flex items-end justify-between mb-8">
      <div>
        <p class="text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1">Panel de administración</p>
        <h1 class="text-3xl font-black text-[#081B6A]">Usuarios</h1>
      </div>
      <button
        onclick={openCreate}
        class="bg-[#3554FF] text-white text-xs font-black tracking-widest uppercase px-5 py-3 hover:bg-[#2340cc] transition-colors"
      >
        + Nuevo usuario
      </button>
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

    <!-- Table -->
    <div class="bg-white border border-[#E0E0E0] overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[#E0E0E0]">
            <th class="px-5 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Nombre</th>
            <th class="px-5 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Email</th>
            <th class="px-5 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Rol</th>
            <th class="px-5 py-3 text-left text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] hidden md:table-cell">Participante vinculado</th>
            <th class="px-5 py-3 text-right text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF]">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#F0F0F0]">
          {#each users as user}
            <tr class="hover:bg-[#FAFAFA] transition-colors">
              <td class="px-5 py-3 font-semibold text-[#081B6A]">{user.name}</td>
              <td class="px-5 py-3 text-[#6B7280]">{user.email}</td>
              <td class="px-5 py-3">
                <span class="inline-block px-2.5 py-0.5 text-[10px] font-black tracking-widest uppercase {roleBadge(user.role)}">
                  {roleLabel(user.role)}
                </span>
              </td>
              <td class="px-5 py-3 text-[#6B7280] hidden md:table-cell">
                {#if user.participant_name}
                  <a href="/participants/{user.participant_slug}" class="text-[#3554FF] hover:underline">
                    {user.participant_name}
                  </a>
                {:else}
                  <span class="text-[#D1D5DB]">—</span>
                {/if}
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-3">
                  <button
                    onclick={() => openEdit(user)}
                    class="text-xs font-bold text-[#3554FF] hover:underline"
                  >
                    Editar
                  </button>
                  <button
                    onclick={() => deleteUser(user)}
                    class="text-xs font-bold text-red-500 hover:underline"
                  >
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
          {:else}
            <tr>
              <td colspan="5" class="px-5 py-12 text-center text-[#9CA3AF]">No hay usuarios registrados</td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Slide-over panel -->
{#if panelOpen}
  <!-- Backdrop -->
  <div
    class="fixed inset-0 bg-black/40 z-40"
    onclick={closePanel}
    role="presentation"
  ></div>

  <!-- Panel -->
  <div class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col">
    <!-- Header -->
    <div class="bg-[#081B6A] text-white px-6 py-5 flex items-center justify-between shrink-0">
      <h2 class="font-black text-base tracking-wide">
        {editing ? 'Editar usuario' : 'Nuevo usuario'}
      </h2>
      <button onclick={closePanel} class="text-white/60 hover:text-white text-xl leading-none">
        ✕
      </button>
    </div>

    <!-- Form -->
    <form onsubmit={submit} class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
      <!-- Name -->
      <div>
        <label for="u-name" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Nombre
        </label>
        <input
          id="u-name"
          type="text"
          bind:value={form.name}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
            focus:outline-none focus:border-[#3554FF] transition-colors"
        />
        {#if form.errors.name}
          <p class="text-xs text-red-600 mt-1">{form.errors.name}</p>
        {/if}
      </div>

      <!-- Email -->
      <div>
        <label for="u-email" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Correo electrónico
        </label>
        <input
          id="u-email"
          type="email"
          bind:value={form.email}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
            focus:outline-none focus:border-[#3554FF] transition-colors"
        />
        {#if form.errors.email}
          <p class="text-xs text-red-600 mt-1">{form.errors.email}</p>
        {/if}
      </div>

      <!-- Password -->
      <div>
        <label for="u-password" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Contraseña {editing ? '(dejar vacío para no cambiar)' : ''}
        </label>
        <input
          id="u-password"
          type="password"
          bind:value={form.password}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A]
            focus:outline-none focus:border-[#3554FF] transition-colors"
        />
        {#if form.errors.password}
          <p class="text-xs text-red-600 mt-1">{form.errors.password}</p>
        {/if}
      </div>

      <!-- Role -->
      <div>
        <label for="u-role" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
          Rol
        </label>
        <select
          id="u-role"
          bind:value={form.role}
          class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] bg-white
            focus:outline-none focus:border-[#3554FF] transition-colors"
        >
          <option value="participant">Participante</option>
          <option value="admin">Admin</option>
          <option value="super_admin">Super Admin</option>
        </select>
        {#if form.errors.role}
          <p class="text-xs text-red-600 mt-1">{form.errors.role}</p>
        {/if}
      </div>

      <!-- Participant link -->
      {#if form.role === 'participant'}
        <div>
          <label for="u-participant" class="block text-[10px] font-bold tracking-widest uppercase text-[#9CA3AF] mb-1.5">
            Vincular participante
          </label>
          <select
            id="u-participant"
            bind:value={form.participant_id}
            class="w-full border border-[#E0E0E0] px-4 py-2.5 text-sm text-[#081B6A] bg-white
              focus:outline-none focus:border-[#3554FF] transition-colors"
          >
            <option value={null}>— Sin vincular —</option>
            {#each participants as p}
              <option value={p.id}>{p.name}</option>
            {/each}
          </select>
          {#if form.errors.participant_id}
            <p class="text-xs text-red-600 mt-1">{form.errors.participant_id}</p>
          {/if}
          <p class="text-[10px] text-[#9CA3AF] mt-1">Al iniciar sesión, el usuario verá los pronósticos del participante vinculado.</p>
        </div>
      {/if}
    </form>

    <!-- Footer buttons -->
    <div class="px-6 py-4 border-t border-[#E0E0E0] flex gap-3 shrink-0">
      <button
        onclick={closePanel}
        class="flex-1 border border-[#E0E0E0] text-[#6B7280] font-bold text-sm py-2.5
          hover:border-[#081B6A] hover:text-[#081B6A] transition-colors"
      >
        Cancelar
      </button>
      <button
        onclick={(e) => submit(e as unknown as SubmitEvent)}
        disabled={form.processing}
        class="flex-1 bg-[#3554FF] text-white font-black text-sm py-2.5
          hover:bg-[#2340cc] transition-colors disabled:opacity-50"
      >
        {form.processing ? 'Guardando...' : editing ? 'Actualizar' : 'Crear usuario'}
      </button>
    </div>
  </div>
{/if}
