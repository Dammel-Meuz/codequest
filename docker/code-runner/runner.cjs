'use strict';

const fs = require('node:fs');
const vm = require('node:vm');

const MAX_LOG_ENTRIES = 20;
const MAX_LOG_BYTES = 8192;
const MAX_VALUE_BYTES = 16384;
const EXECUTION_TIMEOUT_MS = 1500;

const getArguments = (input) => {
    if (input && typeof input === 'object' && !Array.isArray(input)) {
        return {
            args: Array.isArray(input.args) ? input.args : [],
            functionName: input.function || input.function_name || input.functionName || input.name || null,
        };
    }

    if (Array.isArray(input)) return { args: input, functionName: null };
    if (typeof input !== 'undefined' && input !== null) return { args: [input], functionName: null };

    return { args: [], functionName: null };
};

const executeTest = ({ source_code: sourceCode, input_data: input }) => {
    const { args, functionName } = getArguments(input);
    const context = vm.createContext(Object.create(null), {
        codeGeneration: { strings: false, wasm: false },
    });

    vm.runInContext(`(() => {
        const logs = [];
        let loggedBytes = 0;
        const safeStringify = JSON.stringify.bind(JSON);
        const safeParse = JSON.parse.bind(JSON);
        const safeSerialize = (value) => {
            if (typeof value === 'undefined') return null;
            const json = safeStringify(value);
            if (typeof json === 'undefined') return null;
            if (json.length > ${MAX_VALUE_BYTES}) throw new Error('La sortie dépasse la taille maximale.');
            return safeParse(json);
        };
        const safeLog = (...values) => {
            if (logs.length >= ${MAX_LOG_ENTRIES}) return;
            const entry = safeSerialize(values.length === 1 ? values[0] : values);
            const json = safeStringify(entry);
            if (loggedBytes + json.length > ${MAX_LOG_BYTES}) return;
            loggedBytes += json.length;
            logs.push(entry);
        };

        Object.defineProperty(globalThis, '__codequest_logs', { value: logs });
        Object.defineProperty(globalThis, '__codequest_stringify', {
            value: safeStringify,
        });
        Object.defineProperty(globalThis, '__codequest_parse', {
            value: safeParse,
        });
        Object.defineProperty(globalThis, 'console', {
            value: Object.freeze({ log: safeLog, warn: () => {}, error: () => {}, info: () => {} }),
        });
    })()`, context, { timeout: 100 });

    try {
        vm.runInContext(sourceCode, context, {
            timeout: EXECUTION_TIMEOUT_MS,
            filename: 'submission.js',
        });

        const invocation = `(() => {
            const logs = globalThis.__codequest_logs;
            const safeStringify = globalThis.__codequest_stringify;
            const safeParse = globalThis.__codequest_parse;
            const args = ${JSON.stringify(args)};
            const requestedFunction = ${JSON.stringify(functionName)};

            try {
                let candidate = requestedFunction && Object.prototype.hasOwnProperty.call(globalThis, requestedFunction)
                    ? globalThis[requestedFunction]
                    : null;

                if (typeof candidate !== 'function') {
                    for (const name of ['solution', 'main', 'solve', 'run']) {
                        if (Object.prototype.hasOwnProperty.call(globalThis, name) && typeof globalThis[name] === 'function') {
                            candidate = globalThis[name];
                            break;
                        }
                    }
                }

                const actual = typeof candidate === 'function'
                    ? candidate(...args)
                    : logs.length === 1
                        ? logs[0]
                        : logs.length > 1
                            ? logs
                            : Object.prototype.hasOwnProperty.call(globalThis, '__CODEQUEST_RESULT__')
                                ? globalThis.__CODEQUEST_RESULT__
                                : null;
                const json = safeStringify(actual === undefined ? null : actual);
                if (json.length > ${MAX_VALUE_BYTES}) throw new Error('La sortie dépasse la taille maximale.');
                return safeStringify({ actual: safeParse(json) });
            } catch (error) {
                return safeStringify({
                    actual: null,
                    error: error && error.message
                        ? String(error.message).slice(0, 1000)
                        : 'Erreur pendant l’exécution du code.',
                });
            }
        })()`;

        return JSON.parse(vm.runInContext(invocation, context, { timeout: 100 }));
    } catch (error) {
        return {
            actual: null,
            error: error && error.message
                ? String(error.message).slice(0, 1000)
                : 'La durée maximale d’exécution a été dépassée.',
        };
    }
};

try {
    const payload = JSON.parse(fs.readFileSync('/tmp/input.json', 'utf8'));
    process.stdout.write(JSON.stringify(executeTest(payload)));
} catch (error) {
    process.stderr.write((error && error.message ? error.message : 'Entrée de correction invalide.').slice(0, 2000));
    process.exitCode = 1;
}
