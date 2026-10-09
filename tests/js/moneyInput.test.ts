import assert from 'node:assert/strict';
import { test } from 'node:test';
import { canAcceptPayment, parseMoneyCents, paymentPreview, textValue } from '../../resources/js/utils/moneyInput.ts';

test('a non-string amount never throws and stays blank', () => {
    assert.equal(textValue(null), '');
    assert.equal(textValue(undefined), '');
    assert.equal(textValue({}), '');
    assert.equal(paymentPreview(2500, null).state, 'blank');
    assert.equal(paymentPreview(2500, 30).state, 'change');
});

test('change is calculated for over, exact and short payments', () => {
    assert.deepEqual(paymentPreview(2500, '30.00'), {
        state: 'change',
        label: 'Monnaie à remettre',
        amount: '5.00',
    });
    assert.deepEqual(paymentPreview(2500, '25'), {
        state: 'change',
        label: 'Monnaie à remettre',
        amount: '0.00',
    });
    assert.deepEqual(paymentPreview(2500, '20,50'), {
        state: 'due',
        label: 'Montant restant dû',
        amount: '4.50',
    });
});

test('an incomplete or invalid amount shows no change figure', () => {
    assert.equal(paymentPreview(2500, '').state, 'blank');
    assert.equal(paymentPreview(2500, '12.').state, 'invalid');
    assert.equal(paymentPreview(2500, '12,').state, 'invalid');
    assert.equal(paymentPreview(2500, 'abc').state, 'invalid');
    assert.equal(parseMoneyCents('12.5'), 1250);
    assert.equal(canAcceptPayment(2500, '30'), true);
    assert.equal(canAcceptPayment(2500, '10'), false);
    assert.equal(canAcceptPayment(2500, ''), false);
    assert.equal(canAcceptPayment(0, '10'), false);
});

test('several lines in either currency use the same cent total', () => {
    const usdTotal = (1900 * 1) + (300 * 2);
    const cdfTotal = (1900 * 1) + (200 * 3);

    assert.equal(usdTotal, 2500);
    assert.equal(paymentPreview(usdTotal, '25.00').state, 'change');
    assert.equal(paymentPreview(cdfTotal, '25.00').amount, '0.00');
    assert.equal(canAcceptPayment(cdfTotal, '24.99'), false);
});
