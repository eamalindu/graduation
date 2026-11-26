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

        // 1. Pick only the columns you want (key order = column order)
        const exportData = jsonData.map(s => ({
            "Graduation No": s.seatNo,
            "Reg No": s.registration_number,
            "Full Name": s.full_name,
            "Program": s.program,
            "Attendance": s.attendance_status
        }));

        const worksheet = XLSX.utils.json_to_sheet(exportData);

        // Conditions (use the ORIGINAL row)
        const shouldHighlight = (s) => Number(s.isGold) > 0;
        const isPending = (s) => s.attendance_status === 'pending';

        const thin = { style: "thin", color: { rgb: "000000" } };
        const border = { top: thin, bottom: thin, left: thin, right: thin };

        const goldFill = { patternType: "solid", fgColor: { rgb: "FFC300" } };
        const pendingFont = { bold: true, color: { rgb: "FF0000" } };

        const headerStyle = {
            border,
            font: { bold: true },
            fill: { patternType: "solid", fgColor: { rgb: "D9E1F2" } }
        };

        // Build a style per row from the flags
        const getRowStyle = (s) => {
            const style = { border };
            if (shouldHighlight(s)) style.fill = goldFill;
            if (isPending(s)) style.font = pendingFont;
            return style;
        };

        const range = XLSX.utils.decode_range(worksheet['!ref']);

        for (let r = range.s.r; r <= range.e.r; r++) {
            const style = r === 0 ? headerStyle : getRowStyle(jsonData[r - 1]);

            for (let c = range.s.c; c <= range.e.c; c++) {
                const addr = XLSX.utils.encode_cell({ r, c });
                if (worksheet[addr]) worksheet[addr].s = style;
            }
        }

        // Optional: column widths
        worksheet['!cols'] = [{ wch: 15 }, { wch: 16 }, { wch: 34 }, { wch: 67 }, { wch: 11 }];

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Students | Graduation 2026");
        XLSX.writeFile(workbook, "Student_Records_Graduation_2026.xlsx", { compression: true });

    } catch (error) {
        console.error("Failed to export student data:", error);
        alert("An error occurred while downloading the file.");
    }
});
