// GreenGrocer – Main JS

document.addEventListener('DOMContentLoaded', function () {

    // ── Category filter ──
    const filterBtns = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const cat = btn.dataset.category;
            productCards.forEach(card => {
                if (cat === 'all' || card.dataset.category === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // ── Qty controls (+ / - buttons) ──
    document.querySelectorAll('.qty-control').forEach(ctrl => {
        const input = ctrl.querySelector('input');
        const btnMinus = ctrl.querySelector('[data-action="minus"]');
        const btnPlus  = ctrl.querySelector('[data-action="plus"]');

        if (!input) return;

        const step = parseFloat(input.step) || 1;
        const min  = parseFloat(input.min)  || 0;
        const max  = parseFloat(input.max)  || 9999;

        if (btnMinus) {
            btnMinus.addEventListener('click', () => {
                let val = parseFloat(input.value) - step;
                if (val < min) val = min;
                input.value = step < 1 ? val.toFixed(1) : Math.round(val);
            });
        }
        if (btnPlus) {
            btnPlus.addEventListener('click', () => {
                let val = parseFloat(input.value) + step;
                if (val > max) val = max;
                input.value = step < 1 ? val.toFixed(1) : Math.round(val);
            });
        }
    });

    // ── Flash auto-dismiss ──
    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => flash.remove(), 5000);
    }

    // ── Delivery date: no past dates ──
    const dateInput = document.getElementById('delivery_date');
    if (dateInput) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
        // Max 14 days ahead
        const maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + 14);
        dateInput.max = maxDate.toISOString().split('T')[0];
    }

    // ── Admin: confirm delete ──
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', e => {
            if (!confirm(btn.dataset.confirm)) e.preventDefault();
        });
    });

    // ── Admin: stock highlight ──
    document.querySelectorAll('.admin-table td[data-stock]').forEach(td => {
        const val = parseFloat(td.dataset.stock);
        if (val <= 0)  td.classList.add('stock-none');
        else if (val <= 5) td.classList.add('stock-low');
    });
});
