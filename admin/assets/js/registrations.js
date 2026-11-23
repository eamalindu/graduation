window.addEventListener('DOMContentLoaded', () => {
    const REFRESH_MS = 5000;
    const body = document.getElementById('students-table-body');
    let table = null;

    async function loadStudents() {
        try {
            const res = await fetch('api/registrations.php', { cache: 'no-store' });
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'Failed to load');

            if (!table) {
                table = new DataTable('#students-table', {
                    data: data.students,
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    columns: [
                        { data: 'seatNo', defaultContent: 'N/A' },
                        { data: 'registration_number', className: 'student-row__reg' },
                        { data: 'full_name', className: 'student-row__name' },
                        { data: 'program', className: 'student-row__phone' },
                        { data: 'reg_timestamp', defaultContent: '-', className: 'student-row__status' }
                    ],
                    createdRow: (row, rowData) => {
                        row.classList.add('student-row');
                        if (Number(rowData.isGold) === 1) row.style.background = '#FFC300';
                    }
                });
            } else {
                table.clear().rows.add(data.students).draw(false); // false = stay on current page
            }
        } catch (err) {
            console.error('Error fetching students:', err);
            // keep the old data on screen if a refresh fails; only show an error on first load
            if (!table) {
                body.innerHTML = '<tr><td colspan="5" class="empty-note">Error loading students.</td></tr>';
            }
        } finally {
            setTimeout(loadStudents, REFRESH_MS);
        }
    }

    loadStudents();
});