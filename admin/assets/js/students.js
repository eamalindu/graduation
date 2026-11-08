window.addEventListener('DOMContentLoaded', () => {

    const students = fetch('api/students.php')
    .then(response => response.json())
    .then(data => {
        const studentList = document.getElementById('students-table-body');
        if (data.success) {
            const registeredStudents = data.students.filter(
                student => student.roster_status === 'registered'
            );

            if (registeredStudents.length === 0) {
                studentList.innerHTML = '<tr><td colspan="9" class="empty-note">No registered students found.</td></tr>';
                return;
            }

            studentList.innerHTML = registeredStudents.map(student => `
                <tr class="student-row" ${student.isGold ===1 ? 'style="background:#FFC300"':''}>
                    <td>${student.seatNo ?? 'N/A'}</td>
                    <td class="student-row__reg">${student.registration_number}</td>
                    <td class="student-row__name">${student.full_name}</td>
                    <td class="student-row__phone">${student.program}</td>
                    <td class="student-row__status">${student.registered_date ?? 'N/A'}</td>
                    <td class="student-row__status"> ${student.attendance_status === 'present' ? 'Present' : 'Pending'}</td>
                    <td class="student-row__status">${student.attendance_time ?? '-'}</td>
                    <td class="student-row__status"> ${student.marked_by ?? '-'}</td>
                    <td> ${student.isGold ===1 ? 'Yes':'No'}</td>
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

// 1. Add the "async" keyword before the arrow function parameters
document.getElementById('download-btn').addEventListener('click', async () => {
    try {
        const response = await fetch('api/students.php');
        const data = await response.json();
        const jsonData = data.students;

        if (!jsonData || jsonData.length === 0) {
            alert("No student records found to export.");
            return;
        }

        const worksheet = XLSX.utils.json_to_sheet(jsonData);

        // EDIT THIS: your logical condition
        const shouldHighlight = (s) => Number(s.isGold) > 0;

        const thin = { style: "thin", color: { rgb: "000000" } };
        const border = { top: thin, bottom: thin, left: thin, right: thin };

        const baseStyle = { border };
        const highlightStyle = {
            border,
            fill: { patternType: "solid", fgColor: { rgb: "FFC300" } }
        };
        const headerStyle = {
            border,
            font: { bold: true },
            fill: { patternType: "solid", fgColor: { rgb: "D9E1F2" } }
        };

        const range = XLSX.utils.decode_range(worksheet['!ref']);

        for (let r = range.s.r; r <= range.e.r; r++) {
            const highlight = r > 0 && shouldHighlight(jsonData[r - 1]);
            const style = r === 0 ? headerStyle : (highlight ? highlightStyle : baseStyle);

            for (let c = range.s.c; c <= range.e.c; c++) {
                const addr = XLSX.utils.encode_cell({ r, c });
                if (worksheet[addr]) worksheet[addr].s = style;
            }
        }

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Students | Graduation 2026");
        XLSX.writeFile(workbook, "Student_Records_Graduation_2026.xlsx", { compression: true });

    } catch (error) {
        console.error("Failed to export student data:", error);
        alert("An error occurred while downloading the file.");
    }
});
