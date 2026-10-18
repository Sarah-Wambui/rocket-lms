<style>
    .cvh-forum-box { background: var(--white); border: 1px solid var(--gray-200); border-radius: 24px; }
    .cvh-forum-cat { display: flex; gap: 16px; align-items: flex-start; padding: 20px; border-radius: 20px; background: var(--white); border: 1px solid var(--gray-200); height: 100%; transition: transform .2s ease, box-shadow .2s ease; }
    .cvh-forum-cat:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(18, 31, 62, .08); }
    .cvh-forum-cat__icon { flex: 0 0 auto; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: var(--primary); color: #fff; }
    .cvh-forum-cat__icon--alt { background: var(--accent); }
    .cvh-forum-meta { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: var(--gray-500); }

    .cvh-forum-row { display: flex; gap: 14px; align-items: center; padding: 16px 20px; border-top: 1px solid var(--gray-200); }
    .cvh-forum-row:first-child { border-top: 0; }
    .cvh-forum-row__main { flex: 1 1 auto; min-width: 0; }
    .cvh-forum-row__title { font-size: 15px; color: var(--dark); font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cvh-forum-row__title:hover { color: var(--primary); }
    .cvh-forum-row__stats { flex: 0 0 auto; text-align: right; min-width: 90px; }

    .cvh-forum-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex: 0 0 auto; background: var(--gray-200); }
    .cvh-forum-avatar--lg { width: 52px; height: 52px; }
    .cvh-forum-tag { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .cvh-forum-tag--pinned { background: rgba(94, 172, 73, .15); color: var(--accent); }
    .cvh-forum-tag--locked { background: var(--gray-200); color: var(--gray-500); }
    .cvh-forum-tag--admin { background: var(--primary); color: #fff; }
    .cvh-forum-tag--cat { background: var(--gray-100); color: var(--primary); border: 1px solid var(--gray-200); }

    .cvh-forum-post { padding: 24px; }
    .cvh-forum-post + .cvh-forum-post { border-top: 1px solid var(--gray-200); }
    .cvh-forum-post__body { font-size: 15px; line-height: 1.75; color: var(--dark); word-wrap: break-word; overflow-wrap: anywhere; }
    .cvh-forum-mod { display: inline-flex; }
    .cvh-forum-mod button { border: 0; background: none; padding: 4px 8px; font-size: 12px; color: var(--gray-500); border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
    .cvh-forum-mod button:hover { background: var(--gray-100); color: var(--dark); }
    .cvh-forum-mod button.is-danger:hover { color: var(--danger); }

    .cvh-forum-alert { padding: 12px 16px; border-radius: 12px; background: rgba(1, 112, 255, .08); color: var(--primary); font-size: 14px; }

    @media (max-width: 575.98px) {
        .cvh-forum-row { padding: 14px; }
        .cvh-forum-row__stats { display: none; }
        .cvh-forum-post { padding: 16px; }
    }
</style>
