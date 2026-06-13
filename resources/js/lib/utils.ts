import type { MatchStatus } from './types';

export function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function formatDateShort(iso: string): string {
    return new Intl.DateTimeFormat('es-MX', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(iso));
}

export function statusLabel(status: MatchStatus, minute?: number | null): string {
    switch (status) {
        case 'live': return minute ? `${minute}'` : 'En vivo';
        case 'finished': return 'Finalizado';
        default: return 'Por jugar';
    }
}

export function flagEmoji(teamName: string): string {
    const flags: Record<string, string> = {
        'México': '🇲🇽', 'Mexico': '🇲🇽',
        'Estados Unidos': '🇺🇸', 'United States': '🇺🇸',
        'Canadá': '🇨🇦', 'Canada': '🇨🇦',
        'Argentina': '🇦🇷', 'Brasil': '🇧🇷', 'Brazil': '🇧🇷',
        'Francia': '🇫🇷', 'France': '🇫🇷',
        'Alemania': '🇩🇪', 'Germany': '🇩🇪',
        'España': '🇪🇸', 'Spain': '🇪🇸',
        'Portugal': '🇵🇹',
        'Inglaterra': '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'England': '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
        'Países Bajos': '🇳🇱', 'Netherlands': '🇳🇱',
        'Bélgica': '🇧🇪', 'Belgium': '🇧🇪',
        'Croacia': '🇭🇷', 'Croatia': '🇭🇷',
        'Uruguay': '🇺🇾',
        'Corea Del Sur': '🇰🇷', 'South Korea': '🇰🇷',
        'Japón': '🇯🇵', 'Japan': '🇯🇵',
        'Marruecos': '🇲🇦', 'Morocco': '🇲🇦',
        'Senegal': '🇸🇳',
        'Ecuador': '🇪🇨',
        'Colombia': '🇨🇴',
        'Noruega': '🇳🇴', 'Norway': '🇳🇴',
        'Suiza': '🇨🇭', 'Switzerland': '🇨🇭',
        'Australia': '🇦🇺',
        'Turquía': '🇹🇷', 'Turkey': '🇹🇷',
        'Irán': '🇮🇷', 'Iran': '🇮🇷',
        'Arabia Saudita': '🇸🇦', 'Saudi Arabia': '🇸🇦',
        'Ghana': '🇬🇭',
        'Catar': '🇶🇦', 'Qatar': '🇶🇦',
        'Sudáfrica': '🇿🇦', 'South Africa': '🇿🇦',
    };
    return flags[teamName] ?? '🏳️';
}

export function medalFor(rank: number): string {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return `${rank}°`;
}
