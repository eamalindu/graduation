window.addEventListener('DOMContentLoaded', async () => {

    // Get students
    const studentResponse = await fetch('api/students.php');
    const studentData = await studentResponse.json();

    // ONLY registered students
    student = studentData.students.filter(
        s => s.roster_status === 'registered'
    );

    //generateTrendTable(student);
    generateTrendChart(student);

    hideLoader();
});


const generateTrendTable = (data) => {

    // Group students by registration date
    const dateCounts = {};

    data.forEach(student => {
        const date = student.registered_date;
        dateCounts[date] = (dateCounts[date] || 0) + 1;
    });

    // Sort dates (DD-MM-YYYY)
    const sortedDates = Object.keys(dateCounts).sort((a, b) => {
        const [dayA, monthA, yearA] = a.split('-').map(Number);
        const [dayB, monthB, yearB] = b.split('-').map(Number);

        return new Date(yearA, monthA - 1, dayA) -
            new Date(yearB, monthB - 1, dayB);
    });

    // Populate table header
    document.querySelector('#trend-table thead').innerHTML = `
        <tr>
            <th>Registration Date</th>
            <th>Number of Students</th>
        </tr>
    `;

    // Populate table body
    document.querySelector('#trend-table tbody').innerHTML =
        sortedDates.map(date => `
            <tr>
                <td>${date}</td>
                <td>${dateCounts[date]}</td>
            </tr>
        `).join('');
};

const generateTrendChart = (data) => {

    // Group students by registration date
    const dateCounts = {};

    data.forEach(student => {
        const date = student.registered_date;

        dateCounts[date] = (dateCounts[date] || 0) + 1;
    });

    // Sort dates chronologically (DD-MM-YYYY)
    const sortedDates = Object.keys(dateCounts).sort();

    // Prepare chart data
    const categories = sortedDates;
    const registrationCounts = sortedDates.map(date => dateCounts[date]);

    // Generate Highcharts chart
    Highcharts.chart('registration-trend-chart', {

        chart: {
            type: 'spline',
            backgroundColor: 'transparent',
        },

        title: {
            text: 'Graduation Registration Trend',

            margin: 30
        },

        subtitle: {
            text: 'Registration trend of students over time',
            margin: 30

        },

        xAxis: {
            categories: categories,
            title: {
                text: 'Registration Date',

            },
            labels: {
                rotation: -45
            }
        },

        yAxis: {
            min: 1,
            allowDecimals: false,
            title: {
                text: 'Number of Registrations'
            }
        },

        tooltip: {
            headerFormat: '<b>{point.key}</b><br>',
            pointFormat: 'Registered Students: <b>{point.y}</b>'
        },

        plotOptions: {
            spline: {
                marker: {
                    enabled: true,
                    radius: 4
                },
                dataLabels: {
                    enabled: true
                }
            }
        },

        series: [{
            name: 'Registrations',
            data: registrationCounts,
            color: '#203D79'
        }],

        credits: {
            enabled: false
        },

        exporting: {
            enabled: true
        }

    });
};