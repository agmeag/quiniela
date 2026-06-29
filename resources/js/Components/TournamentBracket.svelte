<script lang="ts">
  import type { Match } from '../lib/types';
  import { flagEmoji } from '../lib/utils';

  let { bracket_matches }: { bracket_matches: Match[] } = $props();

  function byStage(stage: string): Match[] {
    return bracket_matches
      .filter(m => m.stage === stage)
      .sort((a, b) => a.correlativo - b.correlativo);
  }

  function pad(arr: (Match | null)[], len: number): (Match | null)[] {
    const out: (Match | null)[] = [...arr];
    while (out.length < len) out.push(null);
    return out;
  }

  function formatMatchDate(svStr: string): string {
    const [date, time = '00:00:00'] = svStr.split(' ');
    const [, m, d] = date.split('-').map(Number);
    const [h, min] = time.split(':');
    const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return `${d} ${months[m - 1]} · ${h}:${min}`;
  }

  const r32 = byStage('round_of_32');
  const r16 = byStage('round_of_16');
  const qf  = byStage('quarter');
  const sf  = byStage('semi');

  const leftR32  = pad(r32.slice(0, 8), 8);
  const rightR32 = pad(r32.slice(8, 16), 8);
  const leftR16  = pad(r16.slice(0, 4), 4);
  const rightR16 = pad(r16.slice(4, 8), 4);
  const leftQF   = pad(qf.slice(0, 2), 2);
  const rightQF  = pad(qf.slice(2, 4), 2);
  const sf1 = sf[0] ?? null;
  const sf2 = sf[1] ?? null;
  const finalMatch = byStage('final')[0] ?? null;
  const thirdPlace = byStage('third_place')[0] ?? null;

  function winner(m: Match | null): 'home' | 'away' | null {
    if (!m || m.home_score === null || m.away_score === null) return null;
    if (m.home_score > m.away_score) return 'home';
    if (m.away_score > m.home_score) return 'away';
    return null;
  }
</script>

{#snippet matchCard(match: Match | null)}
  {@const w = winner(match)}
  <div
    class="match-card"
    class:live={match?.status === 'live'}
    class:finished={match?.status === 'finished'}
    class:empty={!match}
  >
    <div class="team" class:win={w === 'home'}>
      <span class="flag">{match?.home_team_flag ?? flagEmoji(match?.home_team ?? '')}</span>
      <span class="tname">{match?.home_team ?? 'TBD'}</span>
      {#if match && match.home_score !== null && match.home_score !== undefined}
        <span class="sc">{match.home_score}</span>
      {/if}
    </div>
    <div class="team" class:win={w === 'away'}>
      <span class="flag">{match?.away_team_flag ?? flagEmoji(match?.away_team ?? '')}</span>
      <span class="tname">{match?.away_team ?? 'TBD'}</span>
      {#if match && match.away_score !== null && match.away_score !== undefined}
        <span class="sc">{match.away_score}</span>
      {/if}
    </div>
    {#if match}
      <div class="match-meta">
        {#if match.status === 'live'}
          <span class="meta-live">{match.minute ? `${match.minute}'` : 'EN VIVO'}</span>
        {:else if match.status === 'finished'}
          <span class="meta-fin">✓ FIN</span>
        {:else}
          <span class="meta-date">{formatMatchDate(match.match_date)}</span>
        {/if}
      </div>
    {/if}
  </div>
{/snippet}

{#snippet slot(match: Match | null, idx: number)}
  <div class="slot" class:odd-slot={idx % 2 === 0} class:even-slot={idx % 2 === 1}>
    {#if match}
      <a href="/matches/{match.id}" class="card-link">
        {@render matchCard(match)}
      </a>
    {:else}
      {@render matchCard(null)}
    {/if}
  </div>
{/snippet}

{#snippet leftRound(matches: (Match | null)[], isFirst: boolean = false, isLast: boolean = false)}
  <div class="round" class:first-round={isFirst} class:last-round={isLast}>
    {#each matches as m, i}
      {@render slot(m, i)}
    {/each}
  </div>
{/snippet}

{#snippet rightRound(matches: (Match | null)[], isFirst: boolean = false, isLast: boolean = false)}
  <div class="round right" class:first-round={isFirst} class:last-round={isLast}>
    {#each matches as m, i}
      {@render slot(m, i)}
    {/each}
  </div>
{/snippet}

<section class="section">
  <div class="scroll-wrap">
    <div class="bracket">

      <!-- Left half: R32 → R16 → QF → SF (columns left to right) -->
      <div class="half">
        {@render leftRound(leftR32, true, false)}
        {@render leftRound(leftR16, false, false)}
        {@render leftRound(leftQF, false, false)}
        {@render leftRound([sf1], false, true)}
      </div>

      <!-- Center: trophy, final, 3rd place -->
      <div class="center">
        <span class="trophy">🏆</span>
        <p class="round-label">Final</p>
        <div class="center-match">
          {#if finalMatch}
            <a href="/matches/{finalMatch.id}" class="card-link">{@render matchCard(finalMatch)}</a>
          {:else}
            {@render matchCard(null)}
          {/if}
        </div>
        <p class="round-label" style="margin-top: 20px;">3.° Lugar</p>
        <div class="center-match">
          {#if thirdPlace}
            <a href="/matches/{thirdPlace.id}" class="card-link">{@render matchCard(thirdPlace)}</a>
          {:else}
            {@render matchCard(null)}
          {/if}
        </div>
      </div>

      <!-- Right half: SF → QF → R16 → R32 (columns left to right) -->
      <div class="half right-half">
        {@render rightRound([sf2], false, true)}
        {@render rightRound(rightQF, false, false)}
        {@render rightRound(rightR16, false, false)}
        {@render rightRound(rightR32, true, false)}
      </div>

    </div>
  </div>
</section>

<style>
  /* ── Animations ── */
  @keyframes fire-pulse {
    0%   { box-shadow: 0 0 6px #FF5A0099, 0 0 14px #FF3D0066; border-color: #FF5A00; }
    25%  { box-shadow: 0 0 12px #FFB30099, 0 0 24px #FF5A0077; border-color: #FFB300; }
    50%  { box-shadow: 0 0 8px #FF1E0099, 0 0 18px #FF5A0055; border-color: #FF3D00; }
    75%  { box-shadow: 0 0 16px #FF8C0099, 0 0 28px #FF3D0066; border-color: #FF8C00; }
    100% { box-shadow: 0 0 6px #FF5A0099, 0 0 14px #FF3D0066; border-color: #FF5A00; }
  }

  @keyframes gold-settle {
    0%, 100% { box-shadow: 0 0 4px #D4AF3733; }
    50%       { box-shadow: 0 0 10px #D4AF3766; }
  }

  @keyframes trophy-glow {
    0%, 100% { text-shadow: 0 0 12px rgba(212,175,55,0.3), 0 0 24px rgba(212,175,55,0.15); }
    50%       { text-shadow: 0 0 20px rgba(212,175,55,0.6), 0 0 40px rgba(212,175,55,0.3); }
  }

  .section {
    background: #050505;
    padding: 40px 0;
    border-top: 1px solid #151515;
    border-bottom: 1px solid #151515;
  }

  .scroll-wrap {
    overflow-x: auto;
    padding: 0 24px;
    scrollbar-width: thin;
    scrollbar-color: #2A2A2A transparent;
  }

  .bracket {
    display: flex;
    align-items: stretch;
    height: 700px;
    min-width: 1260px;
    max-width: 1560px;
    margin: 0 auto;
  }

  /* ── Halves ── */
  .half {
    display: flex;
    flex: 1;
    height: 100%;
  }

  .right-half {
    /* no row-reverse — DOM order SF→QF→R16→R32 renders correctly L→R with SF closest to center */
  }

  /* ── Round columns ── */
  .round {
    display: flex;
    flex-direction: column;
    flex: 1;
    height: 100%;
    position: relative;
    overflow: visible;
  }

  /* ── Slots ── */
  .slot {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: visible;
    padding: 0 6px;
  }

  .card-link {
    display: block;
    text-decoration: none;
  }

  /* ─────────────────────────────────────────
     LEFT-SIDE CONNECTORS (outgoing → right)
  ──────────────────────────────────────────── */

  .half:not(.right-half) .round:not(.last-round) .slot::after {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    width: 6px;
    border-top: 1px solid #4A4A4A;
    pointer-events: none;
  }

  .half:not(.right-half) .round:not(.last-round) .odd-slot::after {
    top: 50%;
    height: calc(50% + 1px);
    border-top: 1px solid #4A4A4A;
    border-right: 1px solid #4A4A4A;
    border-bottom: none;
    width: 6px;
  }

  .half:not(.right-half) .round:not(.last-round) .even-slot::after {
    top: auto;
    bottom: calc(50% - 1px);
    height: calc(50% + 1px);
    border-top: none;
    border-right: 1px solid #4A4A4A;
    border-bottom: 1px solid #4A4A4A;
    width: 6px;
  }

  .half:not(.right-half) .round:not(.first-round) .slot::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    width: 6px;
    border-top: 1px solid #4A4A4A;
    pointer-events: none;
  }

  /* ─────────────────────────────────────────
     RIGHT-SIDE CONNECTORS (outgoing → left)
  ──────────────────────────────────────────── */

  .right-half .round:not(.last-round) .slot::after {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    width: 6px;
    border-top: 1px solid #4A4A4A;
    pointer-events: none;
  }

  .right-half .round:not(.last-round) .odd-slot::after {
    top: 50%;
    height: calc(50% + 1px);
    border-top: 1px solid #4A4A4A;
    border-left: 1px solid #4A4A4A;
    border-bottom: none;
    width: 6px;
  }

  .right-half .round:not(.last-round) .even-slot::after {
    top: auto;
    bottom: calc(50% - 1px);
    height: calc(50% + 1px);
    border-top: none;
    border-left: 1px solid #4A4A4A;
    border-bottom: 1px solid #4A4A4A;
    width: 6px;
  }

  .right-half .round:not(.first-round) .slot::before {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    width: 6px;
    border-top: 1px solid #4A4A4A;
    pointer-events: none;
  }

  /* ── Center ── */
  .center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 160px;
    flex-shrink: 0;
    padding: 0 8px;
    position: relative;
  }

  .center::before,
  .center::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 12px;
    border-top: 1px solid #4A4A4A;
    pointer-events: none;
  }

  .center::before {
    left: 0;
  }

  .center::after {
    right: 0;
  }

  .trophy {
    font-size: 44px;
    display: block;
    text-align: center;
    margin-bottom: 8px;
    line-height: 1;
    animation: trophy-glow 2.5s ease-in-out infinite;
  }

  .round-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #777777;
    margin: 0 0 6px;
    text-align: center;
  }

  .center-match {
    width: 100%;
  }

  /* ── Match card ── */
  .match-card {
    width: 140px;
    min-width: 140px;
    border: 1px solid #303030;
    background: #1A1A1A;
    border-radius: 5px;
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .card-link:hover .match-card:not(.live):not(.finished) {
    border-color: #D4AF37;
    box-shadow: 0 0 8px rgba(212, 175, 55, 0.2);
  }

  /* Live: fire glow */
  .match-card.live {
    border-color: #FF5A00;
    animation: fire-pulse 1.8s ease-in-out infinite;
  }

  /* Finished: gold settle */
  .match-card.finished {
    border-color: rgba(212, 175, 55, 0.4);
    animation: gold-settle 3s ease-in-out infinite;
  }

  .match-card.empty {
    background: #111111;
    border-color: #222222;
  }

  .team {
    display: flex;
    align-items: center;
    padding: 4px 6px;
    gap: 3px;
    border-bottom: 1px solid #242424;
  }

  .team:last-of-type {
    border-bottom: none;
  }

  .flag {
    font-size: 10px;
    line-height: 1;
    flex-shrink: 0;
    width: 14px;
    text-align: center;
  }

  .tname {
    font-size: 8.5px;
    font-weight: 500;
    color: #BDBDBD;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.01em;
  }

  .team.win .tname {
    color: #FFFFFF;
    font-weight: 700;
  }

  .sc {
    font-size: 10px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.85);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
    min-width: 10px;
    text-align: right;
  }

  .team.win .sc {
    color: #D4AF37;
  }

  .match-card.empty .tname {
    color: rgba(255, 255, 255, 0.15);
    font-style: italic;
  }

  /* ── Match meta row (date / live / fin) ── */
  .match-meta {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px 4px 3px;
    border-top: 1px solid #242424;
  }

  .meta-date {
    font-size: 7px;
    color: #777777;
    letter-spacing: 0.04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .meta-live {
    font-size: 7px;
    font-weight: 700;
    color: #FF8C00;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .meta-fin {
    font-size: 7px;
    font-weight: 700;
    color: #D4AF37;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }
</style>
