import './bootstrap';
import UnsavedChangesGuard from './unsaved-changes';

window.UnsavedChangesGuard = UnsavedChangesGuard;

// flatpickr（日付入力）
import flatpickr from 'flatpickr';
import { Japanese } from 'flatpickr/dist/l10n/ja.js';

flatpickr.localize(Japanese);
window.flatpickr = flatpickr;

document.addEventListener('DOMContentLoaded', () => {
    flatpickr('.datepicker', {
        dateFormat: 'Y-m-d',
        allowInput: true,
        disableMobile: true,
    });
});
