let students;

let programs;

window.addEventListener('DOMContentLoaded',()=>{

    students = fetch('api/students.php')
        .then(response => response.json())
        .then(data => {
        })

    programs = fetch('api/programs.php')

    generateMonochromePieChart('programs-pie-chart', 'Programs Distribution', 'Target', TARGETS.map(target => ({name: target.name, y: target.target})));

})

const TARGETS = [{name:'Rangamal',target:125},{name: 'Nethmini',target: 200},{name:'Divani',target: 40},{name:'Dilrukshi',target:40},{name:'Chathurya',target:40}];

const generateMonochromePieChart = (elementID, title, axisName, series) => {

    const totalAmount = series.reduce(
        (total, point) => total + point.y,
        0
    );

    const chartColors = [
        '#2563EB', // Blue
        '#7C3AED', // Violet
        '#0891B2', // Cyan
        '#059669', // Emerald
        '#D97706', // Amber
        '#DC2626'  // Red
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
                `<span style="font-size:10px">${axisName}</span><table>`,

            pointFormat:
                `<tr>
                    <td style="padding:0">{point.name}: </td>
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

const generateStatTable = ()=>{


}