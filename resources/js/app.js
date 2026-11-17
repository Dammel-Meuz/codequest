
import './bootstrap';

import * as monaco from 'monaco-editor';

window.monaco = monaco;

document.addEventListener('livewire:navigated', initializeMonaco);

document.addEventListener('DOMContentLoaded', initializeMonaco);

function initializeMonaco() {
    const container = document.getElementById('monaco-editor');

    if (!container || container.dataset.initialized === 'true') {
        return;
    }

    container.dataset.initialized = 'true';

    const starterCode = container.dataset.code || '// Écris ton code JavaScript ici';

    const editor = monaco.editor.create(container, {
        value: starterCode,
        language: 'javascript',
        theme: 'vs-dark',
        automaticLayout: true,
        minimap: {
            enabled: false,
        },
        fontSize: 14,
        lineNumbers: 'on',
        roundedSelection: false,
        scrollBeyondLastLine: false,
    });
    editor.onDidChangeModelContent(() => {
        const componentElement = container.closest('[wire\\:id]');

        if (!componentElement) {
            return;
        }

        const component = Livewire.find(componentElement.getAttribute('wire:id'));

        component.set('sourceCode', editor.getValue());
    });
}
