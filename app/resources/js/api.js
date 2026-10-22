export class ApiError extends Error {
    /** `errors` holds validation messages by field name, when the server sent them. */
    constructor(message, status, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

/** Laravel sets this cookie; signed-in (session) requests must echo it back. */
function csrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

async function request(method, url, body) {
    let response;

    try {
        response = await fetch(`/api/${url}`, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
                ...(csrfToken() ? { 'X-XSRF-TOKEN': csrfToken() } : {}),
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
                : response.status === 419
                  ? 'Your session has expired. Reload the page and try again.'
                  : (data?.message ?? `The server responded with an error (${response.status}).`);
        throw new ApiError(message, response.status, data?.errors);
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
    reviewItems: (quizQuestionIds, challengeIds) =>
        request('POST', 'review-items', { quiz_questions: quizQuestionIds, challenges: challengeIds }),
    user: () => request('GET', 'user').then((r) => r.data),
    register: (details) => request('POST', 'register', details).then((r) => r.user),
    login: (credentials) => request('POST', 'login', credentials).then((r) => r.user),
    logout: () => request('POST', 'logout'),
    progress: () => request('GET', 'progress'),
    saveProgress: (state) => request('PUT', 'progress', state),
    mergeProgress: (state) => request('POST', 'progress/merge', state),
    forgotPassword: (email) => request('POST', 'forgot-password', { email }),
    resetPassword: (details) => request('POST', 'reset-password', details),
    resendVerification: () => request('POST', 'email/verification-notification'),
    deleteAccount: (confirmation) => request('DELETE', 'account', confirmation),
    tracks: () => request('GET', 'tracks').then((r) => r.data),
    search: (query) => request('GET', `search?q=${encodeURIComponent(query)}`).then((r) => r.data),
    solution: (challengeId) => request('GET', `challenges/${challengeId}/solution`).then((r) => r.solution),
    // Anonymous learning statistics; losing one doesn't matter, so failures are ignored.
    recordAttempt: (challengeId, passed, attempt) =>
        request('POST', `challenges/${challengeId}/attempts`, { passed, attempt }).catch(() => {}),
    recordHint: (challengeId) => request('POST', `challenges/${challengeId}/hint-views`).catch(() => {}),
};
