document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    button.addEventListener('click', () => {
        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        button.textContent = isVisible ? 'Показать' : 'Скрыть';
        button.setAttribute('aria-pressed', String(!isVisible));
    });
});

document.querySelectorAll('[data-password-confirmation]').forEach((form) => {
    const password = form.querySelector('[data-password]');
    const confirmation = form.querySelector('[data-password-confirmation-input]');

    if (!(password instanceof HTMLInputElement) || !(confirmation instanceof HTMLInputElement)) {
        return;
    }

    const validateMatch = () => {
        confirmation.setCustomValidity(
            confirmation.value === password.value ? '' : 'Пароли не совпадают.',
        );
    };

    password.addEventListener('input', validateMatch);
    confirmation.addEventListener('input', validateMatch);
    form.addEventListener('submit', validateMatch);
});