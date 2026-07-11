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
