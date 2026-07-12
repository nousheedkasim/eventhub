require('dotenv').config();
const express = require('express');
const { createQueue } = require('./queues/notificationQueue');
const { processEmailJob } = require('./workers/emailWorker');
const { processWebhookJob } = require('./workers/webhookWorker');
const { listDeadLetters } = require('./utils/deadLetterStore');
const logger = require('./utils/logger');

const app = express();
const PORT = process.env.PORT || 3002;

// Middleware
app.use(express.json());

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ 
    status: 'healthy', 
    service: 'notification-service',
    timestamp: new Date().toISOString()
  });
});

// Dead-letter inspection endpoint
app.get('/dead-letter', (req, res) => {
  const deadLetters = listDeadLetters();
  res.json({
    count: deadLetters.length,
    jobs: deadLetters,
  });
});

// Queue status endpoint
app.get('/queues', async (req, res) => {
  try {
    const emailWaiting = await emailQueue.getWaitingCount();
    const emailActive = await emailQueue.getActiveCount();
    const emailCompleted = await emailQueue.getCompletedCount();
    const emailFailed = await emailQueue.getFailedCount();

    const webhookWaiting = await webhookQueue.getWaitingCount();
    const webhookActive = await webhookQueue.getActiveCount();
    const webhookCompleted = await webhookQueue.getCompletedCount();
    const webhookFailed = await webhookQueue.getFailedCount();

    res.json({
      email: {
        waiting: emailWaiting,
        active: emailActive,
        completed: emailCompleted,
        failed: emailFailed,
      },
      webhook: {
        waiting: webhookWaiting,
        active: webhookActive,
        completed: webhookCompleted,
        failed: webhookFailed,
      },
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to get queue status' });
  }
});

// View waiting jobs endpoint
app.get('/queues/email/waiting', async (req, res) => {
  try {
    const jobs = await emailQueue.getWaiting(0, 10);
    const jobDetails = jobs.map(job => ({
      id: job.id,
      name: job.name,
      data: job.data,
      timestamp: job.timestamp,
    }));
    res.json({
      count: jobDetails.length,
      jobs: jobDetails,
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to get waiting email jobs' });
  }
});

app.get('/queues/webhook/waiting', async (req, res) => {
  try {
    const jobs = await webhookQueue.getWaiting(0, 10);
    const jobDetails = jobs.map(job => ({
      id: job.id,
      name: job.name,
      data: job.data,
      timestamp: job.timestamp,
    }));
    res.json({
      count: jobDetails.length,
      jobs: jobDetails,
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to get waiting webhook jobs' });
  }
});

// Shared secret auth middleware for internal API routes
function authenticateInternal(req, res, next) {
  const secret = process.env.CORE_API_SECRET || 'secure_shared_secret';
  const authHeader = req.headers['x-internal-secret'];

  if (!authHeader || authHeader !== secret) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
}

// POST /api/notifications/email - Enqueue an email notification
app.post('/api/notifications/email', authenticateInternal, async (req, res) => {
  try {
    const { type, data } = req.body;

    if (!type || !data) {
      return res.status(400).json({ error: 'Missing required fields: type, data' });
    }

    const job = await emailQueue.add({ type, data }, {
      attempts: parseInt(process.env.NOTIFICATION_MAX_RETRIES || '5', 10),
      backoff: { type: 'exponential', delay: 1000 },
    });

    logger.info(`[API] Email notification enqueued: type=${type}, jobId=${job.id}`);

    res.status(201).json({ success: true, job_id: job.id });
  } catch (error) {
    logger.error('[API] Failed to enqueue email notification:', error);
    res.status(500).json({ error: 'Failed to enqueue email notification' });
  }
});

// POST /api/notifications/webhook - Enqueue a webhook delivery
app.post('/api/notifications/webhook', authenticateInternal, async (req, res) => {
  try {
    const { type, data } = req.body;

    if (!type || !data) {
      return res.status(400).json({ error: 'Missing required fields: type, data' });
    }

    const job = await webhookQueue.add({ type, data }, {
      attempts: parseInt(process.env.NOTIFICATION_MAX_RETRIES || '5', 10),
      backoff: { type: 'exponential', delay: 1000 },
    });

    logger.info(`[API] Webhook notification enqueued: type=${type}, jobId=${job.id}`);

    res.status(201).json({ success: true, job_id: job.id });
  } catch (error) {
    logger.error('[API] Failed to enqueue webhook notification:', error);
    res.status(500).json({ error: 'Failed to enqueue webhook notification' });
  }
});

// Initialize queues and workers
let emailQueue, webhookQueue;

async function startServer() {
  try {
    // Initialize queues
    emailQueue = await createQueue('email-notifications');
    webhookQueue = await createQueue('webhook-notifications');

    logger.info('Notification queues initialized successfully');

    // Register email worker
    emailQueue.process('*', 5, (job) => processEmailJob(job));
    logger.info('Email worker registered');

    // Register webhook worker
    webhookQueue.process('*', 3, (job) => processWebhookJob(job));
    logger.info('Webhook worker registered');

    // Start Express server
    app.listen(PORT, () => {
      logger.info(`Notification Service running on port ${PORT}`);
      logger.info(`Redis: ${process.env.REDIS_HOST}:${process.env.REDIS_PORT}`);
    });
  } catch (error) {
    logger.error('Failed to start notification service:', error);
    process.exit(1);
  }
}

// Graceful shutdown
process.on('SIGTERM', async () => {
  logger.info('SIGTERM received, shutting down gracefully...');
  
  if (emailQueue) await emailQueue.close();
  if (webhookQueue) await webhookQueue.close();
  
  process.exit(0);
});

process.on('SIGINT', async () => {
  logger.info('SIGINT received, shutting down gracefully...');
  
  if (emailQueue) await emailQueue.close();
  if (webhookQueue) await webhookQueue.close();
  
  process.exit(0);
});

startServer();
