export function textValue(value: unknown): string {
    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' && Number.isFinite(value)) {
        return String(value);
    }

    return '';
}

export function parseMoneyCents(value: unknown): number | null {
    const raw = textValue(value).trim().replace(/\s/g, '').replace(',', '.');

    if (raw === '' || raw === '.') {
        return null;
    }

    if (!/^\d+$/.test(raw) && !/^\d+\.\d{1,2}$/.test(raw)) {
        return null;
    }

    const [whole, fraction = ''] = raw.split('.');
    const minor = (fraction + '00').slice(0, 2);
    const cents = (Number(whole) * 100) + Number(minor);

    if (!Number.isSafeInteger(cents)) {
        return null;
    }

    return cents;
}

export function formatCents(value: number): string {
    const sign = value < 0 ? '-' : '';
    const absolute = Math.abs(value);
    const whole = Math.floor(absolute / 100);
    const minor = String(absolute % 100).padStart(2, '0');

    return `${sign}${whole}.${minor}`;
}

export type PaymentPreview =
    | { state: 'blank' }
    | { state: 'invalid' }
    | { state: 'due'; label: 'Montant restant dû'; amount: string }
    | { state: 'change'; label: 'Monnaie à remettre'; amount: string };

export function paymentPreview(totalCents: number, received: unknown): PaymentPreview {
    if (textValue(received).trim() === '') {
        return { state: 'blank' };
    }

    const receivedCents = parseMoneyCents(received);

    if (receivedCents === null) {
        return { state: 'invalid' };
    }

    const difference = receivedCents - totalCents;

    if (difference < 0) {
        return {
            state: 'due',
            label: 'Montant restant dû',
            amount: formatCents(Math.abs(difference)),
        };
    }

    return {
        state: 'change',
        label: 'Monnaie à remettre',
        amount: formatCents(difference),
    };
}

export function canAcceptPayment(totalCents: number, received: unknown): boolean {
    return totalCents > 0 && paymentPreview(totalCents, received).state === 'change';
}
