const Queue = require('bull');
const Redis = require('ioredis');
const logger = require('../utils/logger');
const { persistDeadLetter } = require('../utils/deadLetterStore');

// Redis connection
const redisConfig = {
  host: process.env.REDIS_HOST || 'localhost',
  port: process.env.REDIS_PORT || 6379,
  password: process.env.REDIS_PASSWORD || undefined,
  maxRetriesPerRequest: null,
};

const MAX_RETRIES = parseInt(process.env.NOTIFICATION_MAX_RETRIES || '5', 10);

/**
 * Create a Bull queue for processing notifications
 */
function createQueue(queueName) {
  const queue = new Queue(queueName, {
    redis: redisConfig,
    defaultJobOptions: {
      attempts: MAX_RETRIES,
      backoff: {
        type: 'exponential',
        delay: 1000, // 1s, 2s, 4s, 8s, 16s
      },
      removeOnComplete: {
        age: 3600,
        count: 1000,
      },
      removeOnFail: {
        age: 86400,
      },
    },
  });

  queue.on('completed', (job, result) => {
    logger.info(`Job ${job.id} in queue ${queueName} completed:`, result);
  });

  queue.on('failed', (job, err) => {
    logger.error(`Job ${job.id} in queue ${queueName} failed (attempt ${job.attemptsMade}/${job.opts.attempts}):`, err.message);

    if (job.attemptsMade >= job.opts.attempts) {
      logger.error(`[DEAD LETTER] Job ${job.id} in queue ${queueName} exhausted all ${job.opts.attempts} retries. Persisting to dead-letter store.`);
      persistDeadLetter(queueName, job, err);
    }
  });

  queue.on('stalled', (job) => {
    logger.warn(`Job ${job.id} in queue ${queueName} stalled`);
  });

  return queue;
}

module.exports = { createQueue };
