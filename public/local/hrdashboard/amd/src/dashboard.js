// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lightweight behaviour for the HR dashboards: client-side table sorting and
 * auto-submitting filter selects. No dependencies beyond the DOM.
 *
 * @module     local_hrdashboard/dashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Value used to order a cell: data-sort when present, else its text.
 *
 * @param {HTMLElement} cell
 * @returns {{num: number|null, text: string}}
 */
const cellValue = (cell) => {
    if (!cell) {
        return {num: null, text: ''};
    }
    const raw = cell.dataset.sort !== undefined ? cell.dataset.sort : cell.textContent.trim();
    const num = raw === '' ? null : Number(raw);
    return {num: Number.isFinite(num) ? num : null, text: raw.toLowerCase()};
};

/**
 * Sort one table by a column index.
 *
 * @param {HTMLTableElement} table
 * @param {number} index
 * @param {boolean} descending
 */
const sortTable = (table, index, descending) => {
    const body = table.tBodies[0];
    if (!body) {
        return;
    }
    const rows = Array.from(body.rows);
    const collator = new Intl.Collator(document.documentElement.lang || undefined, {numeric: true, sensitivity: 'base'});
    rows.sort((a, b) => {
        const va = cellValue(a.cells[index]);
        const vb = cellValue(b.cells[index]);
        let cmp;
        if (va.num !== null && vb.num !== null) {
            cmp = va.num - vb.num;
        } else if (va.num !== null || vb.num !== null) {
            // Numbers before text (dashes and "never" sink to the end).
            cmp = va.num !== null ? -1 : 1;
        } else {
            cmp = collator.compare(va.text, vb.text);
        }
        return descending ? -cmp : cmp;
    });
    rows.forEach((row) => body.appendChild(row));
};

/**
 * Wire sortable headers: <th><button class="hrd-sort">…</button></th> inside table[data-hrd-sortable].
 *
 * @param {HTMLTableElement} table
 */
const initSortable = (table) => {
    const headers = Array.from(table.querySelectorAll('thead th'));
    headers.forEach((th, index) => {
        const button = th.querySelector('.hrd-sort');
        if (!button) {
            return;
        }
        button.addEventListener('click', () => {
            const current = th.getAttribute('aria-sort');
            const descending = current === 'ascending';
            headers.forEach((other) => other.removeAttribute('aria-sort'));
            th.setAttribute('aria-sort', descending ? 'descending' : 'ascending');
            sortTable(table, index, descending);
        });
    });
};

/**
 * Submit a filter form as soon as a select changes (the Apply button stays for keyboard users).
 *
 * @param {HTMLFormElement} form
 */
const initAutosubmit = (form) => {
    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => {
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    });
};

/**
 * Entry point.
 */
export const init = () => {
    document.querySelectorAll('table[data-hrd-sortable]').forEach(initSortable);
    document.querySelectorAll('form[data-hrd-autosubmit]').forEach(initAutosubmit);
};
