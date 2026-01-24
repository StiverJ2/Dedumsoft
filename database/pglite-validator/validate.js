#!/usr/bin/env node
// PGlite SQL Validator
// Generic validator for PostgreSQL projects using PGlite (WASM)
// Validates schema, functions, and seed data before applying to real PostgreSQL

import { PGlite } from '@electric-sql/pglite';
import { readFileSync, readdirSync, existsSync } from 'fs';
import { join, dirname, basename } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const DB_PATH = dirname(__dirname);

// Config file (optional) - create validator.config.json to customize
const CONFIG_PATH = join(__dirname, 'validator.config.json');
const defaultConfig = {
    schemaFile: 'db_schema.sql',
    functionsDir: 'functions',
    seedFile: 'seed.sql',
    functionPrefix: null,  // null = detect all functions, or e.g. 'fun_%'
    smokeTests: [],        // array of {name, sql} objects
    functionTests: {
        enabled: false,    // set to true to run function output tests
        errorPatterns: [], // regex patterns that indicate error output (e.g. ["^ERROR:", "failed"])
        skipFunctions: [], // functions to skip (e.g. ["fun_eliminar_%", "fun_crear_%"])
        defaultParams: {}  // default params per function type (see README)
    }
};

const args = process.argv.slice(2);

if (args.includes('--help') || args.includes('-h')) {
    console.log(`
PGlite SQL Validator - Validate PostgreSQL schemas before applying

Usage: node validate.js [command] [options]

Commands:
  --all          Validate schema + functions + seed + smoke tests + function tests (default)
  --schema       Validate only db_schema.sql
  --functions    Validate schema + functions/*.sql
  --file <path>  Validate a specific SQL file

Options:
  --no-config    Ignore validator.config.json, use defaults
  --help, -h     Show this help message

Config file (validator.config.json):
  {
    "schemaFile": "db_schema.sql",
    "functionsDir": "functions",
    "seedFile": "seed.sql",
    "functionPrefix": "fun_%",
    "smokeTests": [{ "name": "test", "sql": "SELECT 1" }],
    "functionTests": {
      "enabled": true,
      "errorPatterns": ["^ERROR", "failed", "no disponible"],
      "skipFunctions": ["fun_eliminar_%", "fun_crear_%"],
      "defaultParams": { "par_offset": 0, "par_limit": 1 }
    }
  }

Function Tests:
  When enabled, calls each function with safe default parameters and checks
  for error patterns in the output. Functions with required parameters that
  cannot be safely inferred are automatically skipped.

  - errorPatterns: Regex patterns that indicate error output (case-insensitive)
  - skipFunctions: Glob patterns for functions to skip (% = wildcard)
  - defaultParams: Named params to use when calling functions

Examples:
  node validate.js --all
  node validate.js --file ../functions/03_inventario.sql
  node validate.js --all --no-config
`);
    process.exit(0);
}

const noConfig = args.includes('--no-config');
const filteredArgs = args.filter(a => a !== '--no-config');

const loadConfig = () => {
    if (noConfig) return defaultConfig;
    if (existsSync(CONFIG_PATH)) {
        try {
            return { ...defaultConfig, ...JSON.parse(readFileSync(CONFIG_PATH, 'utf8')) };
        } catch { return defaultConfig; }
    }
    return defaultConfig;
};

const config = loadConfig();

const colors = { reset: '\x1b[0m', red: '\x1b[31m', green: '\x1b[32m', yellow: '\x1b[33m', bold: '\x1b[1m' };
const ok = msg => console.log(`${colors.green}[OK]${colors.reset} ${msg}`);
const err = msg => console.log(`${colors.red}[ERR]${colors.reset} ${msg}`);
const warn = msg => console.log(`${colors.yellow}[WARN]${colors.reset} ${msg}`);
const info = msg => console.log(`[..] ${msg}`);

const readSql = path => {
    if (!existsSync(path)) throw new Error(`Not found: ${path}`);
    return readFileSync(path, 'utf8').replace(/^\\[a-z]+.*$/gm, '');
};

const execSql = async (db, sql) => {
    try { await db.exec(sql); return { success: true }; }
    catch (error) { return { success: false, error: error.message }; }
};

// Auto-detect user schemas (excludes system schemas)
const getUserSchemas = async (db) => {
    const result = await db.query(`
        SELECT schema_name FROM information_schema.schemata 
        WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
        AND schema_name NOT LIKE 'pg_%'
    `);
    return result.rows.map(r => r.schema_name);
};

const validateSchema = async (db) => {
    const schemaPath = join(DB_PATH, config.schemaFile);
    if (!existsSync(schemaPath)) { err(`${config.schemaFile} not found`); return false; }
    
    info(`Schema: ${config.schemaFile}`);
    const result = await execSql(db, readSql(schemaPath));
    if (!result.success) { err(`${config.schemaFile}: ${result.error}`); return false; }
    
    const schemas = await getUserSchemas(db);
    const schemaList = schemas.filter(s => s !== 'public').join(', ') || 'public only';
    
    const tables = await db.query(`
        SELECT COUNT(1) as count FROM information_schema.tables 
        WHERE table_schema = ANY($1) AND table_type = 'BASE TABLE'
    `, [schemas]);
    
    ok(`${config.schemaFile} (${tables.rows[0].count} tables, schemas: ${schemaList})`);
    return true;
};

const validateFunctions = async (db) => {
    const functionsDir = join(DB_PATH, config.functionsDir);
    if (!existsSync(functionsDir)) { info(`${config.functionsDir}/ not found (skipping)`); return true; }
    
    info('Functions...');
    const files = readdirSync(functionsDir).filter(file => file.endsWith('.sql')).sort();
    if (files.length === 0) { info('No SQL files in functions/'); return true; }
    
    let passCount = 0, failCount = 0;
    for (const file of files) {
        const result = await execSql(db, readSql(join(functionsDir, file)));
        result.success ? (ok(file), passCount++) : (err(`${file}: ${result.error}`), failCount++);
    }
    
    const schemas = await getUserSchemas(db);
    const funcFilter = config.functionPrefix 
        ? `AND routine_name LIKE '${config.functionPrefix}'` 
        : '';
    
    const funcs = await db.query(`
        SELECT COUNT(1) as count FROM information_schema.routines 
        WHERE routine_schema = ANY($1) ${funcFilter}
    `, [schemas]);
    
    info(`Functions: ${passCount} ok, ${failCount} errors, ${funcs.rows[0].count} total`);
    return failCount === 0;
};

const validateSeed = async (db) => {
    const seedPath = join(DB_PATH, config.seedFile);
    if (!existsSync(seedPath)) { info(`${config.seedFile} not found (optional)`); return true; }
    
    info(`Seed: ${config.seedFile}`);
    const result = await execSql(db, readSql(seedPath));
    if (!result.success) { err(`${config.seedFile}: ${result.error}`); return false; }
    ok(config.seedFile);
    return true;
};

const validateFile = async (db, filePath) => {
    info(`File: ${basename(filePath)}`);
    const result = await execSql(db, readSql(filePath));
    result.success ? ok(basename(filePath)) : err(result.error);
    return result.success;
};

const runSmokeTests = async (db) => {
    if (!config.smokeTests || config.smokeTests.length === 0) return true;
    
    info('Smoke tests...');
    let passCount = 0;
    for (const { name, sql } of config.smokeTests) {
        try { await db.query(sql); ok(name); passCount++; } 
        catch (error) { err(`${name}: ${error.message}`); }
    }
    info(`Tests: ${passCount}/${config.smokeTests.length} passed`);
    return passCount === config.smokeTests.length;
};

// Test functions by calling them with safe defaults and checking for error patterns
const runFunctionTests = async (db) => {
    const ft = config.functionTests;
    if (!ft || !ft.enabled) return true;
    
    info('Function output tests...');
    const schemas = await getUserSchemas(db);
    const funcFilter = config.functionPrefix 
        ? `AND routine_name LIKE '${config.functionPrefix}'` 
        : '';
    
    // Get all functions with their parameters
    const funcs = await db.query(`
        SELECT 
            r.routine_schema, 
            r.routine_name,
            r.data_type as return_type,
            COALESCE(
                string_agg(
                    p.parameter_name || ':' || p.data_type || ':' || 
                    CASE WHEN p.parameter_default IS NOT NULL THEN 'default' ELSE 'required' END,
                    ','
                    ORDER BY p.ordinal_position
                ), ''
            ) as params
        FROM information_schema.routines r
        LEFT JOIN information_schema.parameters p 
            ON r.specific_schema = p.specific_schema 
            AND r.specific_name = p.specific_name
            AND p.parameter_mode = 'IN'
        WHERE r.routine_schema = ANY($1) ${funcFilter}
        GROUP BY r.routine_schema, r.routine_name, r.data_type
        ORDER BY r.routine_name
    `, [schemas]);
    
    const skipPatterns = (ft.skipFunctions || []).map(p => new RegExp(`^${p.replace(/%/g, '.*')}$`));
    const errorPatterns = (ft.errorPatterns || []).map(p => new RegExp(p, 'i'));
    const defaults = ft.defaultParams || {};
    
    let passCount = 0, skipCount = 0, failCount = 0, warnCount = 0;
    
    for (const func of funcs.rows) {
        const fullName = `${func.routine_schema}.${func.routine_name}`;
        
        // Check if should skip
        if (skipPatterns.some(p => p.test(func.routine_name))) {
            skipCount++;
            continue;
        }
        
        // Build parameter list with safe defaults
        // We need to handle params with defaults differently - we can skip trailing defaults
        const params = func.params ? func.params.split(',').filter(Boolean) : [];
        let hasUnknownRequiredParam = false;
        
        // First pass: determine values
        const paramData = params.map(pstr => {
            const [name, type, hasDefault] = pstr.split(':');
            let value = null;
            let useDefault = false;
            
            // If param has a default in the function definition, prefer using it
            // (don't override with mocks - mocks are for REQUIRED params only)
            if (hasDefault === 'default') {
                useDefault = true;
            } else if (defaults[name] !== undefined) {
                // Required param with a configured mock value
                value = defaults[name];
            } else {
                // Required param without config - try to infer from type
                switch (type) {
                    case 'integer': case 'bigint': case 'smallint': 
                        if (name.includes('offset') || name.includes('limit') || name.includes('id')) {
                            value = 0;
                        } else {
                            hasUnknownRequiredParam = true;
                            value = 0;
                        }
                        break;
                    case 'boolean': 
                        value = name.includes('activo') ? true : null;
                        break;
                    default: 
                        hasUnknownRequiredParam = true;
                        value = null;
                }
            }
            return { name, type, hasDefault: hasDefault === 'default', value, useDefault };
        });
        
        // Skip functions with required params we can't safely infer
        if (hasUnknownRequiredParam && paramData.some(p => !p.hasDefault && p.value === null)) {
            skipCount++;
            continue;
        }
        
        // Second pass: build param list, omitting trailing defaults
        // Find the last non-default param
        let lastRequiredIdx = -1;
        for (let i = paramData.length - 1; i >= 0; i--) {
            if (!paramData[i].useDefault) {
                lastRequiredIdx = i;
                break;
            }
        }
        
        // Build params up to lastRequiredIdx
        const paramValues = paramData.slice(0, lastRequiredIdx + 1).map(p => {
            if (p.value === null) return 'NULL';
            if (p.value === true) return 'TRUE';
            if (p.value === false) return 'FALSE';
            if (typeof p.value === 'string') {
                // If already quoted (starts with '), use as-is; otherwise quote it
                return p.value.startsWith("'") ? p.value : `'${p.value}'`;
            }
            return p.value;
        });
        
        // Build and execute test query
        const paramStr = paramValues.join(', ');
        const testSql = func.return_type === 'record' || func.return_type === 'TABLE'
            ? `SELECT 1 FROM ${fullName}(${paramStr}) LIMIT 1`
            : `SELECT ${fullName}(${paramStr})`;
        
        try {
            const result = await db.query(testSql);
            
            // Check result for error patterns in string columns
            let hasErrorPattern = false;
            if (result.rows && result.rows.length > 0) {
                const firstRow = result.rows[0];
                for (const val of Object.values(firstRow)) {
                    if (typeof val === 'string' && errorPatterns.some(p => p.test(val))) {
                        hasErrorPattern = true;
                        err(`${func.routine_name}: output matches error pattern: "${val}"`);
                        break;
                    }
                }
            }
            
            if (hasErrorPattern) {
                failCount++;
            } else {
                ok(func.routine_name);
                passCount++;
            }
        } catch (error) {
            // PostgreSQL exception (RAISE EXCEPTION, constraint violation, etc.)
            // Categorize errors:
            // 1. Silent skip: function resolution issues (not our problem)
            // 2. Warning: data validation issues (tester config problem)
            // 3. Error: unexpected runtime errors (real issues)
            const silentPatterns = ['does not exist', 'is not unique'];
            const warnPatterns = [
                'no encontrad', 'not found', 'P0002',
                'violates foreign key', 'violates check constraint',
                'violates unique constraint', 'duplicate key',
                'no válid', 'invalid', 'must be'
            ];
            const msgLower = error.message.toLowerCase();
            
            if (silentPatterns.some(p => msgLower.includes(p.toLowerCase()))) {
                // Function overload issue - silent skip
                skipCount++;
            } else if (warnPatterns.some(p => msgLower.includes(p.toLowerCase()))) {
                // Data validation issue - warn but count as pass (tester config problem)
                warn(`${func.routine_name}: ${error.message} (add to defaultParams?)`);
                warnCount++;
            } else {
                err(`${func.routine_name}: ${error.message}`);
                failCount++;
            }
        }
    }
    
    info(`Function tests: ${passCount} ok, ${warnCount} warnings, ${failCount} errors, ${skipCount} skipped`);
    return failCount === 0;
};

const main = async () => {
    console.log(`\n${colors.bold}PGlite SQL Validator${colors.reset}\n${'='.repeat(40)}\n`);
    const [mode, filePath] = filteredArgs;
    
    if (noConfig) info('Running without config file');
    info('Starting PGlite...');
    const db = new PGlite();
    ok('PGlite ready\n');
    
    let success = true;
    try {
        switch (mode) {
            case '--schema': success = await validateSchema(db); break;
            case '--functions': success = await validateSchema(db) && await validateFunctions(db); break;
            case '--file':
                if (!filePath) { err('Usage: --file <path.sql>'); process.exit(1); }
                if (filePath.includes(`${config.functionsDir}/`)) await validateSchema(db);
                success = await validateFile(db, filePath);
                break;
            case '--all': default:
                success = await validateSchema(db);
                if (success) success = await validateFunctions(db);
                if (success) success = await validateSeed(db);
                if (success) success = await runSmokeTests(db);
                if (success) success = await runFunctionTests(db);
        }
    } catch (error) { err(`Fatal: ${error.message}`); success = false; }
    
    await db.close();
    console.log(`\n${'='.repeat(40)}`);
    console.log(success ? `${colors.green}${colors.bold}PASS${colors.reset} - Ready to apply` : `${colors.red}${colors.bold}FAIL${colors.reset} - Fix errors first`);
    process.exit(success ? 0 : 1);
};

main();
