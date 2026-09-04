window.addEventListener('DOMContentLoaded', () => {

    const students = fetch('api/pending.php')
        .then(response => response.json())
        .then(data => {
            const studentList = document.getElementById('students-table-body');
            if (data.success) {
                studentList.innerHTML = data.students.map(student => `
                   <tr class="student-row">
                        <td class="student-row__reg">${student.registration_number}</td>
                        <td class="student-row__name">${student.full_name}</td>
                        <td class="student-row__email">${student.email}</td>
                        <td class="student-row__phone">${student.program}</td>
                        <td class="student-row__status">${student.registered_date ?? 'N/A'}</td>
                        <td class="student-row__status"> ${student.attendance_status === 'present' ? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-check-icon lucide-map-pin-check"><path d="M19.43 12.935c.357-.967.57-1.955.57-2.935a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 1.202 0 32.197 32.197 0 0 0 .813-.728"/><circle cx="12" cy="10" r="3"/><path d="m16 18 2 2 4-4"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="orange" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin-x-icon lucide-map-pin-x"><path d="M19.752 11.901A7.78 7.78 0 0 0 20 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 1.202 0 19 19 0 0 0 .09-.077"/><circle cx="12" cy="10" r="3"/><path d="m21.5 15.5-5 5"/><path d="m21.5 20.5-5-5"/></svg>'}</td>
                        <td class="student-row__status">${student.attendance_time ?? '-'}</td>
                        <td class="student-row__status"> ${student.marked_by ?? '-'}</td>
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
})