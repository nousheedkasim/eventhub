const emailService = require('../services/emailService');
const logger = require('../utils/logger');

/**
 * Process email notification jobs from the queue
 */
async function processEmailJob(job) {
  const { type, data } = job.data;

  logger.info(`[EMAIL WORKER] Processing ${type} email job #${job.id}`);

  try {
    let result;

    switch (type) {
      case 'order_confirmation':
        result = await emailService.sendOrderConfirmation(data);
        break;
      
      case 'event_reminder':
        result = await emailService.sendEventReminder(data);
        break;
      
      case 'payout_notification':
        result = await emailService.sendPayoutNotification(data);
        break;
      
      case 'vendor_approval':
        result = await emailService.sendVendorApprovalNotification(data);
        break;
      
      default:
        throw new Error(`Unknown email type: ${type}`);
    }

    logger.info(`[EMAIL WORKER] Job #${job.id} completed successfully`);
    return result;
    
  } catch (error) {
    logger.error(`[EMAIL WORKER] Job #${job.id} failed:`, error);
    throw error;
  }
}

module.exports = { processEmailJob };
