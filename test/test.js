import http from 'k6/http';
import { check } from 'k6';

export const options = {
    scenarios: {
        simultaneous_search: {
            executor: 'shared-iterations',
            vus: 100,
            iterations: 100,
            maxDuration: '30s',
        },
    },
};

export default function () {
    const response = http.post(
        'http://localhost/graduation/api/search_student.php',
        {
            registration_number: 'YOUR-TEST-REGISTRATION',
        }
    );

    check(response, {
        'HTTP 200': (r) => r.status === 200,
        'valid JSON': (r) => {
            try {
                r.json();
                return true;
            } catch {
                return false;
            }
        },
    });
}