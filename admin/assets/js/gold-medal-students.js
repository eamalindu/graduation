window.addEventListener('DOMContentLoaded', () => {

    const students = fetch('api/gold-medal-all.php')
        .then(response => response.json())
        .then(data => {
            const studentList = document.getElementById('students-table-body');
            if (data.success) {
                studentList.innerHTML = data.students.map(student => `
                <tr class="student-row">
                    <td>${student.seatNo ?? 'N/A'}</td>
                    <td class="student-row__reg">${student.registration_number}</td>
                    <td class="student-row__name">${student.full_name}</td>
                    <td class="student-row__phone">${student.program}</td>
                    <td class="student-row__status">${student.registered_date ?? 'N/A'}</td>
                    <td class="student-row__status"> ${student.attendance_status === 'present' ? 'Present' : 'Pending'}</td>
                    <td class="student-row__status">${student.attendance_time ?? '-'}</td>
                    <td class="student-row__status"> ${student.marked_by ?? '-'}</td>
                    <td style="font-weight: bold"> ${student.isGold ===1 ? 'Yes':'No'}</td>
                </tr>
                `).join('');

                new DataTable('#students-table', {
                    pageLength: 10, searching: true, ordering: true
                });
            } else {
                studentList.innerHTML = '<p class="empty-note">No students found.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            const studentList = document.getElementById('student-list');
            studentList.innerHTML = '<p class="empty-note">Error loading students.</p>';
        });
    hideLoader()
})