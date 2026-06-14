import type { MatchStatus } from './types';

const MONTHS_ES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
const DAYS_ES   = ['dom','lun','mar','mié','jue','vie','sáb'];

function parseSv(svStr: string): { m: number; d: number; h: string; min: string; dow: number } {
    const [date, time = '00:00:00'] = svStr.split(' ');
    const [y, m, d] = date.split('-').map(Number);
    const [h, min]  = time.split(':');
    return { m, d, h, min, dow: new Date(y, m - 1, d).getDay() };
}

// Backend sends "YYYY-MM-DD HH:MM:SS" in El Salvador time — we format directly, no Date timezone involved
export function formatDate(svStr: string): string {
    const { m, d, h, min, dow } = parseSv(svStr);
    return `${DAYS_ES[dow]}, ${d} ${MONTHS_ES[m - 1]} · ${h}:${min}`;
}

export function formatDateShort(svStr: string): string {
    const { m, d } = parseSv(svStr);
    return `${d} ${MONTHS_ES[m - 1]}`;
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
        // Group A
        'México': '🇲🇽', 'Mexico': '🇲🇽',
        'Corea Del Sur': '🇰🇷', 'South Korea': '🇰🇷',
        'Rep. Checa': '🇨🇿', 'Czech Republic': '🇨🇿', 'Czechia': '🇨🇿',
        'Sudáfrica': '🇿🇦', 'South Africa': '🇿🇦',
        // Group B
        'Canadá': '🇨🇦', 'Canada': '🇨🇦',
        'Bosnia Y Herzeg.': '🇧🇦', 'Bosnia and Herzegovina': '🇧🇦',
        'Suiza': '🇨🇭', 'Switzerland': '🇨🇭',
        'Catar': '🇶🇦', 'Qatar': '🇶🇦',
        // Group C
        'Brasil': '🇧🇷', 'Brazil': '🇧🇷',
        'Marruecos': '🇲🇦', 'Morocco': '🇲🇦',
        'Haití': '🇭🇹', 'Haiti': '🇭🇹',
        'Escocia': '🏴󠁧󠁢󠁳󠁣󠁴󠁿', 'Scotland': '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
        // Group D
        'Estados Unidos': '🇺🇸', 'United States': '🇺🇸', 'USA': '🇺🇸',
        'Paraguay': '🇵🇾',
        'Australia': '🇦🇺',
        'Turquía': '🇹🇷', 'Turkey': '🇹🇷', 'Türkiye': '🇹🇷',
        // Group E
        'Alemania': '🇩🇪', 'Germany': '🇩🇪',
        'Curazao': '🇨🇼', 'Curaçao': '🇨🇼', 'Curacao': '🇨🇼',
        'Costa De Marfil': '🇨🇮', 'Ivory Coast': '🇨🇮', "Côte d'Ivoire": '🇨🇮',
        'Ecuador': '🇪🇨',
        // Group F
        'Países Bajos': '🇳🇱', 'Netherlands': '🇳🇱',
        'Japón': '🇯🇵', 'Japan': '🇯🇵',
        'Suecia': '🇸🇪', 'Sweden': '🇸🇪',
        'Túnez': '🇹🇳', 'Tunisia': '🇹🇳',
        // Group G
        'Bélgica': '🇧🇪', 'Belgium': '🇧🇪',
        'Egipto': '🇪🇬', 'Egypt': '🇪🇬',
        'Irán': '🇮🇷', 'Iran': '🇮🇷',
        'Nueva Zelanda': '🇳🇿', 'New Zealand': '🇳🇿',
        // Group H
        'España': '🇪🇸', 'Spain': '🇪🇸',
        'Cabo Verde': '🇨🇻', 'Cape Verde': '🇨🇻',
        'Arabia Saudita': '🇸🇦', 'Saudi Arabia': '🇸🇦',
        'Uruguay': '🇺🇾',
        // Group I
        'Francia': '🇫🇷', 'France': '🇫🇷',
        'Senegal': '🇸🇳',
        'Irak': '🇮🇶', 'Iraq': '🇮🇶',
        'Noruega': '🇳🇴', 'Norway': '🇳🇴',
        // Group J
        'Argentina': '🇦🇷',
        'Argelia': '🇩🇿', 'Algeria': '🇩🇿',
        'Austria': '🇦🇹',
        'Jordania': '🇯🇴', 'Jordan': '🇯🇴',
        // Group K
        'Portugal': '🇵🇹',
        'Rep. Del Congo': '🇨🇩', 'DR Congo': '🇨🇩', 'Democratic Republic of Congo': '🇨🇩',
        'Uzbekistán': '🇺🇿', 'Uzbekistan': '🇺🇿',
        'Colombia': '🇨🇴',
        // Group L
        'Inglaterra': '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'England': '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
        'Croacia': '🇭🇷', 'Croatia': '🇭🇷',
        'Ghana': '🇬🇭',
        'Panamá': '🇵🇦', 'Panama': '🇵🇦',
    };
    return flags[teamName] ?? '🏳️';
}

export function medalFor(rank: number): string {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return `${rank}°`;
}
