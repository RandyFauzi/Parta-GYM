import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../../', import.meta.url));
const python = fileURLToPath(new URL(process.platform === 'win32'
    ? '../../.codex/tools/graphify-venv/Scripts/python.exe'
    : '../../.codex/tools/graphify-venv/bin/python', import.meta.url));

if (!existsSync(python)) {
    console.error('Install Graphify into .codex/tools/graphify-venv first; see README.md.');
    process.exit(1);
}

const args = process.argv.slice(2);
const command = args[0] === 'mcp'
    ? ['-m', 'graphify.serve', '--graph', 'graphify-out/graph.json', '--transport', 'stdio']
    : ['-m', 'graphify', ...args];
const child = spawn(python, command, { cwd: root, stdio: 'inherit' });
child.on('error', (error) => { console.error(error.message); process.exitCode = 1; });
child.on('exit', (code) => { process.exitCode = code ?? 1; });
for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => child.kill(signal));
}
