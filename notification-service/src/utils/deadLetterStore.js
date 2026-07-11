const fs = require('fs');
const path = require('path');
const logger = require('./logger');

const DEAD_LETTER_DIR = process.env.DEAD_LETTER_DIR || path.join(__dirname, '../../data/dead-letter');

/**
 * Ensure the dead-letter directory exists
 */
function ensureDir() {
  if (!fs.existsSync(DEAD_LETTER_DIR)) {
    fs.mkdirSync(DEAD_LETTER_DIR, { recursive: true });
  }
}

/**
 * Persist a failed job to the dead-letter store (JSON file per job)
 */
function persistDeadLetter(queueName, jobData, error) {
  ensureDir();

  const record = {
    id: jobData.id || Date.now(),
    queue: queueName,
    type: jobData.data?.type || 'unknown',
    payload: jobData.data || {},
    error: error?.message || String(error),
    attemptsMade: jobData.attemptsMade || 0,
    failedAt: new Date().toISOString(),
  };

  const filename = `${queueName}_${record.id}_${Date.now()}.json`;
  const filepath = path.join(DEAD_LETTER_DIR, filename);

  try {
    fs.writeFileSync(filepath, JSON.stringify(record, null, 2));
    logger.info(`[DEAD LETTER] Persisted job ${record.id} to ${filepath}`);
  } catch (err) {
    logger.error(`[DEAD LETTER] Failed to persist job ${record.id}: ${err.message}`);
  }

  return record;
}

/**
 * List all dead-letter records
 */
function listDeadLetters() {
  ensureDir();

  const files = fs.readdirSync(DEAD_LETTER_DIR).filter(f => f.endsWith('.json'));
  return files.map(f => {
    try {
      const content = fs.readFileSync(path.join(DEAD_LETTER_DIR, f), 'utf8');
      return JSON.parse(content);
    } catch {
      return null;
    }
  }).filter(Boolean);
}

module.exports = { persistDeadLetter, listDeadLetters };
