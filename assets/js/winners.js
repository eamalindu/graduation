window.addEventListener('DOMContentLoaded', () => {
    const REFRESH_MS = 5000;
    const body = document.getElementById('winners-table-body');
    let table = null;

    async function loadWinners() {
        try {
            const res = await fetch('api/winners.php', { cache: 'no-store' });
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'Failed to load');

            if (!table) {
                table = new DataTable('#winners-table', {
                    data: data.winners,
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    order: [[4, 'desc']], // latest winner first
                    columns: [
                        { data: 'seatNo', defaultContent: 'N/A' },
                        { data: 'full_name' },
                        { data: 'program' },
                        { data: 'prize_name' },
                        { data: 'drawn_at' }
                    ],
                    createdRow: (row) => row.classList.add('student-row')
                });
            } else {
                table.clear().rows.add(data.winners).draw(false);
            }
        } catch (err) {
            console.error('Error fetching winners:', err);
            if (!table) {
                body.innerHTML = '<tr><td colspan="5" class="empty-note">Error loading winners.</td></tr>';
            }
        } finally {
            setTimeout(loadWinners, REFRESH_MS);
        }
    }

    loadWinners();
});