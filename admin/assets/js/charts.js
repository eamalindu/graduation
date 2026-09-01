window.addEventListener('DOMContentLoaded',()=>{

    const students = fetch('api/students.php')
        .then(response => response.json())
        .then(data => {
        })

    const programs = fetch('api/programs.php')

})

const TARGETS = [{name:'Rangamal',target:125},{name: 'Nethmini',target: 200},{name:'Divani',target: 40},{name:'Dilrukshi',target:40},{name:'Chathurya',target:40}];

