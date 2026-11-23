(() => {
    const LIST_URL = 'api/users.php';
    const SAVE_URL = 'api/user_save.php';

    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
        ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    let users = [];

    async function load() {
        try {
            const res = await fetch(LIST_URL);
            const data = await res.json();
            if (!data.success) throw new Error(data.message);
            users = data.users;
            render();
        } catch (e) {
            $('users-table-body').innerHTML = `<tr><td colspan="5">${esc(e.message)}</td></tr>`;
        }
    }

    function render() {
        $('users-table-body').innerHTML = users.map(u => `
            <tr>
                <td>${esc(u.username)}</td>
                <td>${esc(u.full_name)}</td>
                <td>${Number(u.superadmin) ? '<span class="role-badge">Superadmin</span>' : 'Admin'}</td>
                <td>${esc(u.created_at)}</td>
                <td><button type="button" class="btn-toggle" data-id="${u.id}">Edit</button></td>
            </tr>`).join('') || '<tr><td colspan="5">No users.</td></tr>';
    }

    function openDialog(u = null) {
        $('dialog-title').textContent = u ? 'Edit user' : 'Add user';
        $('user-id').value = u ? u.id : 0;
        $('user-username').value = u ? u.username : '';
        $('user-fullname').value = u ? (u.full_name ?? '') : '';
        $('user-password').value = '';
        $('user-password').required = !u;
        $('pw-hint').textContent = u ? '(leave blank to keep current)' : '';
        $('user-superadmin').checked = u ? !!Number(u.superadmin) : false;
        $('form-error').textContent = '';
        $('user-dialog').showModal();
    }

    $('add-btn').addEventListener('click', () => openDialog());
    $('cancel-btn').addEventListener('click', () => $('user-dialog').close());

    $('users-table-body').addEventListener('click', e => {
        const btn = e.target.closest('button[data-id]');
        if (btn) openDialog(users.find(u => String(u.id) === btn.dataset.id));
    });

    $('user-form').addEventListener('submit', async e => {
        e.preventDefault();
        $('form-error').textContent = '';
        try {
            const res = await fetch(SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN
                },
                body: JSON.stringify({
                    id: Number($('user-id').value),
                    username: $('user-username').value,
                    full_name: $('user-fullname').value,
                    password: $('user-password').value,
                    superadmin: $('user-superadmin').checked
                })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);
            $('user-dialog').close();
            load();
        } catch (err) {
            $('form-error').textContent = err.message;
        }
    });

    load();
})();