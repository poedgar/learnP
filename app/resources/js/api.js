export class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}

async function request(method, url, body) {
    let response;

    try {
        response = await fetch(`/api/${url}`, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new ApiError('Could not reach the server. Check your connection and try again.', 0);
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const message =
            response.status === 429
                ? 'You are running queries very quickly. Wait a few seconds, then try again.'
                : (data?.message ?? `The server responded with an error (${response.status}).`);
        throw new ApiError(message, response.status);
    }

    return data;
}

export const api = {
    courses: () => request('GET', 'courses').then((r) => r.data),
    course: (slug) => request('GET', `courses/${slug}`).then((r) => r.data),
    lesson: (course, lesson) => request('GET', `courses/${course}/lessons/${lesson}`),
    run: (lessonId, sql) => request('POST', `lessons/${lessonId}/run`, { sql }),
    answer: (questionId, option) => request('POST', `quiz-questions/${questionId}/answer`, { option }),
    check: (challengeId, sql) => request('POST', `challenges/${challengeId}/check`, { sql }),
};
