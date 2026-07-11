const webhookService = require('../services/webhookService');
const logger = require('../utils/logger');

/**
 * Process webhook delivery jobs from the queue
 */
async function processWebhookJob(job) {
  const { type, data } = job.data;

  logger.info(`[WEBHOOK WORKER] Processing ${type} webhook job #${job.id}`);

  try {
    let result;

    switch (type) {
      case 'order_webhook':
        result = await webhookService.deliverOrderWebhook(data);
        break;
      
      case 'payout_webhook':
        result = await webhookService.deliverPayoutWebhook(data);
        break;
      
      default:
        throw new Error(`Unknown webhook type: ${type}`);
    }

    logger.info(`[WEBHOOK WORKER] Job #${job.id} completed successfully`);
    return result;
    
  } catch (error) {
    logger.error(`[WEBHOOK WORKER] Job #${job.id} failed:`, error);
    throw error;
  }
}

module.exports = { processWebhookJob };
