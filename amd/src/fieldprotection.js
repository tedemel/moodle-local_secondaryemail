import Log from 'core/log';

export const init = (params) => {
    if (!params) {
        return;
    }

    const fieldId = Number(params.fieldId || 0);
    const categoryId = Number(params.categoryId || 0);
    const strings = params.strings || {};

    if (!fieldId) {
        return;
    }

    const lockedLabel = strings.lockedLabel || '';
    const fieldLockedMsg = strings.fieldLockedMsg || '';
    const categoryLockedMsg = strings.categoryLockedMsg || '';
    const fieldWarning = strings.fieldWarning || '';
    const categoryWarning = strings.categoryWarning || '';
    const lockedByPlugin = strings.lockedByPlugin || '';
    const categoryName = strings.categoryName || '';

    const disableLink = (link, msg) => {
        link.removeAttribute('href');
        link.setAttribute('data-locked', 'true');
        link.classList.add('secondaryemail-locked-link');
        link.title = msg;
        link.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            Log.warn(msg);
            return false;
        }, true);
    };

    const lockFormFields = (modal, isField) => {
        if (modal.getAttribute('data-secondaryemail-locked')) {
            return;
        }
        modal.setAttribute('data-secondaryemail-locked', 'true');

        const modalBody = modal.querySelector('.modal-body');
        if (modalBody && !modalBody.querySelector('.secondaryemail-warning')) {
            const warning = document.createElement('div');
            warning.className = 'alert alert-warning d-flex align-items-center mb-3 secondaryemail-warning';
            warning.innerHTML = '<i class="fa fa-lock fa-2x me-3"></i><div><strong>' +
                (isField ? fieldWarning : categoryWarning) + '</strong></div>';
            modalBody.insertBefore(warning, modalBody.firstChild);
        }

        const fieldsToLock = isField ? ['shortname', 'name', 'datatype', 'visible'] : ['name'];

        fieldsToLock.forEach((fieldName) => {
            const input = modal.querySelector('[name="' + fieldName + '"], #id_' + fieldName);
            if (input && !input.getAttribute('data-locked')) {
                input.setAttribute('data-locked', 'true');
                input.disabled = true;
                input.readOnly = true;
                input.classList.add('secondaryemail-locked-input');

                const container = input.closest('.fitem, .form-group, .row, .col-md-9')?.parentElement || input.parentElement;
                const label = container?.querySelector('label, .col-form-label');
                if (label && !label.querySelector('.fa-lock')) {
                    const lockIcon = document.createElement('i');
                    lockIcon.className = 'fa fa-lock text-warning ms-1';
                    lockIcon.title = lockedByPlugin;
                    label.appendChild(lockIcon);
                }
            }
        });

        const selectsToLock = modal.querySelectorAll('select[name="datatype"], select[name="visible"]');
        selectsToLock.forEach((select) => {
            if (!select.getAttribute('data-locked')) {
                select.setAttribute('data-locked', 'true');
                select.disabled = true;
                select.classList.add('secondaryemail-locked-input');
            }
        });
    };

    const hrefHasId = (href, id) => href.indexOf('id=' + id + '&') !== -1 ||
        href.indexOf('id=' + id + '"') !== -1 ||
        href.endsWith('id=' + id) ||
        href.indexOf('&id=' + id) !== -1;

    const hideLink = (link) => {
        link.style.display = 'none';
        link.setAttribute('data-locked', 'true');
    };

    const addLockedBadge = (link) => {
        const row = link.closest('tr') || link.closest('li') || link.parentElement;
        if (row && !row.querySelector('.secondaryemail-locked-badge')) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-warning text-bg-warning ms-2 secondaryemail-locked-badge';
            badge.textContent = lockedLabel;
            badge.title = fieldLockedMsg;
            const nameCell = row.querySelector('td:first-child, span.text-break') || row;
            if (nameCell) {
                nameCell.appendChild(badge);
            }
        }
    };

    const processLink = (link) => {
        if (link.getAttribute('data-locked')) {
            return;
        }
        const href = link.getAttribute('href') || '';
        const isFieldLink = hrefHasId(href, fieldId);

        if (href.indexOf('action=editfield') !== -1 && isFieldLink) {
            disableLink(link, fieldLockedMsg);
            addLockedBadge(link);
        }

        if (href.indexOf('action=deletefield') !== -1 && isFieldLink) {
            hideLink(link);
        }

        const isCategoryAction = href.indexOf('action=editcategory') !== -1 ||
            href.indexOf('action=deletecategory') !== -1 ||
            href.indexOf('action=movecategory') !== -1;
        if (isCategoryAction && hrefHasId(href, categoryId)) {
            hideLink(link);
        }
    };

    const lockField = () => {
        document.querySelectorAll('a[href]').forEach(processLink);

        document.querySelectorAll('[data-inplaceeditable]').forEach((editableEl) => {
            const text = editableEl.textContent || '';
            const isOurCategory = (text.indexOf(categoryName) !== -1);

            if (isOurCategory && !editableEl.getAttribute('data-secondaryemail-locked')) {
                editableEl.setAttribute('data-secondaryemail-locked', 'true');

                const pencilIcon = editableEl.querySelector(
                    'a.quickeditlink, .quickediticon, i.fa-pencil, i[class*="pencil"], a[title]'
                );
                if (pencilIcon) {
                    pencilIcon.style.display = 'none';
                }

                editableEl.querySelectorAll('a').forEach((a) => {
                    if (a.querySelector('i') || a.classList.contains('quickeditlink')) {
                        a.style.display = 'none';
                    }
                });

                editableEl.style.pointerEvents = 'none';
                editableEl.removeAttribute('data-inplaceeditablelink');

                if (!editableEl.parentElement.querySelector('.secondaryemail-category-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'secondaryemail-category-badge';
                    badge.innerHTML = '<i class="fa fa-lock"></i>';
                    badge.title = categoryLockedMsg;
                    editableEl.after(badge);
                }
            }
        });

        document.querySelectorAll('h3, h4').forEach((header) => {
            const text = header.textContent || '';
            const isOurCategory = (text.indexOf(categoryName) !== -1);

            if (isOurCategory && !header.querySelector('.secondaryemail-category-badge')) {
                header.querySelectorAll('a, i.fa-pencil, i[class*="pencil"]').forEach((el) => {
                    if (el.tagName === 'A' && el.querySelector('i')) {
                        el.style.display = 'none';
                    }
                });

                const badge = document.createElement('span');
                badge.className = 'secondaryemail-category-badge';
                badge.innerHTML = '<i class="fa fa-lock"></i>';
                badge.title = categoryLockedMsg;
                header.appendChild(badge);
            }
        });

        document.querySelectorAll('.modal, .moodle-dialogue').forEach((modal) => {
            const form = modal.querySelector('form');
            if (!form) {
                return;
            }

            const shortnameInput = form.querySelector('[name="shortname"]');
            const shortname = shortnameInput ? shortnameInput.value : '';

            if (shortname === 'secondaryemail') {
                lockFormFields(modal, true);
            }

            const idInput = form.querySelector('[name="id"]');
            const actionInput = form.querySelector('[name="action"]');
            if (idInput && actionInput && actionInput.value === 'editcategory' && parseInt(idInput.value, 10) === categoryId) {
                lockFormFields(modal, false);
            }
        });
    };

    lockField();

    const observer = new MutationObserver(() => {
        window.setTimeout(lockField, 100);
    });
    observer.observe(document.body, {childList: true, subtree: true});
};
