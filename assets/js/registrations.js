document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('search-form');
    const regInput = document.getElementById('reg-number');
    const searchBtn = document.getElementById('search-btn');
    const messageBox = document.getElementById('message');
    const studentDetails = document.getElementById('student-details');
    const confirmBtn = document.getElementById('confirm-btn');
    const successState = document.getElementById('success-state');
    const resetBtn = document.getElementById('reset-btn');

    const detailReg = document.getElementById('detail-reg');
    const detailName = document.getElementById('detail-name');
    const detailCourse = document.getElementById('detail-course');
    const detailFaculty = document.getElementById('detail-faculty');
    const detailBatch = document.getElementById('detail-batch');
    const detailSeat = document.getElementById('detail-seat');
    const detailSeatImage = document.getElementById('detail-seat-image');
    const detailContact = document.getElementById('detail-contact');

    const successName = document.getElementById('success-name');
    const successTime = document.getElementById('success-time');

    let currentRegNumber = null;

    function showMessage(text, type = 'error') {
        messageBox.textContent = text;
        messageBox.className = `message message--${type}`;
        messageBox.hidden = false;
    }

    function hideMessage() {
        messageBox.hidden = true;
    }

    function formatDateTime(value) {
        if (!value) return '';
        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
        });
    }

    function resetView() {
        studentDetails.hidden = true;
        successState.hidden = true;
        hideMessage();
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Confirm Registration';
        regInput.value = '';
        regInput.focus();
        currentRegNumber = null;
    }

    function setAlreadyRegistered(timestamp) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Already Registered';
        showMessage(`Already registered on ${formatDateTime(timestamp)}.`, 'info');
    }

    detailSeatImage.onerror = () => {
        detailSeatImage.style.display = 'none';
    };

    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessage();
        studentDetails.hidden = true;
        successState.hidden = true;
        confirmBtn.disabled = true;

        const regNumber = regInput.value.trim();
        if (!regNumber) {
            showMessage('Enter your registration number to continue.');
            return;
        }

        searchBtn.disabled = true;
        searchBtn.textContent = 'Searching…';

        try {
            const response = await fetch('api/search_student.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({registration_number: regNumber}),
            });
            const result = await response.json();

            if (!result.success) {
                showMessage(result.message || 'No record found for that registration number.');
                return;
            }

            const student = result.data;
            currentRegNumber = student.registration_number;

            detailSeat.textContent = student.seatNo || '—';
            detailReg.textContent = student.registration_number;
            detailName.textContent = student.full_name;
            detailCourse.textContent = student.program || '—';
            detailFaculty.textContent = student.email || '—';
            detailBatch.textContent = student.registered_date || '—';
            detailContact.textContent = student.contact || '—';

            if (student.seatNo) {
                const seatImagePath = `images/Seats/${student.seatNo}.jpg`;
                detailSeatImage.src = seatImagePath;
                detailSeatImage.style.display = 'block';
                detailSeatImage.onclick = () => window.open(seatImagePath, '_blank');
            } else {
                detailSeatImage.style.display = 'none';
            }

            studentDetails.hidden = false;

            // Check whether this student is already in the registration table
            try {
                const checkRes = await fetch('api/check_registration.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({registration_number: currentRegNumber}),
                });
                const check = await checkRes.json();

                if (check.success && check.reg_timestamp) {
                    setAlreadyRegistered(check.reg_timestamp);
                } else {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Confirm Registration';
                }
            } catch (checkErr) {
                // If the check fails, still allow confirm; mark_registration.php is the real guard
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Registration';
            }
        } catch (err) {
            showMessage('Could not reach the server. Check your connection and try again.');
            console.error(err);
        } finally {
            searchBtn.disabled = false;
            searchBtn.textContent = 'Find My Record';
        }
    });

    confirmBtn.addEventListener('click', async () => {
        if (!currentRegNumber || confirmBtn.disabled) return;

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Confirming…';
        hideMessage();

        try {
            const response = await fetch('api/mark_registration.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({registration_number: currentRegNumber}),
            });
            const result = await response.json();

            if (!result.success) {
                if (result.already_marked) {
                    setAlreadyRegistered(result.reg_timestamp);
                } else {
                    confirmBtn.textContent = 'Confirm Registration';
                    confirmBtn.disabled = false;
                    showMessage(result.message || 'Could not record registration. Try again.');
                }
                return;
            }

            studentDetails.hidden = true;
            successName.textContent = result.full_name;
            successTime.textContent = formatDateTime(result.reg_timestamp);
            successState.hidden = false;
        } catch (err) {
            showMessage('Could not reach the server. Try again.');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm Registration';
        }
    });

    resetBtn.addEventListener('click', resetView);
});