let student;
let program;

const TARGETS = [
    { name: 'Rangamal', target: 125 },
    { name: 'Nethmini', target: 200 },
    { name: 'Divani', target: 40 },
    { name: 'Dilrukshi', target: 40 },
    { name: 'Chathurya', target: 40 }
];

window.addEventListener('DOMContentLoaded', async () => {

    // Get students
    const studentResponse = await fetch('api/students.php');
    student = await studentResponse.json();

    // Get programs
    const programResponse = await fetch('api/programs.php');
    program = await programResponse.json();


    /*
     * Generate counsellor statistics
     */
    const statistics = generateCounsellorStatistics(
        student.students,
        program.programs
    );


    /*
     * Generate statistics table
     */
    generateStatTable(statistics);


    /*
     * Generate pie chart
     */
    generateMonochromePieChart(
        'programs-pie-chart',
        'Programs Distribution',
        'Registered',
        statistics.map(stat => ({
            name: stat.name,
            y: stat.registered
        }))
    );


    /*
     * Generate program cards
     *
     * Only show programs which have students
     */
    const programStatistics = generateProgramStatistics(
        student.students,
        program.programs
    ).filter(program => program.count > 0);

    generateListOfPrograms(programStatistics);

});


/* =========================================================
   COUNSELLOR STATISTICS
========================================================= */

const generateCounsellorStatistics = (students, programs) => {

    return TARGETS.map(target => {

        /*
         * Get programs assigned to this counsellor
         */
        const assignedPrograms = programs.filter(
            program => program.assigned === target.name
        );


        /*
         * Get program names assigned to this counsellor
         */
        const programNames = assignedPrograms.map(
            program => program.name
        );


        /*
         * Count students registered under
         * those programs
         */
        const registered = students.filter(
            student => programNames.includes(student.program)
        ).length;


        /*
         * Calculate pending
         */
        const pending = Math.max(
            target.target - registered,
            0
        );


        /*
         * Calculate percentage
         */
        const percentage = target.target > 0
            ? (registered / target.target) * 100
            : 0;


        return {
            name: target.name,
            target: target.target,
            registered: registered,
            pending: pending,
            percentage: percentage
        };

    });
};


/* =========================================================
   STATISTICS TABLE
========================================================= */

const generateStatTable = (statistics) => {

    const tbody = document.getElementById(
        'programs-table-body'
    );

    tbody.innerHTML = "";


    statistics.forEach(stat => {

        const row = document.createElement('tr');


        row.innerHTML = `
            <td>
                ${stat.name}
            </td>

            <td>
                ${stat.target}
            </td>

            <td>
                ${stat.registered}
            </td>

            <td>
                ${stat.pending}
            </td>

            <td>
                ${stat.percentage.toFixed(1)}%
            </td>
        `;


        tbody.appendChild(row);

    });

};


/* =========================================================
   PROGRAM STATISTICS
========================================================= */

const generateProgramStatistics = (students, programs) => {

    return programs.map(program => {

        const count = students.filter(
            student => student.program === program.name
        ).length;


        return {
            id: program.id,
            name: program.name,
            assigned: program.assigned,
            count: count
        };

    });

};


/* =========================================================
   PROGRAM CARDS
========================================================= */

const generateListOfPrograms = (programs) => {

    const container = document.getElementById(
        'programs-stat'
    );

    container.innerHTML = "";


    programs.forEach(program => {

        const card = document.createElement('div');

        card.classList.add(
            'program-stat-card'
        );

        if(program.assigned=="Rangamal") {
            card.classList.add('bg-rangamal');
        }

        if (program.assigned=="Nethmini") {
            card.classList.add('bg-nethmini');
        }

        if (program.assigned=="Divani") {
            card.classList.add('bg-divani');
        }
        if (program.assigned=="Dilrukshi") {
            card.classList.add('bg-dilrukshi');
        }
        if (program.assigned=="Chathurya") {
            card.classList.add('bg-chathurya');
        }

        card.innerHTML = `

            <div class="program-stat-name">
                ${program.name}
            </div>

            <div class="program-stat-count">
                ${program.count}
            </div>

            <div class="program-stat-label">
                Students
            </div>

        `;


        container.appendChild(card);

    });

};


/* =========================================================
   PIE CHART
========================================================= */

const generateMonochromePieChart = (
    elementID,
    title,
    axisName,
    series
) => {

    const totalAmount = series.reduce(
        (total, point) => total + point.y,
        0
    );


    const chartColors = [
        '#3B82F6',
        '#6366F1',
        '#F59E0B',
        '#EC4899',
        '#10B981',
        '#F59E0B'
    ];


    Highcharts.chart(elementID, {

        colors: chartColors,


        chart: {
            type: 'pie',
            backgroundColor: 'white'
        },


        title: {
            text: title,

            style: {
                color: '#111827'
            }
        },


        subtitle: {
            text: `Total ${axisName} : ${totalAmount}`,

            style: {
                color: '#6B7280'
            }
        },


        exporting: {
            enabled: false
        },


        tooltip: {

            useHTML: true,

            headerFormat:
                `<span style="font-size:10px">
                    ${axisName}
                </span>
                <table>`,

            pointFormat:
                `<tr>
                    <td style="padding:0">
                        {point.name}:
                    </td>

                    <td style="padding:0">
                        <b>&nbsp;{point.y}</b>

                        <span style="color:#6B7280">
                            ({point.percentage:.1f}%)
                        </span>
                    </td>
                </tr>`,

            footerFormat: '</table>',

            style: {
                color: '#111827'
            }
        },


        plotOptions: {

            pie: {

                allowPointSelect: true,

                cursor: 'pointer',


                dataLabels: {

                    enabled: true,

                    format:
                        '<b>{point.name}</b>: {point.y} ' +
                        '({point.percentage:.1f}%)',

                    style: {
                        color: '#111827',
                        textOutline: 'none'
                    }
                }
            }
        },


        series: [{
            name: axisName,
            data: series
        }]

    });

};