<style>
    .cvh-innov-filters { border: 1px solid var(--gray-200); }
    .cvh-innov-tab { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 12px; font-size: 14px; color: var(--dark); background: var(--gray-100); border: 1px solid var(--gray-200); }
    .cvh-innov-tab:hover { color: var(--primary); border-color: var(--primary); }
    .cvh-innov-tab.is-active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .cvh-innov-tab__count { font-size: 12px; padding: 1px 8px; border-radius: 8px; background: rgba(0, 0, 0, .06); }
    .cvh-innov-tab.is-active .cvh-innov-tab__count { background: rgba(255, 255, 255, .25); }
    .cvh-innov-search { position: relative; min-width: 220px; }
    .cvh-innov-search .icons { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); }
    .cvh-innov-search input { padding-left: 42px; }
    .cvh-innov-select { width: auto; min-width: 170px; }

    .cvh-innov-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 24px; overflow: hidden; transition: transform .2s ease, box-shadow .2s ease; }
    .cvh-innov-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(18, 31, 62, .10); }
    .cvh-innov-card__image { position: relative; aspect-ratio: 1 / 1; background: var(--gray-100); }
    .cvh-innov-card__image img { width: 100%; height: 100%; object-fit: cover; }
    .cvh-innov-badge { position: absolute; top: 12px; left: 12px; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 8px; font-size: 12px; color: #fff; background: var(--secondary); }
    .cvh-innov-badge--winner { left: auto; right: 12px; background: var(--accent); }

    .cvh-innov-profile__photo { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 24px; }
    .cvh-innov-profile__content h3 { font-size: 18px; color: var(--secondary); margin: 24px 0 8px; }
    .cvh-innov-profile__content p { margin: 0 0 12px; line-height: 1.7; }
</style>
<style>
    @media (max-width: 575.98px) {
        .cvh-innov-card h3 { font-size: 14px; }
        .cvh-innov-card .p-16 { padding: 12px !important; }
        .cvh-innov-badge { top: 8px; left: 8px; padding: 2px 8px; font-size: 11px; }
        .cvh-innov-badge--winner { left: auto; right: 8px; }
    }
</style>
