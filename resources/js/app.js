
import './bootstrap';

const draftSaveQueues = new Map();

document.addEventListener('livewire:navigated', initializeMonaco);
document.addEventListener('livewire:navigated', initializeMobileMenu);

document.addEventListener('DOMContentLoaded', initializeMonaco);
document.addEventListener('DOMContentLoaded', initializeMobileMenu);

function setMobileMenuOpen(isOpen, restoreFocus = false) {
    const panel = document.getElementById('primary-navigation');
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const backdrop = document.querySelector('[data-mobile-menu-backdrop]');

    if (!panel || !toggle || !backdrop) {
        return;
    }

    const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
    const open = isOpen && !isDesktop;

    panel.classList.toggle('invisible', !open && !isDesktop);
    panel.classList.toggle('-translate-x-full', !open && !isDesktop);
    panel.classList.toggle('visible', open || isDesktop);
    panel.classList.toggle('translate-x-0', open || isDesktop);
    panel.setAttribute('aria-hidden', String(!open && !isDesktop));
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');

    backdrop.classList.toggle('invisible', !open);
    backdrop.classList.toggle('pointer-events-none', !open);
    backdrop.classList.toggle('pointer-events-auto', open);
    backdrop.classList.toggle('opacity-0', !open);
    backdrop.classList.toggle('opacity-100', open);

    document.body.classList.toggle('overflow-hidden', open);

    if (open) {
        panel.querySelector('[data-mobile-menu-close]')?.focus();
    } else if (restoreFocus && !isDesktop) {
        toggle.focus();
    }
}

function initializeMobileMenu() {
    if (!document.getElementById('primary-navigation')) {
        return;
    }

    if (document.documentElement.dataset.mobileMenuInitialized !== 'true') {
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target.closest('[data-mobile-menu-toggle]')) {
                const toggle = document.querySelector('[data-mobile-menu-toggle]');
                setMobileMenuOpen(toggle?.getAttribute('aria-expanded') !== 'true');
                return;
            }

            if (event.target.closest('[data-mobile-menu-close], [data-mobile-menu-backdrop]')) {
                setMobileMenuOpen(false, true);
                return;
            }

            if (event.target.closest('#primary-navigation a, #primary-navigation button[type="submit"]')) {
                setMobileMenuOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            const menuIsOpen = document.querySelector('[data-mobile-menu-toggle]')
                ?.getAttribute('aria-expanded') === 'true';

            if (event.key === 'Escape' && menuIsOpen) {
                setMobileMenuOpen(false, true);
                return;
            }

            if (event.key === 'Tab' && menuIsOpen) {
                const panel = document.getElementById('primary-navigation');
                const focusableElements = panel?.querySelectorAll('a[href], button:not([disabled])');
                const firstElement = focusableElements?.[0];
                const lastElement = focusableElements?.[focusableElements.length - 1];

                if (event.shiftKey && document.activeElement === firstElement) {
                    event.preventDefault();
                    lastElement?.focus();
                } else if (!event.shiftKey && document.activeElement === lastElement) {
                    event.preventDefault();
                    firstElement?.focus();
                }
            }
        });

        window.matchMedia('(min-width: 1024px)').addEventListener('change', () => {
            setMobileMenuOpen(false);
        });

        document.documentElement.dataset.mobileMenuInitialized = 'true';
    }

    setMobileMenuOpen(false);
}

document.addEventListener('livewire:init', () => {
    Livewire.on('exercise-code-restored', ({ code }) => {
        const container = document.getElementById('monaco-editor');
        const editor = container?._codequestEditor;

        if (editor) {
            container._restoringSubmittedCode = true;
            editor.setValue(code);
            container._restoringSubmittedCode = false;
        }
    });
});

async function initializeMonaco() {
    const container = document.getElementById('monaco-editor');

    if (!container || container.dataset.initialized === 'true' || container.dataset.initializing === 'true') {
        return;
    }

    container.dataset.initializing = 'true';
    const status = document.getElementById('monaco-editor-status');

    if (status) {
        status.textContent = 'Chargement de l’éditeur…';
    }

    try {
        const monaco = await import('monaco-editor');

        if (!container.isConnected) {
            return;
        }

        const editor = monaco.editor.create(container, {
            value: container.dataset.code || '// Écris ton code JavaScript ici',
            language: 'javascript',
            theme: 'vs-dark',
            automaticLayout: true,
            minimap: { enabled: false },
            fontSize: 14,
            lineNumbers: 'on',
            roundedSelection: false,
            scrollBeyondLastLine: false,
            wordWrap: 'on',
            tabSize: 2,
            insertSpaces: true,
            ariaLabel: 'Éditeur de code JavaScript',
        });
        container._codequestEditor = editor;

        editor.onDidChangeModelContent(() => {
            if (container._restoringSubmittedCode) {
                return;
            }

            const componentElement = container.closest('[wire\\:id]');
            const componentId = componentElement?.getAttribute('wire:id');

            if (componentId) {
                const component = Livewire.find(componentId);
                const sourceCode = editor.getValue();
                const draftStatus = document.getElementById('exercise-draft-status');

                component?.set('sourceCode', sourceCode, false);

                if (draftStatus) {
                    draftStatus.textContent = 'Modifications à sauvegarder…';
                    draftStatus.classList.remove('text-red-300');
                }

                clearTimeout(container._draftSaveTimer);
                container._draftSaveTimer = setTimeout(() => {
                    if (!component) {
                        return;
                    }

                    if (draftStatus) {
                        draftStatus.textContent = 'Sauvegarde du brouillon…';
                    }

                    const previousSave = draftSaveQueues.get(componentId) || Promise.resolve();
                    const save = previousSave
                        .catch(() => {})
                        .then(() => component.$wire.saveDraft(sourceCode));
                    draftSaveQueues.set(componentId, save);

                    save
                        .then(() => {
                            if (draftStatus && editor.getValue() === sourceCode) {
                                draftStatus.textContent = 'Brouillon enregistré';
                            }
                        })
                        .catch((error) => {
                            if (draftStatus && editor.getValue() === sourceCode) {
                                draftStatus.textContent = 'Échec de la sauvegarde. Réessaie.';
                                draftStatus.classList.add('text-red-300');
                            }

                            console.error('Échec de la sauvegarde du brouillon.', error);
                        });
                }, 800);
            }
        });

        container.dataset.initialized = 'true';
        if (status) {
            status.textContent = 'Éditeur prêt. Tu peux écrire ou coller ton code JavaScript.';
        }
    } catch (error) {
        if (status) {
            status.textContent = 'Impossible de charger l’éditeur. Recharge la page pour réessayer.';
            status.classList.add('text-red-300');
        }

        console.error('Échec du chargement de Monaco Editor.', error);
    } finally {
        delete container.dataset.initializing;
    }
}
