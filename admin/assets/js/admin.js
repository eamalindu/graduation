document.addEventListener('DOMContentLoaded', () => {
  const statTotal = document.getElementById('stat-total');
  const statPresent = document.getElementById('stat-present');
  const statPending = document.getElementById('stat-pending');
  const statPercent = document.getElementById('stat-percent');
  const recentList = document.getElementById('recent-list');

  const searchForm = document.getElementById('admin-search-form');
  const searchInput = document.getElementById('admin-search-input');
  const searchResults = document.getElementById('search-results');
  const adminMessage = document.getElementById('admin-message');

  const csrfToken = window.CSRF_TOKEN;

  function showMessage(text) {
    adminMessage.textContent = text;
    adminMessage.hidden = false;
  }

  function hideMessage() {
    adminMessage.hidden = true;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function formatDateTime(value) {
    if (!value) return '';
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('en-GB', {
      day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    });
  }

  async function loadStats() {
    try {
      const res = await fetch('api/stats.php');
      const data = await res.json();
      if (!data.success) return;

      statTotal.textContent = data.total;
      statPresent.textContent = data.present;
      statPending.textContent = data.pending;
      statPercent.textContent = `${data.percent}%`;

      recentList.innerHTML = data.recent.length === 0
        ? '<p class="empty-note">No check-ins yet.</p>'
        : data.recent.map((r) => `
            <div class="recent-row">
              <div>
                <div class="recent-row__name">${escapeHtml(r.full_name)}</div>
                <div class="recent-row__reg">${escapeHtml(r.registration_number)}</div>
              </div>
              <div class="recent-row__time">${formatDateTime(r.attendance_time)}</div>
            </div>
          `).join('');
    } catch (err) {
      // Background poll — fail silently, the next tick retries.
    }
  }

  function renderResults(results) {
    if (results.length === 0) {
      searchResults.innerHTML = '<p class="empty-note">No matching students.</p>';
      return;
    }

    searchResults.innerHTML = results.map((s) => {
      const isPresent = s.attendance_status === 'present';
      const meta = isPresent
        ? `<div class="result-row__meta">Checked in ${formatDateTime(s.attendance_time)}${s.marked_by ? ' &middot; ' + escapeHtml(s.marked_by) : ''}</div>`
        : '';
      return `
        <div class="result-row" data-id="${s.id}">
          <div class="result-row__info">
            <div class="result-row__name">${escapeHtml(s.full_name)}</div>
            <div class="result-row__reg">${escapeHtml(s.registration_number)}</div>
            ${meta}
          </div>
          <button
            type="button"
            class="btn-toggle ${isPresent ? 'btn-toggle--undo' : 'btn-toggle--mark'}"
            data-action="${isPresent ? 'pending' : 'present'}"
          >${isPresent ? 'Revert' : 'Mark Present'}</button>
        </div>
      `;
    }).join('');
  }

  async function runSearch(query) {
    if (!query) {
      searchResults.innerHTML = '';
      return;
    }
    searchResults.innerHTML = '<p class="empty-note">Searching…</p>';
    try {
      const res = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
      const data = await res.json();
      if (!data.success) {
        searchResults.innerHTML = `<p class="empty-note">${escapeHtml(data.message || 'Search failed.')}</p>`;
        return;
      }
      renderResults(data.results);
    } catch (err) {
      searchResults.innerHTML = '<p class="empty-note">Could not reach the server.</p>';
    }
  }

  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    hideMessage();
    runSearch(searchInput.value.trim());
  });

  searchResults.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-toggle');
    if (!btn) return;

    hideMessage();
    const originalLabel = btn.textContent;
    const action = btn.dataset.action;
    const id = btn.closest('.result-row').dataset.id;

    btn.disabled = true;
    btn.textContent = '…';

    try {
      const res = await fetch('api/toggle_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id, action, csrf_token: csrfToken }),
      });
      const data = await res.json();

      if (!data.success) {
        showMessage(data.message || 'Could not update attendance.');
        btn.disabled = false;
        btn.textContent = originalLabel;
        return;
      }

      runSearch(searchInput.value.trim());
      loadStats();
    } catch (err) {
      showMessage('Could not reach the server.');
      btn.disabled = false;
      btn.textContent = originalLabel;
    }
  });

  loadStats();
  setInterval(loadStats, 1000);
});
