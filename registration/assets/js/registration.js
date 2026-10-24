
document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ==========================================
    // LIVE STUDENT SEARCH
    // ==========================================
    const searchInput = document.getElementById('registrationSearch');
    const searchForm = document.getElementById('registrationSearchForm');
    const studentTable = document.querySelector('.registration-table');
    const registrationCount = document.querySelector('.registration-count');

    let emptyRow = null;

    function filterStudents() {
        if (!searchInput || !studentTable) return;

        const query = searchInput.value.trim().toLowerCase();
        const rows = Array.from(
            studentTable.querySelectorAll('tbody tr')
        ).filter(row => !row.classList.contains('registration-search-empty'));

        let visibleCount = 0;

        rows.forEach(row => {
            const matches = row.textContent.toLowerCase().includes(query);

            row.hidden = !matches;

            if (matches) {
                visibleCount++;
            }
        });

        if (registrationCount) {
            registrationCount.textContent = query
                ? `${visibleCount} matching record${visibleCount === 1 ? '' : 's'}`
                : `${rows.length} record${rows.length === 1 ? '' : 's'}`;
        }

        if (query && visibleCount === 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.className = 'registration-search-empty';

                const cell = document.createElement('td');
                cell.colSpan = studentTable.querySelectorAll('thead th').length || 1;
                cell.textContent = 'No matching student records found.';
                cell.style.textAlign = 'center';
                cell.style.padding = '20px';

                emptyRow.appendChild(cell);
            }

            studentTable.querySelector('tbody')?.appendChild(emptyRow);
        } else {
            emptyRow?.remove();
        }
    }

    searchInput?.addEventListener('input', filterStudents);

    searchForm?.addEventListener('submit', event => {
        event.preventDefault();
        filterStudents();
    });

    // ==========================================
    // ADD / EDIT STUDENT MODAL
    // ==========================================
    const modal = document.getElementById('studentModal');
    const modalForm = document.getElementById('studentModalForm');
    const openAddButton = document.getElementById('openAddModal');
    const saveButton = document.getElementById('saveStudentButton');
    const message = document.getElementById('studentModalMessage');

    const fields = {
        studentId: document.getElementById('modalStudentId'),
        studentNumber: document.getElementById('modalStudentNumber'),
        firstName: document.getElementById('modalFirstName'),
        middleName: document.getElementById('modalMiddleName'),
        lastName: document.getElementById('modalLastName'),
        email: document.getElementById('modalEmail'),
        phone: document.getElementById('modalPhone'),
        yearLevel: document.getElementById('modalYearLevel'),
        course: document.getElementById('modalCourse')
    };

    function showMessage(text, type = '') {
        if (!message) return;

        message.textContent = text;
        message.className = 'student-modal-message';

        if (type) {
            message.classList.add(type);
        }
    }

    function openModal() {
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('registration-modal-open');

        const firstField = fields.studentNumber;
        firstField?.focus();
    }

    function closeModal() {
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('registration-modal-open');

        showMessage('');
    }

    function resetAddForm() {
        modalForm?.reset();

        if (fields.studentId) {
            fields.studentId.value = '';
        }

        const heading = modal?.querySelector(
            '[data-modal-title], .registration-modal-title'
        );

        if (heading) {
            heading.textContent = 'Register Student';
        }

        if (saveButton) {
            saveButton.textContent = 'Save Student';
        }

        showMessage('');
    }

    function setField(field, value) {
        if (field) {
            field.value = value ?? '';
        }
    }

    openAddButton?.addEventListener('click', () => {
        resetAddForm();
        openModal();
    });

    document.querySelectorAll('.open-edit-modal').forEach(button => {
        button.addEventListener('click', () => {
            resetAddForm();

            const data = button.dataset;

            setField(fields.studentId, data.studentId);
            setField(fields.studentNumber, data.studentNumber);
            setField(fields.firstName, data.firstName);
            setField(fields.middleName, data.middleName);
            setField(fields.lastName, data.lastName);
            setField(fields.email, data.email);
            setField(fields.phone, data.phone);
            setField(fields.yearLevel, data.yearLevel);
            setField(fields.course, data.courseId);

            const heading = modal?.querySelector(
                '[data-modal-title], .registration-modal-title'
            );

            if (heading) {
                heading.textContent = 'Edit Student';
            }

            if (saveButton) {
                saveButton.textContent = 'Update Student';
            }

            openModal();
        });
    });

    // Close buttons and backdrop
    modal?.querySelectorAll(
        '[data-close-modal], .registration-modal-close'
    ).forEach(button => {
        button.addEventListener('click', closeModal);
    });

    modal?.addEventListener('click', event => {
        if (
            event.target === modal ||
            event.target.classList.contains('registration-modal-backdrop')
        ) {
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
            closeModal();
        }
    });

    // ==========================================
    // AJAX SAVE / UPDATE STUDENT
    // ==========================================
    if (modalForm) {
        modalForm.addEventListener('submit', async event => {
            event.preventDefault();

            if (saveButton) {
                saveButton.disabled = true;
            }

            showMessage('Saving student information...');

            try {
                const response = await fetch(modalForm.action, {
                    method: 'POST',
                    body: new FormData(modalForm),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                // Read text first to safely handle HTML/PHP error responses.
                const responseText = await response.text();
                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error(
                        'Invalid JSON response from save.php:',
                        responseText
                    );

                    throw new Error(
                        'The server returned HTML or invalid JSON. ' +
                        'Check the save.php response in your browser Network tab.'
                    );
                }

                if (!response.ok || result.success !== true) {
                    throw new Error(
                        result.message || `Request failed (${response.status}).`
                    );
                }

                showMessage(
                    result.message || 'Student saved successfully.',
                    'success'
                );

                if (saveButton) {
                    saveButton.disabled = true;
                }

                window.setTimeout(() => {
                    window.location.reload();
                }, 700);

            } catch (error) {
                console.error('Student save failed:', error);

                showMessage(
                    error.message || 'An unexpected error occurred.',
                    'error'
                );

            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                }
            }
        });
    }
});
